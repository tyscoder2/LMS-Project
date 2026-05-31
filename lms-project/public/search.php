<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Books and Material Catalog";
include_once 'includes/header.php';

/* ==========================================================================
    DATABASE INTERFACE CONFIGURATION
   ========================================================================== */
$host = 'localhost';
$db   = 'lms_project';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$success_msg = "";
$error_msg = "";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    $pdo = null;
    $error_msg = "Database Pipeline Interruption: " . $e->getMessage();
}

// Track login state for JavaScript injection
$user_is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

/* ==========================================================================
    POST HANDLER: BORROW TRANSACTION & RESERVATION PIPELINE
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_action']) && $pdo !== null) {
    // Backend fallback verification
    if (!$user_is_logged_in) {
        $error_msg = "Authentication required: Please sign in to issue catalog requests.";
    } else {
        $action_type = $_POST['book_action'];
        $target_book_id = (int)($_POST['book_id'] ?? 0);
        $current_user_id = $_SESSION['user_id'];

        try {
            // Pull the borrower tracking profile tied to the user login session identity
            $borrower_stmt = $pdo->prepare("SELECT id FROM borrowers WHERE user_id = :uid");
            $borrower_stmt->execute(['uid' => $current_user_id]);
            $borrower_record = $borrower_stmt->fetch();

            if (!$borrower_record) {
                throw new \Exception("Your account does not have an active profile inside the system borrowers directory.");
            }

            $borrower_id = $borrower_record['id'];

            if ($action_type === 'borrow') {
                $pdo->beginTransaction();

                // Lock row for update to verify real-time inventory levels
                $lock_stmt = $pdo->prepare("SELECT copies FROM books WHERE id = :id FOR UPDATE");
                $lock_stmt->execute(['id' => $target_book_id]);
                $book_record = $lock_stmt->fetch();

                if ($book_record && $book_record['copies'] > 0) {
                    // 1. Decrement data inventory values by 1
                    $dec_stmt = $pdo->prepare("UPDATE books SET copies = copies - 1 WHERE id = :id");
                    $dec_stmt->execute(['id' => $target_book_id]);

                    // 2. Generate systematic transaction ledger record item
                    $tx_stmt = $pdo->prepare("INSERT INTO transactions (book_id, borrower_id, borrow_date, due_date) VALUES (:bid, :borr_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY))");
                    $tx_stmt->execute([
                        'bid' => $target_book_id,
                        'borr_id' => $borrower_id
                    ]);

                    $new_transaction_id = $pdo->lastInsertId();
                    $pdo->commit();

                    // Absolute routing transition to confirmation canvas
                    header("Location: borrowed.php?transaction_id=" . $new_transaction_id);
                    exit();
                } else {
                    $pdo->rollBack();
                    $error_msg = "No physical copies remaining. Please place a reservation instead.";
                }
            } elseif ($action_type === 'reserve') {
                // Create clean item entry trace inside reservations table
                $res_stmt = $pdo->prepare("INSERT INTO reservations (book_id, borrower_id, reserved_date, status) VALUES (:bid, :borr_id, CURDATE(), 'pending')");
                $res_stmt->execute([
                    'bid' => $target_book_id,
                    'borr_id' => $borrower_id
                ]);

                $new_reservation_id = $pdo->lastInsertId();

                // Absolute routing transition to confirmation canvas
                header("Location: borrowed.php?reservation_id=" . $new_reservation_id);
                exit();
            }
        } catch (\Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Process Error: " . $ex->getMessage();
        }
    }
}

/* ==========================================================================
    GET DATA PIPELINE: LIVE SEARCH FILTER MATRIX
   ========================================================================== */
$search_query   = trim($_GET['search'] ?? '');
$sort_selection = $_GET['sort'] ?? 'newest';

// Parse query filter elements
$filter_title    = isset($_GET['f_title']);
$filter_author   = isset($_GET['f_author']);
$filter_category = isset($_GET['f_category']);
$filter_keyword  = isset($_GET['f_keyword']);

// Default all categories to true if none are manually checked
if (!$filter_title && !$filter_author && !$filter_category && !$filter_keyword) {
    $filter_title = $filter_author = $filter_category = $filter_keyword = true;
}

$registered_books_collection = [];

if ($pdo !== null) {
    $query_parts = [];
    $query_params = [];

    if (!empty($search_query)) {
        $sub_conditions = [];
        if ($filter_title) { $sub_conditions[] = "books.title LIKE :s_title"; $query_params['s_title'] = "%$search_query%"; }
        if ($filter_author) { $sub_conditions[] = "books.author LIKE :s_author"; $query_params['s_author'] = "%$search_query%"; }
        if ($filter_category) { $sub_conditions[] = "categories.name LIKE :s_cat"; $query_params['s_cat'] = "%$search_query%"; }
        if ($filter_keyword) { $sub_conditions[] = "books.isbn LIKE :s_isbn"; $query_params['s_isbn'] = "%$search_query%"; }

        if (!empty($sub_conditions)) {
            $query_parts[] = "(" . implode(" OR ", $sub_conditions) . ")";
        }
    }

    $base_select_sql = "SELECT books.*, categories.name AS category_name
                        FROM books
                        LEFT JOIN categories ON books.category_id = categories.id";

    if (!empty($query_parts)) {
        $base_select_sql .= " WHERE " . implode(" AND ", $query_parts);
    }

    switch ($sort_selection) {
        case 'oldest': $base_select_sql .= " ORDER BY books.id ASC"; break;
        case 'name':   $base_select_sql .= " ORDER BY books.title ASC"; break;
        case 'newest':
        default:       $base_select_sql .= " ORDER BY books.id DESC"; break;
    }

    try {
        $fetch_stmt = $pdo->prepare($base_select_sql);
        foreach ($query_params as $param_key => $param_val) {
            $fetch_stmt->bindValue(':' . $param_key, $param_val);
        }
        $fetch_stmt->execute();
        $registered_books_collection = $fetch_stmt->fetchAll();
    } catch (\PDOException $ex) {
        $error_msg = "Query Retrieval Error: " . $ex->getMessage();
    }
}
?>

<style>
    .search-page-body { background-color: #ebdcd9; min-height: 100vh; padding-bottom: 60px; font-family: system-ui, -apple-system, sans-serif; }
    .catalog-centerpiece-container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
    .search-hero-title { font-size: 42px; font-weight: 800; color: #1a202c; letter-spacing: 0.5px; margin-bottom: 8px; }
    .search-hero-subtitle { font-size: 16px; color: #4a5568; margin-bottom: 35px; }

    /* Search Navigation Layout Bar */
    .search-interactive-filter-panel { width: 100%; margin-bottom: 30px; }
    .search-bar-input-wrapper { display: flex; background: #ffffff; border: 1px solid #b8b8b8; border-radius: 4px; overflow: hidden; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .search-bar-input-wrapper input { flex-grow: 1; border: none; padding: 12px 16px; font-size: 16px; outline: none; color: #2d3748; }
    .search-bar-input-wrapper button { border: none; background: #6b6b6b; color: white; padding: 0 20px; cursor: pointer; display: flex; align-items: center; transition: background 0.2s; }
    .search-bar-input-wrapper button:hover { background: #525252; }

    /* Refinement Strips */
    .search-refinement-horizontal-strip { display: flex; align-items: center; justify-content: flex-start; gap: 25px; flex-wrap: wrap; padding-left: 2px; }
    .sort-dropdown-facade select { background: #e2e8f0; border: 1px solid #a0aec0; border-radius: 6px; padding: 6px 32px 6px 12px; font-size: 15px; color: #2d3748; outline: none; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%234a5568' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px; }

    .mock-checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 15px; color: #2d3748; cursor: pointer; user-select: none; }
    .mock-checkbox-label input[type="checkbox"] { display: none; }
    .checkbox-custom-indicator { width: 18px; height: 18px; border: 2px solid #5a5a5a; background: #8b8b8b; border-radius: 2px; position: relative; transition: all 0.15s; }
    .mock-checkbox-label input[type="checkbox"]:checked + .checkbox-custom-indicator { background: #4a5568; border-color: #2d3748; }
    .mock-checkbox-label input[type="checkbox"]:checked + .checkbox-custom-indicator::after { content: ''; position: absolute; left: 4px; top: 1px; width: 5px; height: 9px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }

    /* Flex Row Grid Cards */
    .catalog-cards-vertical-stack { display: flex; flex-direction: column; gap: 20px; margin-top: 10px; }
    .search-result-card-row { display: flex; min-height: 180px; border-radius: 0px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07); }

    .card-left-visual-holder { width: 180px; background-color: #ebd59b; display: flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 10px; position: relative; }
    .fluid-cover-preview-img { width: 100%; height: 100%; object-fit: contain; }

    /* Document Vector Placeholder */
    .wireframe-book-graphic { width: 75px; height: 95px; border: 4px solid #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; position: relative; }
    .wireframe-circle { width: 24px; height: 24px; border: 4px solid #ffffff; border-radius: 50%; }
    .wireframe-line { width: 40px; height: 4px; background-color: #ffffff; }

    .card-right-details-panel { flex-grow: 1; background-color: #cbb2f4; padding: 15px 20px; display: flex; justify-content: space-between; align-items: flex-end; position: relative; }
    .book-metadata-list-stack { display: flex; flex-direction: column; gap: 4px; text-align: left; color: #000000; font-size: 15px; }
    .book-metadata-list-stack p { margin: 0; padding: 0; line-height: 1.4; }

    /* Layout Pill Control Buttons */
    .action-card-btn-classic { background-color: #aeaeae; color: #000000; border: 1px solid #595959; border-radius: 20px; padding: 6px 32px; font-size: 14px; font-weight: 500; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.2s; outline: none; }
    .action-card-btn-classic:hover { background-color: #9c9c9c; }

    /* Feedback Elements */
    .system-status-toast { width: 100%; padding: 12px 20px; margin-bottom: 20px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 15px; }
    .status-success { background-color: #c6f6d5; color: #22543d; }
    .status-error { background-color: #fed7d7; color: #742a2a; }
    .search-empty-state-notice { background: rgba(255,255,255,0.6); padding: 40px; border-radius: 8px; border: 2px dashed #cbd5e1; color: #4a5568; font-size: 16px; }
</style>

<div class="search-page-body">
    <main class="catalog-centerpiece-container">

        <section class="text-center">
            <h1 class="search-hero-title">BOOKS AND MATERIAL</h1>
            <p class="search-hero-subtitle">Enter a title, author, ISBN, or keyword description.</p>
        </section>

        <section class="search-interactive-filter-panel">
            <form action="search.php" method="GET">

                <div class="search-bar-input-wrapper">
                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">
                        <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin='round' stroke-width='2.5' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'></path></svg>
                    </button>
                </div>

                <div class="search-refinement-horizontal-strip">
                    <div class="sort-dropdown-facade">
                        <select name="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort_selection === 'newest' ? 'selected' : ''; ?>>Sort by</option>
                            <option value="oldest" <?php echo $sort_selection === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                            <option value="name" <?php echo $sort_selection === 'name' ? 'selected' : ''; ?>>Sort by: By name</option>
                        </select>
                    </div>

                    <label class="mock-checkbox-label">
                        <input type="checkbox" name="f_title" value="1" <?php echo $filter_title ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="checkbox-custom-indicator"></span> Title
                    </label>

                    <label class="mock-checkbox-label">
                        <input type="checkbox" name="f_author" value="1" <?php echo $filter_author ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="checkbox-custom-indicator"></span> Author
                    </label>

                    <label class="mock-checkbox-label">
                        <input type="checkbox" name="f_category" value="1" <?php echo $filter_category ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="checkbox-custom-indicator"></span> Category
                    </label>

                    <label class="mock-checkbox-label">
                        <input type="checkbox" name="f_keyword" value="1" <?php echo $filter_keyword ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="checkbox-custom-indicator"></span> Keyword
                    </label>
                </div>
            </form>
        </section>

        <?php if (!empty($success_msg)): ?>
            <div class="system-status-toast status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="system-status-toast status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <section class="catalog-cards-vertical-stack">
            <?php if (!empty($registered_books_collection)): ?>
                <?php foreach ($registered_books_collection as $book): ?>

                    <div class="search-result-card-row">

                        <div class="card-left-visual-holder">
                            <?php if (!empty($book['cover_image']) && file_exists($book['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover Image" class="fluid-cover-preview-img">
                            <?php else: ?>
                                <div class="wireframe-book-graphic">
                                    <div class="wireframe-circle"></div>
                                    <div class="wireframe-line"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-right-details-panel">
                            <div class="book-metadata-list-stack">
                                <p><strong>Title:</strong> <?php echo htmlspecialchars($book['title']); ?></p>
                                <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                                <p><strong>ISBN:</strong> <?php echo htmlspecialchars(!empty($book['isbn']) ? $book['isbn'] : 'N/A'); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category_name'] ?? 'Education'); ?></p>
                                <p><strong>Copies:</strong> <?php echo ($book['copies'] > 0) ? htmlspecialchars($book['copies']) : 'On loan'; ?></p>
                                <p><strong>Added:</strong> <?php echo isset($book['created_at']) ? date("Y-m-d", strtotime($book['created_at'])) : 'Date added'; ?></p>
                            </div>

                            <div>
                                <form method="POST" action="search.php?<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? ''); ?>" onsubmit="return handleCatalogActionDispatch(event)">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">

                                    <?php if ($book['copies'] > 0): ?>
                                        <button type="submit" name="book_action" value="borrow" class="action-card-btn-classic">Borrow</button>
                                    <?php else: ?>
                                        <button type="submit" name="book_action" value="reserve" class="action-card-btn-classic">Watch</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="search-empty-state-notice text-center">
                    <p>No registered library resource catalog units matching search criteria details were located.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>
</div>

<script>
    // Safe injection of session evaluation status directly into environment state memory
    const userIsAuthenticated = <?php echo $user_is_logged_in ? 'true' : 'false'; ?>;

    /**
     * Inspects current system authentication criteria and prompts for confirmation prior to execution loops.
     */
    function handleCatalogActionDispatch(event) {
        if (!userIsAuthenticated) {
            event.preventDefault(); // HALT execution lifecycle immediately
            alert("You must sign in first!");
            return false;
        }

        // Identify which action variant sub-element initiated the execution scope
        const activeSubmitter = event.submitter || document.activeElement;
        if (activeSubmitter && activeSubmitter.name === 'book_action') {
            const currentAction = activeSubmitter.value;
            const notificationTerm = (currentAction === 'borrow') ? 'borrow this catalog item' : 'place a reservation queue trace (Watch) for this book';

            if (!confirm(`Are you certain you wish to ${notificationTerm}?`)) {
                event.preventDefault();
                return false;
            }
        }
        return true;
    }
</script>

<?php include_once 'includes/footer.php'; ?>
