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

$page_title = "Fine Records";
include_once 'includes/header.php';

// Capture role parameters to toggle appropriate interfaces
$user_id   = $_SESSION['user_id'];
$user_role = strtolower($_SESSION['role'] ?? 'student');
$is_staff  = ($user_role === 'admin' || $user_role === 'librarian');

// Database credentials setup
$host = 'localhost';
$db   = 'lms_project'; // Aligned to live database schema
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Setup interface filter metrics
$search_query   = trim($_GET['search'] ?? '');
$sort_selection = $_GET['sort'] ?? 'newest';

$filter_id       = isset($_GET['f_id']);
$filter_username = isset($_GET['f_username']) && $is_staff; // Restricted filter metric

// If no active filters are designated, initialize defaults matching mockup definitions
if (!$filter_id && !$filter_username) {
    $filter_id = true;
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
        POST HANDLER: FINE RESOLUTION SYSTEM (WITH OWNER VERIFICATION)
       ========================================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_settle_fine'])) {
        $target_fine_id = (int)$_POST['fine_id'];

        // Secure join query checking fine existence and ownership limits
        $check_fine_sql = "SELECT f.*, br.user_id
                           FROM fines f
                           JOIN transactions t ON f.transaction_id = t.id
                           JOIN borrowers br ON t.borrower_id = br.id
                           WHERE f.id = :fine_id LIMIT 1";

        $chk_stmt = $pdo->prepare($check_fine_sql);
        $chk_stmt->execute(['fine_id' => $target_fine_id]);
        $fine_record = $chk_stmt->fetch();

        if ($fine_record) {
            // Privacy Protection Boundary: Ensure user is staff or owns this specific fine record
            if (!$is_staff && (int)$fine_record['user_id'] !== (int)$user_id) {
                $error_msg = "Unauthorized operations blocked by system core rules.";
            } elseif ((int)$fine_record['paid'] === 1) {
                $error_msg = "This fee record has already been marked settled.";
            } else {
                // Update fine parameters mapping cleanly to the schema footprint
                $settle_sql = "UPDATE fines SET paid = 1, paid_date = :p_date WHERE id = :fine_id";
                $settle_stmt = $pdo->prepare($settle_sql);
                $settle_stmt->execute([
                    'p_date'  => date('Y-m-d'),
                    'fine_id' => $target_fine_id
                ]);
                $success_msg = "Fine settlement recorded successfully.";
            }
        } else {
            $error_msg = "Target ledger record item was not found.";
        }
    }

    /* ==========================================================================
        SQL BUILDER ENGINE: STRUCTURAL RELATIONAL JOIN EXTRACTION
       ========================================================================== */
    $select_fields = "f.id AS fine_id, f.transaction_id, f.amount, f.paid, f.paid_date,
                      u.username, u.id AS user_uuid";

    // Fixed join structure to safely map fines -> transactions -> borrowers -> users
    $query_sql = "SELECT $select_fields FROM fines f
                  JOIN transactions t ON f.transaction_id = t.id
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
        if ($filter_id) {
            $search_subconditions[] = "f.id LIKE :s_id";
            $query_params['s_id'] = "%$search_query%";
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
        $query_sql .= " ORDER BY f.id ASC";
    } else {
        $query_sql .= " ORDER BY f.id DESC"; // Default presentation order
    }

    $fetch_stmt = $pdo->prepare($query_sql);
    $fetch_stmt->execute($query_params);
    $fines_collection = $fetch_stmt->fetchAll();

} catch (\PDOException $e) {
    $error_msg = "Database Connection Infrastructure Error: " . $e->getMessage();
    $fines_collection = [];
}
?>

<main class="content-container fines-canvas">
    <div class="fines-inner-wrapper">

        <h1 class="fn-main-title text-center">FINE RECORDS</h1>

        <p class="fn-subtitle-notice text-center">
            <?php echo $is_staff ? "Enter a fine ID or username." : "Enter a fine ID."; ?>
        </p>

        <form action="fines.php" method="GET" class="fn-filtering-form-node">

            <div class="fn-search-input-field-row">
                <input type="text" name="search" class="fn-search-bar-input"
                       placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="fn-search-execution-trigger">
                    <svg class="fn-search-svg-icon" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div class="fn-control-refinement-row-deck">

                <div class="fn-select-facade-container">
                    <select name="sort" onchange="this.form.submit()" class="fn-native-refinement-select">
                        <option value="newest" <?php echo $sort_selection === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                        <option value="oldest" <?php echo $sort_selection === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                    </select>
                </div>

                <div class="fn-checkbox-filter-strip-row">
                    <label class="fn-custom-checkbox-node">
                        <input type="checkbox" name="f_id" value="1" <?php echo $filter_id ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="fn-checkbox-box-graphic"></span> ID
                    </label>

                    <?php if ($is_staff): ?>
                        <label class="fn-custom-checkbox-node">
                            <input type="checkbox" name="f_username" value="1" <?php echo $filter_username ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="fn-checkbox-box-graphic"></span> Username
                        </label>
                    <?php endif; ?>
                </div>

            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="fn-system-status-toast fn-status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="fn-system-status-toast fn-status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="fn-display-list-vertical-stack">
            <?php if (!empty($fines_collection)): ?>
                <?php foreach ($fines_collection as $row): ?>

                    <div class="fn-material-card-wrapper">

                        <div class="fn-graphic-frame-slate flex-shrink-0">
                            <div class="fn-inner-currency-vector">
                                <div class="fn-vector-circle outer-ring"></div>
                                <div class="fn-vector-circle inner-ring"></div>
                            </div>
                        </div>

                        <div class="fn-metadata-lavender-block">
                            <div class="fn-metadata-rows-stack-left">
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Fine ID:</span>
                                    <span class="fn-meta-data-val">FN-<?php echo str_pad($row['fine_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Transaction ID:</span>
                                    <span class="fn-meta-data-val">TX-<?php echo str_pad($row['transaction_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Borrower:</span>
                                    <span class="fn-meta-data-val font-prominent"><?php echo htmlspecialchars($row['username']); ?> (User ID: <?php echo $row['user_uuid']; ?>)</span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Amount:</span>
                                    <span class="fn-meta-data-val">PHP <?php echo number_format($row['amount'], 2); ?></span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Paid:</span>
                                    <span class="fn-meta-data-val">
                                        <?php echo ((int)$row['paid'] === 1) ? 'Yes' : '<span class="fn-outstanding-tag">No (Pending Settle)</span>'; ?>
                                    </span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Paid Date:</span>
                                    <span class="fn-meta-data-val">
                                        <?php echo ($row['paid_date'] !== null) ? date('m/d/Y', strtotime($row['paid_date'])) : 'TBA'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="fn-metadata-action-box-right">
                                <?php if (((int)$row['paid'] === 0)): ?>
                                    <form action="fines.php" method="POST" onsubmit="return confirm('Are you certain that you have paid the fines? Any false positives may have further penalties!');">
                                        <input type="hidden" name="fine_id" value="<?php echo $row['fine_id']; ?>">
                                        <button type="submit" name="action_settle_fine" value="1" class="fn-card-action-context-node">Paid</button>
                                    </form>
                                <?php else: ?>
                                    <div class="fn-completed-stamp-badge">Cleared</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="fn-empty-results-fallback-card text-center">
                    <p>No penalty records matching filter coordinates were located inside the system index.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
