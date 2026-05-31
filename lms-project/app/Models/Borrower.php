<?php
/**
 * Borrower.php
 * Models system user profiles tied to active material transactions. Handles
 * real-time transactional balances, late fee logic, and validation overrides.
 */

class Borrower {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../../config/database.php';
        $this->db = Database::connect();
    }

    /**
     * Lists all library users with active history logs
     */
    public function listBorrowersWithTransactions($search = '', $sort = 'newest') {
        $params = [];
        $sql = "SELECT DISTINCT u.id, u.username, u.name, u.email, u.course, u.contact,
                (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) AS raw_tx_count,
                (SELECT SUM(amount) FROM fines WHERE user_id = u.id AND is_paid = 0) AS cumulative_unpaid_fines
                FROM users u
                INNER JOIN transactions t ON u.id = t.user_id";

        if (!empty($search)) {
            $sql .= " WHERE (u.username LIKE :s_user OR u.name LIKE :s_name OR u.email LIKE :s_email)";
            $params['s_user'] = "%$search%";
            $params['s_name'] = "%$search%";
            $params['s_email'] = "%$search%";
        }

        $sql .= ($sort === 'alphabetical') ? " ORDER BY u.username ASC" :
                (($sort === 'oldest') ? " ORDER BY u.id ASC" : " ORDER BY u.id DESC");

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Model Error [Borrower::listWithTransactions]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Atomically locks catalog volumes and provisions an outbound material loan
     */
    public function checkoutBook($userId, $bookId) {
        try {
            $this->db->beginTransaction();

            // 1. Structural Availability Check
            $stmt = $this->db->prepare("SELECT copies, title FROM books WHERE id = :bid FOR UPDATE");
            $stmt->execute(['bid' => $bookId]);
            $book = $stmt->fetch();

            if (!$book || (int)$book['copies'] <= 0) {
                $this->db->rollBack();
                return ['status' => false, 'message' => 'Material out of stock inside inventory systems.'];
            }

            // 2. Financial Liability Check (Block checkout if user has more than 100.00 PHP unpaid fines)
            $fineStmt = $this->db->prepare("SELECT SUM(amount) FROM fines WHERE user_id = :uid AND is_paid = 0");
            $fineStmt->execute(['uid' => $userId]);
            if ((float)$fineStmt->fetchColumn() > 100.00) {
                $this->db->rollBack();
                return ['status' => false, 'message' => 'Account locked: Outstanding liabilities exceed system limits.'];
            }

            // 3. Write transaction log
            $ins = $this->db->prepare("INSERT INTO transactions (user_id, book_id, borrow_date, due_date, return_date, fines)
                                       VALUES (:uid, :bid, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), NULL, 0.00)");
            $ins->execute(['uid' => $userId, 'bid' => $bookId]);

            // 4. Deplete Inventory
            $this->db->prepare("UPDATE books SET copies = copies - 1 WHERE id = :bid")->execute(['bid' => $bookId]);

            $this->db->commit();
            return ['status' => true, 'message' => "Successfully checked out \"{$book['title']}\"."];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Model Error [Borrower::checkout]: " . $e->getMessage());
            return ['status' => false, 'message' => 'Critical internal transaction system failure.'];
        }
    }

    /**
     * Processes inventory check-ins and computes fine balances automatically
     */
    public function settleReturn($transactionId) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = :tid FOR UPDATE");
            $stmt->execute(['tid' => $transactionId]);
            $tx = $stmt->fetch();

            if (!$tx || $tx['return_date'] !== null) {
                $this->db->rollBack();
                return ['status' => false, 'message' => 'Transaction reference invalid or already checked in.'];
            }

            // Delta analysis for fine evaluations (PHP 5.00 per late day standard rate configuration)
            $finesAccrued = 0.00;
            if (time() > strtotime($tx['due_date'])) {
                $daysOverdue = ceil((time() - strtotime($tx['due_date'])) / 86400);
                $finesAccrued = $daysOverdue * 5.00;
            }

            // Commit transaction data state updates
            $updTx = $this->db->prepare("UPDATE transactions SET return_date = NOW(), fines = :fines WHERE id = :tid");
            $updTx->execute(['fines' => $finesAccrued, 'tid' => $transactionId]);

            // Restore book availability index
            $this->db->prepare("UPDATE books SET copies = copies + 1 WHERE id = :bid")->execute(['bid' => $tx['book_id']]);

            // Generate an active uncollected tracking invoice if late penalties accumulate
            if ($finesAccrued > 0) {
                $insFine = $this->db->prepare("INSERT INTO fines (transaction_id, user_id, amount, is_paid, paid_date)
                                               VALUES (:tid, :uid, :amt, 0, NULL)");
                $insFine->execute(['tid' => $transactionId, 'uid' => $tx['user_id'], 'amt' => $finesAccrued]);
            }

            $this->db->commit();
            return ['status' => true, 'message' => 'Item returns registered and processed. Accumulation total: PHP ' . number_format($finesAccrued, 2)];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Model Error [Borrower::settleReturn]: " . $e->getMessage());
            return ['status' => false, 'message' => 'Return pipeline processing exception encountered.'];
        }
    }
}
