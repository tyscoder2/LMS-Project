<?php
require_once __DIR__ . '/../Models/Borrower.php';

class BorrowerController {
    private $borrowerModel;

    public function __construct($pdoConn) {
        $this->borrowerModel = new Borrower($pdoConn);
    }

    /**
     * Main dispatch gateway routing action for user record ledger management
     */
    public function manageUsers() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Strict Access Control Guard Wall
        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
            header("Location: index.php?page=error");
            exit();
        }

        $success_msg = "";
        $error_msg = "";

        // Catch incoming entity processing mutations
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action_edit_user'])) {
                $edit_id = (int)($_POST['user_id'] ?? 0);
                $username = trim($_POST['username'] ?? '');

                if ($edit_id > 0 && !empty($username)) {
                    try {
                        $userData = [
                            'username' => $username,
                            'email'    => trim($_POST['email'] ?? ''),
                            'role'     => trim($_POST['role'] ?? 'student')
                        ];
                        $borrowerData = [
                            'student_id' => trim($_POST['student_id'] ?? ''),
                            'name'       => trim($_POST['name'] ?? ''),
                            'course'     => trim($_POST['course'] ?? ''),
                            'contact'    => trim($_POST['contact'] ?? '')
                        ];

                        $this->borrowerModel->updateUserAndBorrower($edit_id, $userData, $borrowerData);
                        $success_msg = "User record modifications preserved cleanly to database matrix infrastructure.";
                    } catch (\Exception $e) {
                        $error_msg = "Database Infrastructure Error: " . $e->getMessage();
                    }
                } else {
                    $error_msg = "Process Interruption: Username target data strings cannot be left empty.";
                }
            }

            if (isset($_POST['action_delete_user'])) {
                $delete_id = (int)($_POST['user_id'] ?? 0);
                if ($delete_id > 0) {
                    try {
                        $this->borrowerModel->deleteUserAndBorrower($delete_id);
                        $success_msg = "Account profile dropped from infrastructure memory tracks successfully.";
                    } catch (\Exception $e) {
                        $error_msg = "Database Infrastructure Error: " . $e->getMessage();
                    }
                }
            }
        }

        // Parse search refinement inputs
        $search_query   = trim($_GET['search'] ?? '');
        $sort_selection = $_GET['sort'] ?? 'newest';

        $filters = [
            'name'      => isset($_GET['f_name']),
            'username'  => isset($_GET['f_username']),
            'email'     => isset($_GET['f_email']),
            'borrowers' => isset($_GET['f_borrowers'])
        ];

        // Apply fallback standard properties matrix if zero refinement bounds parsed
        if (!$filters['name'] && !$filters['username'] && !$filters['email'] && !$filters['borrowers']) {
            $filters['name']     = true;
            $filters['username'] = true;
            $filters['email']    = true;
        }

        try {
            $users_collection = $this->borrowerModel->getAllUsersWithFilters($search_query, $sort_selection, $filters);
        } catch (\Exception $e) {
            $error_msg = "Database Infrastructure Error: " . $e->getMessage();
            $users_collection = [];
        }

        return [
            'users_collection' => $users_collection,
            'search_query'     => $search_query,
            'sort_selection'   => $sort_selection,
            'filters'          => $filters,
            'success_msg'      => $success_msg,
            'error_msg'        => $error_msg,
            'course_options'   => ['BSCS', 'BSEd', 'BEEd']
        ];
    }
}
