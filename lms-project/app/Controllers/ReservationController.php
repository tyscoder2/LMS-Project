<?php
require_once __DIR__ . '/../Models/Reservation.php';

class ReservationController {
    private $reservationModel;

    public function __construct($pdoConn) {
        $this->reservationModel = new Reservation($pdoConn);
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Enforce user authentication state guards
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id   = $_SESSION['user_id'];
        $user_role = strtolower($_SESSION['role'] ?? 'student');
        $is_staff  = ($user_role === 'admin' || $user_role === 'librarian');

        $success_msg = "";
        $error_msg = "";

        // POST Request Management Engine
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action_fulfill']) || isset($_POST['action_cancel']))) {
            $target_res_id = (int)$_POST['reservation_id'];
            $target_status = isset($_POST['action_fulfill']) ? 'fulfilled' : 'cancelled';

            $res_record = $this->reservationModel->getReservationById($target_res_id);

            if ($res_record) {
                if (!$is_staff && (int)$res_record['user_id'] !== (int)$user_id) {
                    $error_msg = "Unauthorized operations blocked by system core rules.";
                } elseif ($res_record['status'] !== 'pending') {
                    $error_msg = "This reservation has already been finalized as " . $res_record['status'] . ".";
                } else {
                    $this->reservationModel->updateStatus($target_res_id, $target_status);
                    $success_msg = "Reservation successfully marked as " . ucfirst($target_status) . ".";
                }
            } else {
                $error_msg = "Target ledger reservation record was not found.";
            }
        }

        // Handle parameters coming from search component interactions
        $search_query   = trim($_GET['search'] ?? '');
        $sort_selection = $_GET['sort'] ?? 'newest';

        $filter_id       = isset($_GET['f_id']);
        $filter_title    = isset($_GET['f_title']);
        $filter_username = isset($_GET['f_username']) && $is_staff;

        if (!$filter_id && !$filter_title && !$filter_username) {
            $filter_id = true;
            $filter_title = true;
            if ($is_staff) {
                $filter_username = true;
            }
        }

        try {
            $reservations_collection = $this->reservationModel->getAllReservations(
                $user_id,
                $is_staff,
                $search_query,
                $sort_selection,
                $filter_id,
                $filter_title,
                $filter_username
            );
        } catch (\Exception $e) {
            $error_msg = "Database Connection Infrastructure Error: " . $e->getMessage();
            $reservations_collection = [];
        }

        // Package scope payload matrices cleanly
        return [
            'is_staff'                => $is_staff,
            'search_query'            => $search_query,
            'sort_selection'          => $sort_selection,
            'filter_id'               => $filter_id,
            'filter_title'            => $filter_title,
            'filter_username'         => $filter_username,
            'success_msg'             => $success_msg,
            'error_msg'               => $error_msg,
            'reservations_collection' => $reservations_collection
        ];
    }
}
