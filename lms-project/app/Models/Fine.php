<?php
class Fine {
    private $db;

    public function __construct($pdoConn) {
        $this->db = $pdoConn;
    }

    /**
     * Look up a single fine record with related ownership checks.
     */
    public function getFineById(int $fine_id) {
        $sql = "SELECT f.*, br.user_id
                FROM fines f
                JOIN transactions t ON f.transaction_id = t.id
                JOIN borrowers br ON t.borrower_id = br.id
                WHERE f.id = :fine_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['fine_id' => $fine_id]);
        return $stmt->fetch();
    }

    /**
     * Mutate fee records to indicate payment clearance status.
     */
    public function settleFine(int $fine_id, string $date) {
        $sql = "UPDATE fines SET paid = 1, paid_date = :p_date WHERE id = :fine_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'p_date'  => $date,
            'fine_id' => $fine_id
        ]);
    }

    /**
     * Retrieve matching collection records across unified structural constraints.
     */
    public function getAllFines(int $user_id, bool $is_staff, string $search_query, string $sort_selection, bool $filter_id, bool $filter_username) {
        $select_fields = "f.id AS fine_id, f.transaction_id, f.amount, f.paid, f.paid_date,
                          u.username, u.id AS user_uuid";

        $sql = "SELECT $select_fields FROM fines f
                JOIN transactions t ON f.transaction_id = t.id
                JOIN borrowers br ON t.borrower_id = br.id
                JOIN users u ON br.user_id = u.id";

        $where_clauses = [];
        $query_params = [];

        // Enforce user-level records protection logic maps for students
        if (!$is_staff) {
            $where_clauses[] = "br.user_id = :session_user_id";
            $query_params['session_user_id'] = $user_id;
        }

        // Add filter configurations safely mapped to search strings
        if (!empty($search_query)) {
            $search_subconditions = [];
            if ($filter_id) {
                $search_subconditions[] = "f.id LIKE :s_id";
                $query_params['s_id'] = "%$search_query%";
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

        // Apply selected order
        if ($sort_selection === 'oldest') {
            $sql .= " ORDER BY f.id ASC";
        } else {
            $sql .= " ORDER BY f.id DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($query_params);
        return $stmt->fetchAll();
    }
}
