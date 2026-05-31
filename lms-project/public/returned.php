<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user isn't authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Book Returned Confirmation";
include_once 'includes/header.php';

/* ==========================================================================
    DYNAMIC DATA SOURCE ENGINE (RETURNS & FINES LOGIC)
   ========================================================================== */
$transaction_id_input = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);

// Default placeholder details array matching the layout verbatim
$details = [
    'transaction_id' => $transaction_id_input ?: "TXN-" . strtoupper(bin2hex(random_bytes(4))),
    'book_title'     => "Introduction to Computer Science",
    'book_id'        => "BK-9021",
    'user_name'      => $_SESSION['username'] ?? "John Doe",
    'user_id'        => "STU-0422",
    'borrow_date'    => date('F d, Y', strtotime('-7 days')),
    'due_date'       => date('F d, Y', strtotime('-2 days')),
    'return_date'    => date('F d, Y'),
    'fines'          => "PHP 0.00",
    'cover_image'    => null
];

// If transaction query exists, pull the updated return log records
if (!empty($transaction_id_input)) {
    $host = 'localhost';
    $db   = 'lms_project'; // Aligned to real database schema
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Query extraction adjusted for the lms_project schema
        $query = "SELECT t.id AS transaction_id, b.title AS book_title, b.isbn AS book_id,
                         b.cover_image, br.name AS user_name, br.student_id AS user_id,
                         t.borrow_date, t.due_date, t.return_date, t.fine AS fines_accrued
                  FROM transactions t
                  JOIN books b ON t.book_id = b.id
                  JOIN borrowers br ON t.borrower_id = br.id
                  WHERE t.id = :id LIMIT 1";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $transaction_id_input]);
        $db_data = $stmt->fetch();

        if ($db_data) {
            $details = [
                'transaction_id' => 'TX-' . str_pad($db_data['transaction_id'], 5, '0', STR_PAD_LEFT),
                'book_title'     => $db_data['book_title'],
                'book_id'        => $db_data['book_id'],
                'user_name'      => $db_data['user_name'],
                'user_id'        => $db_data['user_id'],
                'borrow_date'    => date('F d, Y', strtotime($db_data['borrow_date'])),
                'due_date'       => date('F d, Y', strtotime($db_data['due_date'])),
                'return_date'    => (!empty($db_data['return_date']) && $db_data['return_date'] !== '0000-00-00')
                                    ? date('F d, Y', strtotime($db_data['return_date']))
                                    : date('F d, Y'),
                'fines'          => "PHP " . number_format((float)($db_data['fines_accrued'] ?? 0.00), 2),
                'cover_image'    => !empty($db_data['cover_image']) ? $db_data['cover_image'] : null
            ];
        }
    } catch (\PDOException $e) {
        // Fall back gracefully onto default mock placeholder values if database throws an exception
    }
}
?>

<main class="content-container return-success-canvas">
    <div class="return-success-inner-wrapper text-center">

        <div class="return-graphic-box">
            <?php if (!empty($details['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($details['cover_image']); ?>"
                     alt="Cover image for <?php echo htmlspecialchars($details['book_title']); ?>"
                     class="returned-book-cover-fluid">
            <?php else: ?>
                <div class="inner-book-vector">
                    <div class="vector-circle"></div>
                    <div class="vector-divider"></div>
                </div>
            <?php endif; ?>
        </div>

        <h1 class="return-success-title">BOOK RETURNED!</h1>

        <p class="return-advisory-text">
            Be sure to return your book at the library immediately<br>
            to avoid penalty fines.
        </p>

        <div class="return-meta-data-block">
            <p>Transaction ID: <span class="data-node"><?php echo htmlspecialchars($details['transaction_id']); ?></span></p>
            <p>Book: <span class="data-node"><?php echo htmlspecialchars($details['book_title']); ?> (<?php echo htmlspecialchars($details['book_id']); ?>)</span></p>
            <p>Borrower: <span class="data-node"><?php echo htmlspecialchars($details['user_name']); ?> (<?php echo htmlspecialchars($details['user_id']); ?>)</span></p>
            <p>Borrow Date: <span class="data-node"><?php echo htmlspecialchars($details['borrow_date']); ?></span></p>
            <p>Due Date: <span class="data-node"><?php echo htmlspecialchars($details['due_date']); ?></span></p>
            <p>Return Date: <span class="data-node"><?php echo htmlspecialchars($details['return_date']); ?></span></p>
            <p>Fines: <span class="data-node"><?php echo htmlspecialchars($details['fines']); ?></span></p>
        </div>

        <div class="return-action-link-container">
            <a href="transactions.php" class="return-continue-link">Continue</a>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
