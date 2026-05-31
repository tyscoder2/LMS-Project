<?php
/**
 * BorrowerController.php
 * Handles administration views for accounts containing active logs,
 * plus business logic workflows for checkouts and returns.
 */

class BorrowerController {
    private $borrowerModel;

    public function __construct() {
        require_once __DIR__ . '/../Models/Borrower.php';
        $this->borrowerModel = new Borrower();
    }

    /**
     * Lists all library users with active history logs
     */
    public function index() {
        // Block unauthorized access directly at routing endpoint level
        if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
            header("Location: /error");
            exit();
        }

        $search = trim($_GET['search'] ?? '');
        $sort   = $_GET['sort'] ?? 'newest';

        // Query analytical totals aggregated across user associations
        $borrowers = $this->borrowerModel->listBorrowersWithTransactions($search, $sort);

        require_once __DIR__ . '/../Views/borrowers/index.php';
    }

    /**
     * Initiates an outbound material loan transaction allocation
     */
    public function checkout() {
        $userId = (int)($_POST['user_id'] ?? 0);
        $bookId = (int)($_POST['book_id'] ?? 0);

        if ($userId && $bookId) {
            $result = $this->borrowerModel->checkoutBook($userId, $bookId);
            if ($result['status']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    /**
     * Returns a book copy back into system availability
     */
    public function returnBook() {
        $transactionId = (int)($_POST['transaction_id'] ?? 0);

        if ($transactionId) {
            $result = $this->borrowerModel->settleReturn($transactionId);
            if ($result['status']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
