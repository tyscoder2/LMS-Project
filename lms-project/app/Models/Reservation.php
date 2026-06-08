<?php
class Reservation {
    private $db;

    public function __construct($pdoConn) {
        $this->db = $pdoConn;
    }

    /**
     * Locate a specific reservation and its borrower metadata.
     */
    public function getReservationById(int $res_id) {
        $sql = "SELECT r.*, br.user_id
                FROM reservations r
                JOIN borrowers br ON r.borrower_id = br.id
                WHERE r.id = :res_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['res_id' => $res_id]);
        return $stmt->fetch();
    }

    /**
     * Perform the mutation updating a reservation's lifecycle status.
     */
    public function updateStatus(int $res_id, string $status) {
        $sql = "UPDATE reservations SET status = :status WHERE id = :res_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'res_id' => $res_id
        ]);
    }

    /**
     * Gather a filtered collection of reservations based on authorization and context rules.
     * UPDATED: Added b.cover_image to the query selections block.
     */
    public function getAllReservations(int $user_id, bool $is_staff, string $search_query, string $sort_selection, bool $filter_id, bool $filter_title, bool $filter_username) {
        $select_fields = "r.id AS reservation_id, r.reserved_date, r.status,
                          b.title AS book_title, b.author AS book_author, b.isbn, b.cover_image,
                          u.username, u.id AS user_uuid, br.name AS borrower_name";

        $sql = "SELECT $select_fields FROM reservations r
                JOIN books b ON r.book_id = b.id
                JOIN borrowers br ON r.borrower_id = br.id
                JOIN users u ON br.user_id = u.id";

        $where_clauses = [];
        $query_params = [];

        // Enforce data privacy access controls for basic profiles
        if (!$is_staff) {
            $where_clauses[] = "br.user_id = :session_user_id";
            $query_params['session_user_id'] = $user_id;
        }

        // Evaluate search parameters cleanly against permitted entities
        if (!empty($search_query)) {
            $search_subconditions = [];
            if ($filter_id) {
                $search_subconditions[] = "r.id LIKE :s_id";
                $query_params['s_id'] = "%$search_query%";
            }
            if ($filter_title) {
                $search_subconditions[] = "b.title LIKE :s_title";
                $query_params['s_title'] = "%$search_query%";
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

        // Apply chosen sort sequences
        if ($sort_selection === 'oldest') {
            $sql .= " ORDER BY r.id ASC";
        } else {
            $sql .= " ORDER BY r.id DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($query_params);
        return $stmt->fetchAll();
    }
}
