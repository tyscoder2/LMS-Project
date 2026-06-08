<?php
require_once __DIR__ . '/../Models/Fine.php';

class FineController {
    private $fineModel;

    public function __construct($pdoConn) {
        $this->fineModel = new Fine($pdoConn);
    }

    public function index() {
        // Interceptor protection layer checking session context mappings
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id   = $_SESSION['user_id'];
        $user_role = strtolower($_SESSION['role'] ?? 'student');
        $is_staff  = ($user_role === 'admin' || $user_role === 'librarian');

        $success_msg = "";
        $error_msg = "";

        // POST Data Processing Core Router Block
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_settle_fine'])) {
            $target_fine_id = (int)$_POST['fine_id'];
            $fine_record = $this->fineModel->getFineById($target_fine_id);

            if ($fine_record) {
                if (!$is_staff && (int)$fine_record['user_id'] !== (int)$user_id) {
                    $error_msg = "Unauthorized operations blocked by system core rules.";
                } elseif ((int)$fine_record['paid'] === 1) {
                    $error_msg = "This fee record has already been marked settled.";
                } else {
                    $this->fineModel->settleFine($target_fine_id, date('Y-m-d'));
                    $success_msg = "Fine settlement recorded successfully.";
                }
            } else {
                $error_msg = "Target ledger record item was not found.";
            }
        }

        // Get filter metrics configuration maps
        $search_query   = trim($_GET['search'] ?? '');
        $sort_selection = $_GET['sort'] ?? 'newest';

        $filter_id       = isset($_GET['f_id']);
        $filter_username = isset($_GET['f_username']) && $is_staff;

        if (!$filter_id && !$filter_username) {
            $filter_id = true;
            if ($is_staff) {
                $filter_username = true;
            }
        }

        // Query execution handling loops safely encapsulated inside datasets maps
        try {
            $fines_collection = $this->fineModel->getAllFines(
                $user_id,
                $is_staff,
                $search_query,
                $sort_selection,
                $filter_id,
                $filter_username
            );
        } catch (\Exception $e) {
            $error_msg = "Database Connection Infrastructure Error: " . $e->getMessage();
            $fines_collection = [];
        }

        // Compile and pack parameters payload for context mapping matrix
        return [
            'is_staff'         => $is_staff,
            'search_query'     => $search_query,
            'sort_selection'   => $sort_selection,
            'filter_id'        => $filter_id,
            'filter_username'  => $filter_username,
            'success_msg'      => $success_msg,
            'error_msg'        => $error_msg,
            'fines_collection' => $fines_collection
        ];
    }
}
