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

    // Data Engine Fetch Blocks
    $tx_count_sql = "SELECT COUNT(*) FROM transactions t JOIN borrowers br ON t.borrower_id = br.id";
    if (!$is_staff) { $tx_count_sql .= " WHERE br.user_id = :uid"; }
    $stmt = $pdo->prepare($tx_count_sql);
    $is_staff ? $stmt->execute() : $stmt->execute(['uid' => $user_id]);
    $total_transactions = $stmt->fetchColumn();

    $fine_sql = "SELECT IFNULL(SUM(t.fine), 0) FROM transactions t JOIN borrowers br ON t.borrower_id = br.id";
    if (!$is_staff) { $fine_sql .= " WHERE br.user_id = :uid"; }
    $stmt = $pdo->prepare($fine_sql);
    $is_staff ? $stmt->execute() : $stmt->execute(['uid' => $user_id]);
    $total_fines = $stmt->fetchColumn();

    $total_books = $pdo->query("SELECT IFNULL(SUM(copies), 0) FROM books")->fetchColumn();

    $res_sql = "SELECT COUNT(*) FROM reservations r JOIN borrowers br ON r.borrower_id = br.id";
    if (!$is_staff) { $res_sql .= " WHERE br.user_id = :uid"; }
    $stmt = $pdo->prepare($res_sql);
    $is_staff ? $stmt->execute() : $stmt->execute(['uid' => $user_id]);
    $total_reservations = $stmt->fetchColumn();

    if ($is_staff) {
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Top 5 Books
        $stmt = $pdo->query("SELECT b.title, b.author, COUNT(t.id) as b_count FROM transactions t JOIN books b ON t.book_id = b.id GROUP BY b.id ORDER BY b_count DESC LIMIT 5");
        $top_books = $stmt->fetchAll();

        // Top 5 Users
        $stmt = $pdo->query("SELECT u.username, br.name, COUNT(t.id) as tx_count FROM transactions t JOIN borrowers br ON t.borrower_id = br.id JOIN users u ON br.user_id = u.id GROUP BY u.id ORDER BY tx_count DESC LIMIT 5");
        $top_users = $stmt->fetchAll();

        // Full Catalogue Matrix
        $stmt = $pdo->query("SELECT b.title, b.author, b.isbn, COUNT(t.id) as b_count FROM books b LEFT JOIN transactions t ON b.id = t.book_id GROUP BY b.id ORDER BY b_count DESC");
        $catalog_distribution = $stmt->fetchAll();
    }

    // Detailed Raw Ledger Stream
    $records_sql = "SELECT t.id, b.title, u.username, t.borrow_date, t.due_date, t.return_date, t.fine
                    FROM transactions t JOIN books b ON t.book_id = b.id JOIN borrowers br ON t.borrower_id = br.id JOIN users u ON br.user_id = u.id";
    if (!$is_staff) { $records_sql .= " WHERE br.user_id = :uid"; }
    $records_sql .= " ORDER BY t.id DESC";

    $rec_stmt = $pdo->prepare($records_sql);
    $is_staff ? $rec_stmt->execute() : $rec_stmt->execute(['uid' => $user_id]);
    $raw_records = $rec_stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database Connection Failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Metrics Report - PDF Print Export</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #2c3e50; line-height: 1.4; margin: 0; padding: 40px; background: #fff; }
        .report-header { border-bottom: 2px solid #e74c3c; padding-bottom: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .report-header h1 { margin: 0; font-size: 24px; color: #e74c3c; font-weight: 700; letter-spacing: -0.5px; }
        .meta-tag { text-align: right; font-size: 12px; color: #7f8c8d; }
        h2 { font-size: 14px; color: #34495e; border-left: 3px solid #e74c3c; padding-left: 8px; margin: 30px 0 15px 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .kpi-card { border: 1px solid #e2e8f0; background: #f8fafc; padding: 15px; border-radius: 6px; box-sizing: border-box; }
        .kpi-card .label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 5px; }
        .kpi-card .val { font-size: 20px; font-weight: 700; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 12px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th { background: #f1f5f9; color: #475569; font-weight: 600; text-align: left; padding: 8px 10px; border: 1px solid #e2e8f0; }
        td { padding: 8px 10px; border: 1px solid #e2e8f0; color: #334155; }
        .text-right { text-align: right; }
        .badge-settled { color: #16a34a; font-weight: 600; }
        .badge-active { color: #d97706; font-weight: 600; }
        .page-break { page-break-before: always; }
        .print-btn-bar { padding: 12px; background: #f1f5f9; border-radius: 6px; margin-bottom: 25px; display: flex; gap: 10px; align-items: center; }
        .print-btn { background: #e74c3c; color: white; border: none; padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 4px; cursor: pointer; }
        @media print {
            body { padding: 0; }
            .print-btn-bar { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="print-btn-bar">
        <button onclick="window.print()" class="print-btn">Print / Save as PDF</button>
        <span style="font-size: 12px; color: #64748b;">Review output format guidelines. Choose <strong>"Save as PDF"</strong> inside your browser printer options layer.</span>
    </div>

    <div class="report-header">
        <div>
            <h1>LMS SYSTEM METRICS REPORT</h1>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Scope Parameters: <?php echo $is_staff ? 'Global Administrative Core Ledger' : 'Personal Account Summary'; ?></div>
        </div>
        <div class="meta-tag">
            <div><strong>Generated:</strong> <?php echo date('m/d/Y H:i'); ?></div>
            <div><strong>Status Profile:</strong> <?php echo htmlspecialchars(strtoupper($user_role)); ?></div>
        </div>
    </div>

    <h2>Core System Performance Indicators</h2>
    <div class="kpi-grid">
        <div class="kpi-card"><div class="label">Total Transactions</div><div class="val"><?php echo $total_transactions; ?></div></div>
        <div class="kpi-card"><div class="label">Total Borrows Logged</div><div class="val"><?php echo $total_transactions; ?></div></div>
        <div class="kpi-card"><div class="label">Outstanding Fines</div><div class="val">PHP <?php echo number_format($total_fines, 2); ?></div></div>
        <div class="kpi-card"><div class="label">Total System Inventory Volume</div><div class="val"><?php echo $total_books; ?> copies</div></div>
        <div class="kpi-card"><div class="label">Active Reservations</div><div class="val"><?php echo $total_reservations; ?></div></div>
        <?php if ($is_staff): ?>
            <div class="kpi-card"><div class="label">Registered Accounts</div><div class="val"><?php echo $total_users; ?></div></div>
        <?php endif; ?>
    </div>

    <?php if ($is_staff): ?>
        <div class="page-break"></div>
        <h2>Top 5 Most Borrowed Books</h2>
        <table>
            <thead><tr><th style="width: 60px;">Rank</th><th>Book Title</th><th>Author Reference Name</th><th class="text-right" style="width: 150px;">Borrow Count Log</th></tr></thead>
            <tbody>
                <?php $r = 1; foreach ($top_books as $b): ?>
                    <tr><td><?php echo $r++; ?></td><td><strong><?php echo htmlspecialchars($b['title']); ?></strong></td><td><?php echo htmlspecialchars($b['author']); ?></td><td class="text-right"><?php echo $b['b_count']; ?> times</td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Top 5 Most Active Borrowers</h2>
        <table>
            <thead><tr><th style="width: 60px;">Rank</th><th>Username</th><th>Full Name Metric</th><th class="text-right" style="width: 150px;">Total Transactions</th></tr></thead>
            <tbody>
                <?php $r = 1; foreach ($top_users as $u): ?>
                    <tr><td><?php echo $r++; ?></td><td><code><?php echo htmlspecialchars($u['username']); ?></code></td><td><?php echo htmlspecialchars($u['name']); ?></td><td class="text-right"><?php echo $u['tx_count']; ?> files</td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Books Distribution Volume Chart</h2>
        <table>
            <thead><tr><th>Book Title</th><th>Author</th><th>ISBN Reference Node</th><th class="text-right" style="width: 150px;">Total Borrows Tracking</th></tr></thead>
            <tbody>
                <?php foreach ($catalog_distribution as $c): ?>
                    <tr><td><?php echo htmlspecialchars($c['title']); ?></td><td><?php echo htmlspecialchars($c['author']); ?></td><td><code><?php echo htmlspecialchars($c['isbn']); ?></code></td><td class="text-right"><?php echo $c['b_count']; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="page-break"></div>
    <h2>Comprehensive Running Transactions Audit Ledger</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">TX ID</th>
                <th>Book Title</th>
                <th style="width: 100px;">Borrower</th>
                <th style="width: 90px;">Borrow Date</th>
                <th style="width: 90px;">Due Date</th>
                <th style="width: 110px;">Return Date Status</th>
                <th class="text-right" style="width: 80px;">Fine</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($raw_records as $row): ?>
                <tr>
                    <td><code>TX-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></code></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo date('m/d/Y', strtotime($row['borrow_date'])); ?></td>
                    <td><?php echo date('m/d/Y', strtotime($row['due_date'])); ?></td>
                    <td>
                        <?php
                        if (!empty($row['return_date']) && $row['return_date'] !== '0000-00-00') {
                            echo '<span class="badge-settled">' . date('m/d/Y', strtotime($row['return_date'])) . '</span>';
                        } else {
                            echo '<span class="badge-active">Active Outbound</span>';
                        }
                        ?>
                    </td>
                    <td class="text-right">PHP <?php echo number_format($row['fine'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Triggers the printing framework engine automatically on document presentation assembly load sequence
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => { window.print(); }, 500);
        });
    </script>
</body>
</html>
