<?php

class ProfileController {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    /**
     * Intercepts individual account profiles and processes structural calculation aggregates
     */
    public function index() {
        // Enforce Authentication Guard directly at the application routing level
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $current_user_id = $_SESSION['user_id'];

        // Default structural fallbacks matching the mockup design schema
        $user_profile = [
            'name'             => "User",
            'role'             => $_SESSION['role'] ?? "student",
            'joined_date'      => "N/A",
            'username'         => $_SESSION['username'] ?? "username",
            'id_number'        => $current_user_id,
            'student_id'       => "N/A",
            'email'            => "N/A",
            'course'           => "N/A",
            'contact'          => "N/A",
            'profile_picture'  => null,
            'total_txns'       => 0,
            'times_fined'      => 0,
            'total_fines_paid' => "PHP 0.00"
        ];

        try {
            // Fetch core user credentials combined with matching profiles from borrowers table
            $query = "SELECT u.id AS user_id, u.username, u.email, u.role, u.created_at,
                             b.id AS borrower_id, b.student_id, b.name AS borrower_name, b.course, b.contact
                      FROM users u
                      LEFT JOIN borrowers b ON u.id = b.user_id
                      WHERE u.id = :id LIMIT 1";

            $u_stmt = $this->db->prepare($query);
            $u_stmt->execute(['id' => $current_user_id]);
            $db_user = $u_stmt->fetch(PDO::FETCH_ASSOC);

            if ($db_user) {
                $user_profile['username']    = $db_user['username'];
                $user_profile['role']        = $db_user['role'];
                $user_profile['email']       = $db_user['email'];
                $user_profile['joined_date'] = date('m/d/Y', strtotime($db_user['created_at']));
                $user_profile['id_number']   = $db_user['user_id'];

                // Differentiate formatting logic for Students vs Management Staff
                if (!empty($db_user['borrower_id'])) {
                    $user_profile['name']       = $db_user['borrower_name'];
                    $user_profile['student_id'] = $db_user['student_id'];
                    $user_profile['course']     = $db_user['course'] ?: "N/A";
                    $user_profile['contact']    = $db_user['contact'] ?: "N/A";

                    $borrower_id = $db_user['borrower_id'];

                    // Compute Live Transaction counts linked via borrower profile keys
                    $t_stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM transactions WHERE borrower_id = :borrower_id");
                    $t_stmt->execute(['borrower_id' => $borrower_id]);
                    $user_profile['total_txns'] = $t_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

                    // Compute accurate fine occurrences and sum total paid collections
                    $f_query = "SELECT COUNT(f.id) AS total_fined,
                                       SUM(CASE WHEN f.paid = 1 THEN f.amount ELSE 0.00 END) AS total_paid
                                FROM fines f
                                INNER JOIN transactions t ON f.transaction_id = t.id
                                WHERE t.borrower_id = :borrower_id";

                    $f_stmt = $this->db->prepare($f_query);
                    $f_stmt->execute(['borrower_id' => $borrower_id]);
                    $fine_data = $f_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($fine_data) {
                        $user_profile['times_fined']      = $fine_data['total_fined'] ?? 0;
                        $user_profile['total_fines_paid'] = "PHP " . number_format((float)($fine_data['total_paid'] ?? 0.00), 2);
                    }
                } else {
                    // Admin/Librarian assignments fallback
                    $user_profile['name']       = ucfirst($db_user['username']);
                    $user_profile['student_id'] = "N/A";
                    $user_profile['course']     = "Management Matrix";
                }
            }
        } catch (\PDOException $e) {
            // Context fallbacks are gracefully preserved if a platform execution exception occurs
        }

        return [
            'user_profile' => $user_profile
        ];
    }
}
