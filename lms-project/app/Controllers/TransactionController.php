<?php
require_once __DIR__ . '/../Models/Transaction.php';

class TransactionController {
    private $transactionModel;

    public function __construct($pdoConn) {
        $this->transactionModel = new Transaction($pdoConn);
    }

    public function index() {
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_return_book'])) {
            $target_tx_id = (int)$_POST['transaction_id'];
            $tx_record = $this->transactionModel->getTransactionById($target_tx_id);

            if ($tx_record) {
                $is_already_returned = !empty($tx_record['return_date']) && $tx_record['return_date'] !== '0000-00-00';

                if (!$is_staff && (int)$tx_record['tx_user_id'] !== (int)$user_id) {
                    $error_msg = "Unauthorized operations blocked by system core rules.";
                } elseif ($is_already_returned) {
                    $error_msg = "This item has already been checked into the library system inventory.";
                } else {
                    $current_date = date('Y-m-d');
                    $computed_fines = 0.00;

                    if (strtotime($current_date) > strtotime($tx_record['due_date'])) {
                        $overdue_seconds = strtotime($current_date) - strtotime($tx_record['due_date']);
                        $overdue_days = ceil($overdue_seconds / (60 * 60 * 24));
                        $computed_fines = $overdue_days * 5.00;
                    }

                    try {
                        $this->transactionModel->returnBookItem($tx_record, $current_date, $computed_fines);
                        header("Location: index.php?page=returned&transaction_id=" . $target_tx_id);
                        exit();
                    } catch (\Exception $e) {
                        $error_msg = "Transaction Core execution failed: " . $e->getMessage();
                    }
                }
            } else {
                $error_msg = "Target ledger record item was not found.";
            }
        }

        $search_query   = trim($_GET['search'] ?? '');
        $sort_selection = $_GET['sort'] ?? 'newest';

        $filter_title    = isset($_GET['f_title']);
        $filter_author   = isset($_GET['f_author']);
        $filter_username = isset($_GET['f_username']) && $is_staff;

        if (!$filter_title && !$filter_author && !$filter_username) {
            $filter_title = $filter_author = true;
            if ($is_staff) {
                $filter_username = true;
            }
        }

        try {
            $transactions_collection = $this->transactionModel->getAllTransactions(
                $user_id,
                $is_staff,
                $search_query,
                $sort_selection,
                $filter_title,
                $filter_author,
                $filter_username
            );
        } catch (\PDOException $e) {
            $error_msg = "Database Connection Infrastructure Error: " . $e->getMessage();
            $transactions_collection = [];
        }

        return [
            'is_staff'                => $is_staff,
            'search_query'            => $search_query,
            'sort_selection'          => $sort_selection,
            'filter_title'            => $filter_title,
            'filter_author'           => $filter_author,
            'filter_username'         => $filter_username,
            'success_msg'             => $success_msg,
            'error_msg'               => $error_msg,
            'transactions_collection' => $transactions_collection
        ];
    }

    /**
     * Parse layout verification metrics specifically for book RETURNS receipts
     */
    public function returnConfirmation() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $transaction_id_input = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);

        $details = [
            'display_id'   => "TXN-" . strtoupper(bin2hex(random_bytes(4))),
            'book_title'   => "Introduction to Computer Science",
            'book_id'      => "BK-9021",
            'user_name'    => $_SESSION['username'] ?? "John Doe",
            'user_id'      => "STU-0422",
            'primary_date' => date('F d, Y'),
            'due_date'     => date('F d, Y', strtotime('+14 days')),
            'return_date'  => date('F d, Y'),
            'fines'        => "$0.00",
            'cover_image'  => null
        ];

        try {
            if (!empty($transaction_id_input)) {
                $db_data = $this->transactionModel->getTransactionConfirmation($transaction_id_input);
                if ($db_data) {
                    $details = [
                        'display_id'   => 'TX-' . str_pad($db_data['transaction_id'], 5, '0', STR_PAD_LEFT),
                        'book_title'   => $db_data['book_title'],
                        'book_id'      => $db_data['book_id'],
                        'user_name'    => $db_data['user_name'],
                        'user_id'      => $db_data['user_id'],
                        'primary_date' => date('F d, Y', strtotime($db_data['borrow_date'])),
                        'due_date'     => date('F d, Y', strtotime($db_data['due_date'])),
                        'return_date'  => (!empty($db_data['return_date']) && $db_data['return_date'] !== '0000-00-00') ? date('F d, Y', strtotime($db_data['return_date'])) : date('F d, Y'),
                        'fines'        => '$' . number_format((float)($db_data['fines'] ?? 0.00), 2),
                        'cover_image'  => !empty($db_data['cover_image']) ? $db_data['cover_image'] : null
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fallback gracefully
        }

        return [
            'is_reservation' => false,
            'details'        => $details
        ];
    }

    /**
     * Parse layout verification metrics specifically for book BORROWS or RESERVATIONS receipts
     */
    public function borrowConfirmation() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $transaction_id_input = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);
        $reservation_id_input = filter_input(INPUT_GET, 'reservation_id', FILTER_VALIDATE_INT);
        $is_reservation = !empty($reservation_id_input);

        $details = [
            'display_id'   => "TXN-" . strtoupper(bin2hex(random_bytes(4))),
            'book_title'   => "Introduction to Computer Science",
            'book_id'      => "BK-9021",
            'user_name'    => $_SESSION['username'] ?? "John Doe",
            'user_id'      => "STU-0422",
            'primary_date' => date('F d, Y'),
            'due_date'     => date('F d, Y', strtotime('+14 days')),
            'cover_image'  => null
        ];

        try {
            if ($is_reservation && !empty($reservation_id_input)) {
                $db_data = $this->transactionModel->getReservationConfirmation($reservation_id_input);
                if ($db_data) {
                    $details = [
                        'display_id'   => 'RES-' . str_pad($db_data['reservation_id'], 5, '0', STR_PAD_LEFT),
                        'book_title'   => $db_data['book_title'],
                        'book_id'      => $db_data['book_id'],
                        'user_name'    => $db_data['user_name'],
                        'user_id'      => $db_data['user_id'],
                        'primary_date' => date('F d, Y', strtotime($db_data['reserved_date'])),
                        'due_date'     => "N/A (Pending Item Allocation)",
                        'cover_image'  => !empty($db_data['cover_image']) ? $db_data['cover_image'] : null
                    ];
                }
            } elseif (!empty($transaction_id_input)) {
                $db_data = $this->transactionModel->getTransactionConfirmation($transaction_id_input);
                if ($db_data) {
                    $details = [
                        'display_id'   => 'TX-' . str_pad($db_data['transaction_id'], 5, '0', STR_PAD_LEFT),
                        'book_title'   => $db_data['book_title'],
                        'book_id'      => $db_data['book_id'],
                        'user_name'    => $db_data['user_name'],
                        'user_id'      => $db_data['user_id'],
                        'primary_date' => date('F d, Y', strtotime($db_data['borrow_date'])),
                        'due_date'     => date('F d, Y', strtotime($db_data['due_date'])),
                        'cover_image'  => !empty($db_data['cover_image']) ? $db_data['cover_image'] : null
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fallback gracefully
        }

        return [
            'is_reservation' => $is_reservation,
            'details'        => $details
        ];
    }
}
