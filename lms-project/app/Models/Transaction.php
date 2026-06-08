<?php
class Transaction {
    private $db;

    public function __construct($pdoConn) {
        $this->db = $pdoConn;
    }

    /**
     * Retrieve a specific transaction record cross-referenced with borrower context.
     */
    public function getTransactionById(int $tx_id) {
        $sql = "SELECT t.*, b.id AS book_table_id, br.user_id AS tx_user_id
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                JOIN borrowers br ON t.borrower_id = br.id
                WHERE t.id = :tx_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tx_id' => $tx_id]);
        return $stmt->fetch();
    }

    /**
     * Execute an atomic database transaction updating transaction log,
     * generating potential fines, and returning stock to book inventory.
     */
    public function returnBookItem(array $tx_record, string $current_date, float $computed_fines) {
        try {
            $this->db->beginTransaction();

            // Update transaction table fields
            $update_tx_sql = "UPDATE transactions SET return_date = :ret_date, fine = :fine WHERE id = :tx_id";
            $up_tx_stmt = $this->db->prepare($update_tx_sql);
            $up_tx_stmt->execute([
                'ret_date' => $current_date,
                'fine'     => $computed_fines,
                'tx_id'    => $tx_record['id']
            ]);

            // Register overdue liability balances within the fines index directory if applicable
            if ($computed_fines > 0) {
                $insert_fine_sql = "INSERT INTO fines (transaction_id, amount, paid) VALUES (:tx_id, :amount, 0)";
                $ins_fine_stmt = $this->db->prepare($insert_fine_sql);
                $ins_fine_stmt->execute([
                    'tx_id'  => $tx_record['id'],
                    'amount' => $computed_fines
                ]);
            }

            // Return physical copy stock properties cleanly to system tables
            $update_inventory_sql = "UPDATE books SET copies = copies + 1 WHERE id = :bk_id";
            $up_inv_stmt = $this->db->prepare($update_inventory_sql);
            $up_inv_stmt->execute(['bk_id' => $tx_record['book_table_id']]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Pull down matching collection arrays conforming to dynamic query parameters.
     */
    public function getAllTransactions(int $user_id, bool $is_staff, string $search_query, string $sort_selection, bool $filter_title, bool $filter_author, bool $filter_username) {
        $select_fields = "t.id AS tx_id, t.borrow_date, t.due_date, t.return_date, t.fine AS fines,
                          b.title AS book_title, b.author AS book_author, b.id AS book_uuid, b.cover_image,
                          u.username, u.id AS user_uuid";

        $sql = "SELECT $select_fields FROM transactions t
                JOIN books b ON t.book_id = b.id
                JOIN borrowers br ON t.borrower_id = br.id
                JOIN users u ON br.user_id = u.id";

        $where_clauses = [];
        $query_params = [];

        // Privacy boundary protection logic
        if (!$is_staff) {
            $where_clauses[] = "br.user_id = :session_user_id";
            $query_params['session_user_id'] = $user_id;
        }

        // Apply specified system search metrics cleanly
        if (!empty($search_query)) {
            $search_subconditions = [];
            if ($filter_title) {
                $search_subconditions[] = "b.title LIKE :s_title";
                $query_params['s_title'] = "%$search_query%";
            }
            if ($filter_author) {
                $search_subconditions[] = "b.author LIKE :s_author";
                $query_params['s_author'] = "%$search_query%";
            }
            if ($filter_username && $is_staff) {
                $search_subconditions[] = "u.username LIKE :s_user";
                $query_params['s_user'] = "%$search_query%";
            }

            if (!empty($search_subconditions)) {
                $where_clauses[] = "(" . implode(" OR ", $search_subconditions) . ")";
            }
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        // Apply configured sorting preferences
        if ($sort_selection === 'oldest') {
            $sql .= " ORDER BY t.id ASC";
        } else {
            $sql .= " ORDER BY t.id DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($query_params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch isolated context properties matching verified transaction operations
     * UPDATED: Added return_date and fine collection capabilities
     */
    public function getTransactionConfirmation(int $tx_id) {
        $query = "SELECT t.id AS transaction_id, b.title AS book_title, b.isbn AS book_id,
                         b.cover_image, br.name AS user_name, br.student_id AS user_id,
                         t.borrow_date, t.due_date, t.return_date, t.fine AS fines
                  FROM transactions t
                  JOIN books b ON t.book_id = b.id
                  JOIN borrowers br ON t.borrower_id = br.id
                  WHERE t.id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $tx_id]);
        return $stmt->fetch();
    }

    /**
     * Fetch isolated context properties matching verified reservation operations
     */
    public function getReservationConfirmation(int $res_id) {
        $query = "SELECT r.id AS reservation_id, b.title AS book_title, b.isbn AS book_id,
                         b.cover_image, br.name AS user_name, br.student_id AS user_id,
                         r.reserved_date
                  FROM reservations r
                  JOIN books b ON r.book_id = b.id
                  JOIN borrowers br ON r.borrower_id = br.id
                  WHERE r.id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $res_id]);
        return $stmt->fetch();
    }
}
