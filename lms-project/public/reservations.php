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

$page_title = "Book Reservation Records";
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

$filter_id       = isset($_GET['f_id']);
$filter_title    = isset($_GET['f_title']);
$filter_username = isset($_GET['f_username']) && $is_staff; // Restricted staff metric

// If no active filters are designated, initialize defaults matching mockup definitions
if (!$filter_id && !$filter_title && !$filter_username) {
    $filter_id = true;
    $filter_title = true;
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
        POST HANDLER: RESERVATION STATUS UPDATER (FULFILL / CANCEL)
       ========================================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action_fulfill']) || isset($_POST['action_cancel']))) {
        $target_res_id = (int)$_POST['reservation_id'];
        $target_status = isset($_POST['action_fulfill']) ? 'fulfilled' : 'cancelled';

        // Secure verification checking existence and ownership limits
        $check_res_sql = "SELECT r.*, br.user_id
                          FROM reservations r
                          JOIN borrowers br ON r.borrower_id = br.id
                          WHERE r.id = :res_id LIMIT 1";

        $chk_stmt = $pdo->prepare($check_res_sql);
        $chk_stmt->execute(['res_id' => $target_res_id]);
        $res_record = $chk_stmt->fetch();

        if ($res_record) {
            // Privacy Protection Boundary: Ensure user is staff or owns this specific record
            if (!$is_staff && (int)$res_record['user_id'] !== (int)$user_id) {
                $error_msg = "Unauthorized operations blocked by system core rules.";
            } elseif ($res_record['status'] !== 'pending') {
                $error_msg = "This reservation has already been finalized as " . $res_record['status'] . ".";
            } else {
                // Execute status modifications cleanly safely writing to live storage
                $update_sql = "UPDATE reservations SET status = :status WHERE id = :res_id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    'status' => $target_status,
                    'res_id' => $target_res_id
                ]);
                $success_msg = "Reservation successfully marked as " . ucfirst($target_status) . ".";
            }
        } else {
            $error_msg = "Target ledger reservation record was not found.";
        }
    }

    /* ==========================================================================
        SQL BUILDER ENGINE: STRUCTURAL RELATIONAL JOIN EXTRACTION
       ========================================================================== */
    $select_fields = "r.id AS reservation_id, r.reserved_date, r.status,
                      b.title AS book_title, b.author AS book_author, b.isbn,
                      u.username, u.id AS user_uuid, br.name AS borrower_name";

    // Fixed join structure mapping reservations -> books -> borrowers -> users
    $query_sql = "SELECT $select_fields FROM reservations r
                  JOIN books b ON r.book_id = b.id
                  JOIN borrowers br ON r.borrower_id = br.id
                  JOIN users u ON br.user_id = u.id";

    $where_clauses = [];
    $query_params = [];

    // Privacy boundary protection: Force current session restrictions for student profile accounts
    if (!$is_staff) {
        $where_clauses[] = "br.user_id = :session_user_id";
        $query_params['session_user_id'] = $user_id;
    }

    // Process text searches using specified filter criteria checkboxes
    if (!empty($search_query)) {
        $search_subconditions = [];
        if ($filter_id) {
            $search_subconditions[] = "r.id LIKE :s_id";
            $query_params['s_id'] = "%$search_query%";
        }
        if ($filter_title) {
            $search_subconditions[] = "b.title LIKE :s_title";
            $query_params['s_title'] = "%$search_query%";
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
        $query_sql .= " ORDER BY r.id ASC";
    } else {
        $query_sql .= " ORDER BY r.id DESC"; // Default presentation order
    }

    $fetch_stmt = $pdo->prepare($query_sql);
    $fetch_stmt->execute($query_params);
    $reservations_collection = $fetch_stmt->fetchAll();

} catch (\PDOException $e) {
    $error_msg = "Database Connection Infrastructure Error: " . $e->getMessage();
    $reservations_collection = [];
}
?>

<main class="content-container reservations-canvas">
    <div class="reservations-inner-wrapper">

        <h1 class="res-main-title text-center">RESERVATION RECORDS</h1>

        <p class="res-subtitle-notice text-center">
            <?php echo $is_staff ? "Enter a reservation ID, book title, or username." : "Search by reservation ID or book title."; ?>
        </p>

        <form action="reservations.php" method="GET" class="res-filtering-form-node">

            <div class="res-search-input-field-row">
                <input type="text" name="search" class="res-search-bar-input"
                       placeholder="Search reservations..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="res-search-execution-trigger">
                    <svg class="res-search-svg-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div class="res-control-refinement-row-deck">

                <div class="res-select-facade-container">
                    <select name="sort" onchange="this.form.submit()" class="res-native-refinement-select">
                        <option value="newest" <?php echo $sort_selection === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                        <option value="oldest" <?php echo $sort_selection === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                    </select>
                </div>

                <div class="res-checkbox-filter-strip-row">
                    <label class="res-custom-checkbox-node">
                        <input type="checkbox" name="f_id" value="1" <?php echo $filter_id ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="res-checkbox-box-graphic"></span> ID
                    </label>

                    <label class="res-custom-checkbox-node">
                        <input type="checkbox" name="f_title" value="1" <?php echo $filter_title ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="res-checkbox-box-graphic"></span> Title
                    </label>

                    <?php if ($is_staff): ?>
                        <label class="res-custom-checkbox-node">
                            <input type="checkbox" name="f_username" value="1" <?php echo $filter_username ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="res-checkbox-box-graphic"></span> Username
                        </label>
                    <?php endif; ?>
                </div>

            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="res-system-status-toast res-status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="res-system-status-toast res-status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="res-display-list-vertical-stack">
            <?php if (!empty($reservations_collection)): ?>
                <?php foreach ($reservations_collection as $row): ?>

                    <div class="res-material-card-wrapper status-<?php echo $row['status']; ?>">

                        <div class="res-graphic-frame-slate flex-shrink-0">
                            <div class="res-inner-book-vector">
                                <i class="fas fa-bookmark"></i>
                            </div>
                        </div>

                        <div class="res-metadata-lavender-block">
                            <div class="res-metadata-rows-stack-left">
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Reservation ID:</span>
                                    <span class="res-meta-data-val">RS-<?php echo str_pad($row['reservation_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Book Requested:</span>
                                    <span class="res-meta-data-val font-prominent"><?php echo htmlspecialchars($row['book_title']); ?> <small style="color:#666;">(ISBN: <?php echo htmlspecialchars($row['isbn']); ?>)</small></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Borrower:</span>
                                    <span class="res-meta-data-val"><?php echo htmlspecialchars($row['borrower_name']); ?> (<?php echo htmlspecialchars($row['username']); ?>)</span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Date Reserved:</span>
                                    <span class="res-meta-data-val"><?php echo date('m/d/Y', strtotime($row['reserved_date'])); ?></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Current Status:</span>
                                    <span class="res-meta-data-val">
                                        <span class="res-status-badge badge-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <div class="res-metadata-action-box-right">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <div class="res-action-button-group">
                                        <form action="reservations.php" method="POST" onsubmit="return confirm('Fulfill reservation and convert to active transaction output?');" style="display:inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                            <button type="submit" name="action_fulfill" value="1" class="res-btn-action res-btn-fulfill">Fulfill</button>
                                        </form>

                                        <form action="reservations.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?');" style="display:inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                            <button type="submit" name="action_cancel" value="1" class="res-btn-action res-btn-cancel">Cancel</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="res-completed-stamp-badge stamp-<?php echo $row['status']; ?>">
                                        <?php echo strtoupper($row['status']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="res-empty-results-fallback-card text-center">
                    <p>No book reservation tracking metrics matches your chosen parameters inside the index system ledger.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
