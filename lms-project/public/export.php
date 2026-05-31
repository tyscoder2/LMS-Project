<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role = strtolower($_SESSION['role'] ?? 'student');
$is_staff  = ($user_role === 'admin' || $user_role === 'librarian');

// Database credentials setup
$host = 'localhost';
$db   = 'lms_project';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    /* ==========================================================================
       METRICS CALCULATION QUERIES
       ========================================================================== */

    // 1. Transaction Counts
    $tx_count_sql = "SELECT COUNT(*) FROM transactions t JOIN borrowers br ON t.borrower_id = br.id";
    if (!$is_staff) { $tx_count_sql .= " WHERE br.user_id = :uid"; }
    $stmt = $pdo->prepare($tx_count_sql);
    $is_staff ? $stmt->execute() : $stmt->execute(['uid' => $user_id]);
    $total_transactions = $stmt->fetchColumn();

    // 2. Fines Metrics
    $fine_sql = "SELECT IFNULL(SUM(t.fine), 0) FROM transactions t JOIN borrowers br ON t.borrower_id = br.id";
    if (!$is_staff) { $fine_sql .= " WHERE br.user_id = :uid"; }
    $stmt = $pdo->prepare($fine_sql);
    $is_staff ? $stmt->execute() : $stmt->execute(['uid' => $user_id]);
    $total_fines = $stmt->fetchColumn();

    // 3. Books Metric
    $books_sql = "SELECT IFNULL(SUM(copies), 0) FROM books";
    $total_books = $pdo->query($books_sql)->fetchColumn();

    // 4. Reservations Metric
    $res_sql = "SELECT COUNT(*) FROM reservations r JOIN borrowers br ON r.borrower_id = br.id";
    if (!$is_staff) { $res_sql .= " WHERE br.user_id = :uid"; }
    $stmt = $pdo->prepare($res_sql);
    $is_staff ? $stmt->execute() : $stmt->execute(['uid' => $user_id]);
    $total_reservations = $stmt->fetchColumn();

    /* ==========================================================================
       INITIALIZE STREAM STREAM ATTACHMENT
       ========================================================================== */
    $filename = "LMS_Report_" . date('Y-m-d_Hi') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM injection sequence to enforce clear rendering across MS Excel instances
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Form Header Title Meta
    fputcsv($output, ["MMC LIBRARY MANAGEMENT SYSTEM REPORT"]);
    fputcsv($output, ["Generated:", date('Y-m-d H:i:s'), "Scope Configuration:", $is_staff ? 'Global Administrative Data' : 'Personal Student Ledger']);
    fputcsv($output, []); // Empty spacing separator line

    /* ==========================================================================
       SECTION A: CORE KPI SUMMARY STATISTICS
       ========================================================================== */
    fputcsv($output, ["---- SUMMARY METRICS ----"]);
    fputcsv($output, ["Metric Definition", "System Value"]);
    fputcsv($output, ["Total Transactions Logged", $total_transactions]);
    fputcsv($output, ["Total Borrows Registered", $total_transactions]); // Direct transaction direct correlation mappings
    fputcsv($output, ["Outstanding Fine Ledger Balance", "PHP " . number_format($total_fines, 2)]);
    fputcsv($output, ["Total System Inventory Volume (Physical Copies)", $total_books]);
    fputcsv($output, ["Total Reservations Recorded", $total_reservations]);

    if ($is_staff) {
        // Collect extra administrative vectors
        $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        fputcsv($output, ["Total Registered Accounts", $users_count]);
        fputcsv($output, []);

        /* ==========================================================================
           SECTION B: STAFF EXCLUSIVE INSIGHTS (TOP 5 DEEP-DIVES)
           ========================================================================== */
        // Top 5 Books
        fputcsv($output, ["---- TOP 5 MOST BORROWED BOOKS ----"]);
        fputcsv($output, ["Rank", "Book Title", "Author Name", "Borrow Frequency Count"]);
        $top_books_sql = "SELECT b.title, b.author, COUNT(t.id) as borrow_count
                          FROM transactions t JOIN books b ON t.book_id = b.id
                          GROUP BY b.id ORDER BY borrow_count DESC LIMIT 5";
        $rank = 1;
        foreach ($pdo->query($top_books_sql) as $b_row) {
            fputcsv($output, [$rank++, $b_row['title'], $b_row['author'], $b_row['borrow_count']]);
        }
        fputcsv($output, []);

        // Top 5 Active Users
        fputcsv($output, ["---- TOP 5 MOST ACTIVE BORROWERS ----"]);
        fputcsv($output, ["Rank", "Account Username", "Borrower Persona Full Name", "Total Activity Transactions"]);
        $top_users_sql = "SELECT u.username, br.name, COUNT(t.id) as tx_count
                          FROM transactions t
                          JOIN borrowers br ON t.borrower_id = br.id
                          JOIN users u ON br.user_id = u.id
                          GROUP BY u.id ORDER BY tx_count DESC LIMIT 5";
        $rank = 1;
        foreach ($pdo->query($top_users_sql) as $u_row) {
            fputcsv($output, [$rank++, $u_row['username'], $u_row['name'], $u_row['tx_count']]);
        }
        fputcsv($output, []);

        // Comprehensive Catalogue Matrix Breakdown
        fputcsv($output, ["---- GENERAL CATALOGUE RUNNING TRACKER ----"]);
        fputcsv($output, ["Book Material Title", "Author Reference", "International Standard Book Number (ISBN)", "Total System Borrows Tracker"]);
        $catalog_sql = "SELECT b.title, b.author, b.isbn, COUNT(t.id) as borrow_count
                        FROM books b LEFT JOIN transactions t ON b.id = t.book_id
                        GROUP BY b.id ORDER BY borrow_count DESC";
        foreach ($pdo->query($catalog_sql) as $c_row) {
            fputcsv($output, [$c_row['title'], $c_row['author'], $c_row['isbn'], $c_row['borrow_count']]);
        }
        fputcsv($output, []);
    } else {
        fputcsv($output, []);
    }

    /* ==========================================================================
       SECTION C: DETAILED RAW RECORDS LOG LISTING
       ========================================================================== */
    fputcsv($output, ["---- DETAILED TRANSACTION LOG INDEX ----"]);
    fputcsv($output, ["Transaction Unique ID", "Book Material Title", "Borrower Username", "Authorized Borrow Date", "System Target Due Date", "System Confirmed Check-In Return Date", "Assessed Item Fine Value"]);

    $records_sql = "SELECT t.id, b.title, u.username, t.borrow_date, t.due_date, t.return_date, t.fine
                    FROM transactions t
                    JOIN books b ON t.book_id = b.id
                    JOIN borrowers br ON t.borrower_id = br.id
                    JOIN users u ON br.user_id = u.id";
    if (!$is_staff) { $records_sql .= " WHERE br.user_id = :uid"; }
    $records_sql .= " ORDER BY t.id DESC";

    $rec_stmt = $pdo->prepare($records_sql);
    $is_staff ? $rec_stmt->execute() : $rec_stmt->execute(['uid' => $user_id]);

    while ($row = $rec_stmt->fetch()) {
        $ret_dt = (!empty($row['return_date']) && $row['return_date'] !== '0000-00-00') ? $row['return_date'] : 'Active Outbound Loan';
        fputcsv($output, [
            "TX-" . str_pad($row['id'], 5, '0', STR_PAD_LEFT),
            $row['title'],
            $row['username'],
            $row['borrow_date'],
            $row['due_date'],
            $ret_dt,
            "PHP " . number_format($row['fine'], 2)
        ]);
    }

    fclose($output);
    exit();

} catch (\PDOException $e) {
    die("Data Export Infrastructure Fatal Drop Error: " . $e->getMessage());
}
