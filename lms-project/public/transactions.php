<?php
// Initialize session checking and authentication protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control Wall: Enforce login requirement for page visibility
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Transaction Records";
include_once 'includes/header.php';

// Capture role parameters to toggle appropriate interfaces
$user_id   = $_SESSION['user_id'];
$user_role = strtolower($_SESSION['role'] ?? 'student');
$is_staff  = ($user_role === 'admin' || $user_role === 'librarian');

// Database credentials setup
$host = 'localhost';
$db   = 'lms_project';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Setup interface filter metrics
$search_query   = trim($_GET['search'] ?? '');
$sort_selection = $_GET['sort'] ?? 'newest';

$filter_title    = isset($_GET['f_title']);
$filter_author   = isset($_GET['f_author']);
$filter_username = isset($_GET['f_username']) && $is_staff; // Restricted filter metric

// If no active filters are designated, initialize defaults matching mockup definitions
if (!$filter_title && !$filter_author && !$filter_username) {
    $filter_title = $filter_author = true;
    if ($is_staff) {
        $filter_username = true;
    }
}

$success_msg = "";
$error_msg = "";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    /* ==========================================================================
        POST HANDLER: ITEM RETURN ACTION DISPATCHER
       ========================================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_return_book'])) {
        $target_tx_id = (int)$_POST['transaction_id'];

        // Verify record existence before updating table attributes - map through borrowers to verify user identity
        $check_tx_sql = "SELECT t.*, b.id AS book_table_id, br.user_id AS tx_user_id
                         FROM transactions t
                         JOIN books b ON t.book_id = b.id
                         JOIN borrowers br ON t.borrower_id = br.id
                         WHERE t.id = :tx_id";
        $check_stmt = $pdo->prepare($check_tx_sql);
        $check_stmt->execute(['tx_id' => $target_tx_id]);
        $tx_record = $check_stmt->fetch();

        if ($tx_record) {
            // Guardrail protection: Restrict non-staff accounts from manipulating other files
            // Robust check: evaluate if book has already been returned (not null and not 0000-00-00)
            $is_already_returned = !empty($tx_record['return_date']) && $tx_record['return_date'] !== '0000-00-00';

            if (!$is_staff && $tx_record['tx_user_id'] != $user_id) {
                $error_msg = "Unauthorized operations blocked by system core rules.";
            } elseif ($is_already_returned) {
                $error_msg = "This item has already been checked into the library system inventory.";
            } else {
                // Initialize atomic adjustments
                $pdo->beginTransaction();

                $current_date = date('Y-m-d');
                $computed_fines = 0.00;

                // Calculate outstanding overdue fees if checking in past the due date
                if (strtotime($current_date) > strtotime($tx_record['due_date'])) {
                    $overdue_seconds = strtotime($current_date) - strtotime($tx_record['due_date']);
                    $overdue_days = ceil($overdue_seconds / (60 * 60 * 24));
                    $computed_fines = $overdue_days * 5.00; // Flat-rate charge configuration
                }

                // Settle processing status attributes inside transactions log
                $update_tx_sql = "UPDATE transactions SET return_date = :ret_date, fine = :fine WHERE id = :tx_id";
                $up_tx_stmt = $pdo->prepare($update_tx_sql);
                $up_tx_stmt->execute([
                    'ret_date' => $current_date,
                    'fine'     => $computed_fines,
                    'tx_id'    => $target_tx_id
                ]);

                // If fees accrued, register an open logging transaction invoice item inside fines index directory
                if ($computed_fines > 0) {
                    $insert_fine_sql = "INSERT INTO fines (transaction_id, amount, paid) VALUES (:tx_id, :amount, 0)";
                    $ins_fine_stmt = $pdo->prepare($insert_fine_sql);
                    $ins_fine_stmt->execute([
                        'tx_id'  => $target_tx_id,
                        'amount' => $computed_fines
                    ]);
                }

                // Return item back to general inventory tracking tables
                $update_inventory_sql = "UPDATE books SET copies = copies + 1 WHERE id = :bk_id";
                $up_inv_stmt = $pdo->prepare($update_inventory_sql);
                $up_inv_stmt->execute(['bk_id' => $tx_record['book_table_id']]);

                $pdo->commit();

                // Clear output buffers and redirect immediately to the success page
                header("Location: returned.php?transaction_id=" . $target_tx_id);
                exit();
            }
        } else {
            $error_msg = "Target ledger record item was not found.";
        }
    }

    /* ==========================================================================
        SQL BUILDER ENGINE: DATA FETCH WITH DYNAMIC ROLE FILTERS
       ========================================================================== */
    $select_fields = "t.id AS tx_id, t.borrow_date, t.due_date, t.return_date, t.fine AS fines,
                      b.title AS book_title, b.author AS book_author, b.id AS book_uuid, b.cover_image,
                      u.username, u.id AS user_uuid";

    // Bridge through borrowers table to link users context properly
    $query_sql = "SELECT $select_fields FROM transactions t
                  JOIN books b ON t.book_id = b.id
                  JOIN borrowers br ON t.borrower_id = br.id
                  JOIN users u ON br.user_id = u.id";

    $where_clauses = [];
    $query_params = [];

    // Privacy boundary protection: Force current session restrictions for student profile accounts
    if (!$is_staff) {
        $where_clauses[] = "br.user_id = :session_user_id";
        $query_params['session_user_id'] = $user_id;
    }

    // Process text searches using specified field parameters
    if (!empty($search_query)) {
        $search_subconditions = [];
        if ($filter_title) {
            $search_subconditions[] = "b.title LIKE :s_title";
            $query_params['s_title'] = "%$search_query%";
        }
        if ($filter_author) {
            $search_subconditions[] = "b.author LIKE :s_author";
            $query_params['s_author'] = "%$search_query%";
        }
        if ($filter_username && $is_staff) {
            $search_subconditions[] = "u.username LIKE :s_user";
            $query_params['s_user'] = "%$search_query%";
        }

        if (!empty($search_subconditions)) {
            $where_clauses[] = "(" . implode(" OR ", $search_subconditions) . ")";
        }
    }

    if (!empty($where_clauses)) {
        $query_sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // Apply selected system sorting parameters
    if ($sort_selection === 'oldest') {
        $query_sql .= " ORDER BY t.id ASC";
    } else {
        $query_sql .= " ORDER BY t.id DESC"; // Default presentation order
    }

    $fetch_stmt = $pdo->prepare($query_sql);
    $fetch_stmt->execute($query_params);
    $transactions_collection = $fetch_stmt->fetchAll();

} catch (\PDOException $e) {
    $error_msg = "Database Connection Infrastructure Error: " . $e->getMessage();
    $transactions_collection = [];
}
?>

<style>
    /* Injected layout adjustment to preserve aspect ratios safely within frame blocks */
    .tx-graphic-frame-coral { overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #fce8e6; }
    .tx-book-cover-img { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>

<main class="content-container transactions-canvas">
    <div class="transactions-inner-wrapper">

        <h1 class="tx-main-title text-center">TRANSACTION RECORDS</h1>

        <p class="tx-subtitle-notice text-center">
            <?php echo $is_staff ? "Enter a title, author, ISBN, or username." : "Enter a title, author, or ISBN."; ?>
        </p>

        <form action="transactions.php" method="GET" class="tx-filtering-form-node">

            <div class="tx-search-input-field-row">
                <input type="text" name="search" class="tx-search-bar-input"
                       placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="tx-search-execution-trigger">
                    <svg class="usr-search-svg-icon" viewBox="0 0 24 24" style="width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:2;">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div class="tx-control-refinement-row-deck">

                <div class="tx-select-facade-container">
                    <select name="sort" onchange="this.form.submit()" class="tx-native-refinement-select">
                        <option value="newest" <?php echo $sort_selection === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                        <option value="oldest" <?php echo $sort_selection === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                    </select>
                </div>

                <div class="tx-checkbox-filter-strip-row">
                    <label class="tx-custom-checkbox-node">
                        <input type="checkbox" name="f_title" value="1" <?php echo $filter_title ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="tx-checkbox-box-graphic"></span> Title
                    </label>
                    <label class="tx-custom-checkbox-node">
                        <input type="checkbox" name="f_author" value="1" <?php echo $filter_author ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="tx-checkbox-box-graphic"></span> Author
                    </label>

                    <?php if ($is_staff): ?>
                        <label class="tx-custom-checkbox-node">
                            <input type="checkbox" name="f_username" value="1" <?php echo $filter_username ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="tx-checkbox-box-graphic"></span> Username
                        </label>
                    <?php endif; ?>
                </div>

                <div class="tx-export-action-group">
                    <a href="export.php" class="tx-export-data-trigger btn-csv" title="Download Excel CSV Document">
                        <svg viewBox="0 0 24 24" class="export-svg-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Export CSV</span>
                    </a>
                    <a href="export_pdf.php" target="_blank" class="tx-export-data-trigger btn-pdf" title="Generate Report PDF">
                        <svg viewBox="0 0 24 24" class="export-svg-icon"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        <span>Export PDF</span>
                    </a>
                </div>

            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="tx-system-status-toast tx-status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="tx-system-status-toast tx-status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="tx-display-list-vertical-stack">
            <?php if (!empty($transactions_collection)): ?>
                <?php foreach ($transactions_collection as $row): ?>

                    <div class="tx-material-card-wrapper">

                        <div class="tx-graphic-frame-coral flex-shrink-0">
                            <?php if (!empty($row['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['cover_image']); ?>"
                                     alt="<?php echo htmlspecialchars($row['book_title']); ?> Cover"
                                     class="tx-book-cover-img">
                            <?php else: ?>
                                <div class="tx-inner-document-vector">
                                    <div class="tx-vector-line wide"></div>
                                    <div class="tx-vector-line wide"></div>
                                    <div class="tx-vector-line short"></div>
                                    <div class="tx-vector-line short"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tx-metadata-lavender-block">
                            <div class="tx-metadata-rows-stack-left">
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Transaction ID:</span>
                                    <span class="tx-meta-data-val">TX-<?php echo str_pad($row['tx_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Book:</span>
                                    <span class="tx-meta-data-val font-prominent"><?php echo htmlspecialchars($row['book_title']); ?> (ID: <?php echo $row['book_uuid']; ?>)</span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Borrower:</span>
                                    <span class="tx-meta-data-val"><?php echo htmlspecialchars($row['username']); ?> (User ID: <?php echo $row['user_uuid']; ?>)</span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Borrow Date:</span>
                                    <span class="tx-meta-data-val"><?php echo date('m/d/Y', strtotime($row['borrow_date'])); ?></span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Due Date:</span>
                                    <span class="tx-meta-data-val"><?php echo date('m/d/Y', strtotime($row['due_date'])); ?></span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Return Date:</span>
                                    <span class="tx-meta-data-val">
                                        <?php
                                        if (!empty($row['return_date']) && $row['return_date'] !== '0000-00-00') {
                                            echo date('m/d/Y', strtotime($row['return_date'])) . " (Fines: PHP " . number_format($row['fines'], 2) . ")";
                                        } else {
                                            echo '<span class="tx-pending-tag">Active Outbound Loan</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="tx-metadata-action-box-right">
                                <?php if (empty($row['return_date']) || $row['return_date'] === '0000-00-00'): ?>
                                    <form action="transactions.php" method="POST" onsubmit="return confirm('Confirm processing check-in for this material return sequence?');">
                                        <input type="hidden" name="transaction_id" value="<?php echo $row['tx_id']; ?>">
                                        <button type="submit" name="action_return_book" value="1" class="tx-card-action-context-node">Return</button>
                                    </form>
                                <?php else: ?>
                                    <div class="tx-completed-stamp-badge">Settled</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="tx-empty-results-fallback-card text-center">
                    <p>No active database transaction interactions matching filter coordinates were found.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
