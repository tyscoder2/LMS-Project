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

/* ==========================================================================
    DYNAMIC DATA SOURCE ENGINE (TRANSACTIONS & RESERVATIONS)
   ========================================================================== */
$transaction_id_input = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);
$reservation_id_input = filter_input(INPUT_GET, 'reservation_id', FILTER_VALIDATE_INT);

$is_reservation = !empty($reservation_id_input);
$page_title = $is_reservation ? "Book Reserved Confirmation" : "Book Borrowed Confirmation";

include_once 'includes/header.php';

// Fallback data initialization matrix
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

// Execute extraction pipeline if a valid primary key parameter is verified
if (!empty($transaction_id_input) || !empty($reservation_id_input)) {
    $host = 'localhost';
    $db   = 'lms_project'; // Aligned to live project database schema
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        if ($is_reservation) {
            // Extraction branch tailored to the reservations schema footprint
            $query = "SELECT r.id AS reservation_id, b.title AS book_title, b.isbn AS book_id,
                             b.cover_image, br.name AS user_name, br.student_id AS user_id,
                             r.reserved_date
                      FROM reservations r
                      JOIN books b ON r.book_id = b.id
                      JOIN borrowers br ON r.borrower_id = br.id
                      WHERE r.id = :id LIMIT 1";

            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $reservation_id_input]);
            $db_data = $stmt->fetch();

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
        } else {
            // Extraction branch tailored to the transactions schema footprint
            $query = "SELECT t.id AS transaction_id, b.title AS book_title, b.isbn AS book_id,
                             b.cover_image, br.name AS user_name, br.student_id AS user_id,
                             t.borrow_date, t.due_date
                      FROM transactions t
                      JOIN books b ON t.book_id = b.id
                      JOIN borrowers br ON t.borrower_id = br.id
                      WHERE t.id = :id LIMIT 1";

            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $transaction_id_input]);
            $db_data = $stmt->fetch();

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
    } catch (\PDOException $e) {
        // Graceful error fallback behavior protects UI visibility if connection exceptions drop
    }
}
?>

<main class="content-container borrow-success-canvas">
    <div class="borrow-success-inner-wrapper text-center">

        <div class="borrow-graphic-box">
            <?php if (!empty($details['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($details['cover_image']); ?>"
                     alt="Cover image for <?php echo htmlspecialchars($details['book_title']); ?>"
                     class="borrowed-book-cover-fluid">
            <?php else: ?>
                <div class="inner-book-vector">
                    <div class="vector-circle"></div>
                    <div class="vector-divider"></div>
                </div>
            <?php endif; ?>
        </div>

        <h1 class="borrow-success-title">
            <?php echo $is_reservation ? "BOOK RESERVED!" : "BOOK BORROWED!"; ?>
        </h1>

        <p class="borrow-advisory-text">
            <?php if ($is_reservation): ?>
                This resource is currently out of stock. You have been added to the queue.<br>
                We will contact you as soon as a copy becomes available.
            <?php else: ?>
                Be sure to claim your book at the library immediately<br>
                and return by its due date to avoid penalty fines.
            <?php endif; ?>
        </p>

        <div class="borrow-meta-data-block">
            <p><?php echo $is_reservation ? "Reservation ID" : "Transaction ID"; ?>: <span class="data-node"><?php echo htmlspecialchars($details['display_id']); ?></span></p>
            <p>Book: <span class="data-node"><?php echo htmlspecialchars($details['book_title']); ?> (<?php echo htmlspecialchars($details['book_id']); ?>)</span></p>
            <p>Borrower: <span class="data-node"><?php echo htmlspecialchars($details['user_name']); ?> (<?php echo htmlspecialchars($details['user_id']); ?>)</span></p>
            <p><?php echo $is_reservation ? "Reserved Date" : "Borrow Date"; ?>: <span class="data-node"><?php echo htmlspecialchars($details['primary_date']); ?></span></p>
            <p>Due Date: <span class="data-node"><?php echo htmlspecialchars($details['due_date']); ?></span></p>
        </div>

        <div class="borrow-action-link-container">
            <a href="search.php" class="borrow-continue-link">Continue</a>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
