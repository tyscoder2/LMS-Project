<?php
/**
 * Transaction.php
 * Handles multi-table relational lookups for account ledgers while enforcing
 * user-level profile separation boundaries on datasets.
 */

class Transaction {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../../config/database.php';
        $this->db = Database::connect();
    }

    /**
     * Builds relational loan log models dynamically optimized by roles
     */
    public function getLogHistory($currentUserId, $isStaff = false, $searchText = '', $activeFilters = [], $sortBy = 'newest') {
        $params = [];
        $where = [];

        $sql = "SELECT t.id AS transaction_id, t.user_id, t.book_id, t.borrow_date, t.due_date, t.return_date, t.fines,
                       u.username, u.name AS borrower_name, b.title AS book_title, b.author AS book_author, b.cover_image
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                JOIN books b ON t.book_id = b.id";

        // Privacy Isolation Check: regular student users are limited strictly to their own rows
        if (!$isStaff) {
            $where[] = "t.user_id = :session_user";
            $params['session_user'] = (int)$currentUserId;
        }

        // Apply interactive UI filter states securely
        if (!empty($searchText)) {
            $sub = [];
            if (!empty($activeFilters['id'])) {
                $sub[] = "t.id LIKE :s_id";
                $params['s_id'] = "%$searchText%";
            }
            if (!empty($activeFilters['title'])) {
                $sub[] = "b.title LIKE :s_title";
                $params['s_title'] = "%$searchText%";
            }
            if (!empty($activeFilters['username']) && $isStaff) {
                $sub[] = "u.username LIKE :s_user";
                $params['s_user'] = "%$searchText%";
            }

            if (!empty($sub)) {
                $where[] = "(" . implode(" OR ", $sub) . ")";
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= ($sortBy === 'oldest') ? " ORDER BY t.id ASC" : " ORDER BY t.id DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Model Error [Transaction::getLogHistory]: " . $e->getMessage());
            return [];
        }
    }
}
