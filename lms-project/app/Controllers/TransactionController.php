<?php
/**
 * TransactionController.php
 * Coordinates historical and active borrowing entries, applying strict
 * privacy boundaries to separate regular students from administrative staff.
 */

class TransactionController {
    private $transactionModel;

    public function __construct() {
        require_once __DIR__ . '/../Models/Transaction.php';
        $this->transactionModel = new Transaction();
    }

    /**
     * Display structural loan log directories
     */
    public function index() {
        // Enforce user session verification check walls
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit();
        }

        $currentUserId = $_SESSION['user_id'];
        $userRole      = strtolower($_SESSION['role'] ?? 'student');
        $isStaff       = ($userRole === 'admin' || $userRole === 'librarian');

        $searchText = trim($_GET['search'] ?? '');
        $sortBy     = $_GET['sort'] ?? 'newest';

        $activeFilters = [
            'id'       => isset($_GET['f_id']),
            'title'    => isset($_GET['f_title']),
            'username' => isset($_GET['f_username']) && $isStaff // Restrict to admin queries
        ];

        if (!array_filter($activeFilters)) {
            $activeFilters['id'] = true;
            $activeFilters['title'] = true;
        }

        // Let the model filter the dataset dynamically based on account tier clearances
        $transactions = $this->transactionModel->getLogHistory(
            $currentUserId,
            $isStaff,
            $searchText,
            $activeFilters,
            $sortBy
        );

        require_once __DIR__ . '/../Views/transactions/index.php';
    }
}
