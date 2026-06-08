<?php

class Borrower {
    private $db;

    public function __construct($pdoConn) {
        $this->db = $pdoConn;
    }

    /**
     * Fetch all system accounts coupled with borrower records based on filter profiles
     */
    public function getAllUsersWithFilters($search_query, $sort_selection, $filters) {
        $query_sql = "SELECT users.id, users.username, users.email, users.role, users.created_at,
                             borrowers.student_id, borrowers.name, borrowers.course, borrowers.contact
                      FROM users
                      LEFT JOIN borrowers ON users.id = borrowers.user_id";

        $where_clauses = [];
        $query_params = [];

        if (!empty($search_query)) {
            $search_subconditions = [];
            if ($filters['name']) {
                $search_subconditions[] = "borrowers.name LIKE :s_name";
                $query_params['s_name'] = "%$search_query%";
            }
            if ($filters['username']) {
                $search_subconditions[] = "users.username LIKE :s_user";
                $query_params['s_user'] = "%$search_query%";
            }
            if ($filters['email']) {
                $search_subconditions[] = "users.email LIKE :s_email";
                $query_params['s_email'] = "%$search_query%";
            }

            if (!empty($search_subconditions)) {
                $where_clauses[] = "(" . implode(" OR ", $search_subconditions) . ")";
            }
        }

        if ($filters['borrowers']) {
            $where_clauses[] = "users.role = 'student'";
        }

        if (!empty($where_clauses)) {
            $query_sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        if ($sort_selection === 'oldest') {
            $query_sql .= " ORDER BY users.id ASC";
        } elseif ($sort_selection === 'alphabetical') {
            $query_sql .= " ORDER BY users.username ASC";
        } else {
            $query_sql .= " ORDER BY users.id DESC";
        }

        $stmt = $this->db->prepare($query_sql);
        $stmt->execute($query_params);
        return $stmt->fetchAll();
    }

    /**
     * Atomically process modifications for both primary credentials and detailed profiles
     */
    public function updateUserAndBorrower($userId, $userData, $borrowerData) {
        try {
            $this->db->beginTransaction();

            $user_update = $this->db->prepare("UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id");
            $user_update->execute([
                'username' => $userData['username'],
                'email'    => $userData['email'],
                'role'     => $userData['role'],
                'id'       => $userId
            ]);

            $borrower_check = $this->db->prepare("SELECT id FROM borrowers WHERE user_id = :user_id LIMIT 1");
            $borrower_check->execute(['user_id' => $userId]);

            if ($borrower_check->fetch()) {
                $borrower_update = $this->db->prepare("UPDATE borrowers SET student_id = :student_id, name = :name, course = :course, contact = :contact WHERE user_id = :user_id");
                $borrower_update->execute([
                    'student_id' => $borrowerData['student_id'],
                    'name'       => $borrowerData['name'],
                    'course'     => $borrowerData['course'],
                    'contact'    => $borrowerData['contact'],
                    'user_id'    => $userId
                ]);
            } else {
                $borrower_insert = $this->db->prepare("INSERT INTO borrowers (user_id, student_id, name, course, contact) VALUES (:user_id, :student_id, :name, :course, :contact)");
                $borrower_insert->execute([
                    'user_id'    => $userId,
                    'student_id' => $borrowerData['student_id'],
                    'name'       => $borrowerData['name'],
                    'course'     => $borrowerData['course'],
                    'contact'    => $borrowerData['contact']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove linked identities systematically via controlled transaction steps
     */
    public function deleteUserAndBorrower($userId) {
        try {
            $this->db->beginTransaction();

            $delete_borrower = $this->db->prepare("DELETE FROM borrowers WHERE user_id = :user_id");
            $delete_borrower->execute(['user_id' => $userId]);

            $delete_user = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $delete_user->execute(['id' => $userId]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
