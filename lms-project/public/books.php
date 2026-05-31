<?php
// Initialize session checking and authorization configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * STRICT AUTHENTICATION GUARDRAIL
 */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Strict role verification (Admins & Librarians Only)
$user_role = $_SESSION['role'] ?? 'student';
if (strtolower($user_role) === 'student') {
    header("Location: error.php");
    exit();
}

$page_title = "Books and Material Management";
include_once 'includes/header.php';

// Database configuration settings
$host = 'localhost';
$db   = 'lms_project';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Default interface view states
$search_query   = trim($_GET['search'] ?? '');
$sort_selection = $_GET['sort'] ?? 'newest';
$items_limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($items_limit, [10, 20, 50])) { $items_limit = 10; }

// Process checkboxes values safely
$filter_title    = isset($_GET['f_title']);
$filter_author   = isset($_GET['f_author']);
$filter_category = isset($_GET['f_category']);
$filter_keyword  = isset($_GET['f_keyword']);

if (!$filter_title && !$filter_author && !$filter_category && !$filter_keyword) {
    $filter_title = $filter_author = $filter_category = $filter_keyword = true;
}

$success_msg = "";
$error_msg = "";
$existing_categories = [];

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    /* ==========================================================================
       DYNAMIC CATEGORY FETCH PIPELINE (WITH EMERGENCE SEEDER FALLBACK)
       ========================================================================== */
    $cat_fetch_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $existing_categories = $cat_fetch_stmt->fetchAll();

    // If database table contains zero options, populate standard base baselines automatically
    if (empty($existing_categories)) {
        $seeder_fallbacks = ['Education', 'Fiction', 'Technology', 'History', 'Science'];
        foreach ($seeder_fallbacks as $seed_name) {
            $seed_stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (:name, '')");
            $seed_stmt->execute(['name' => $seed_name]);
        }
        // Re-query newly established records
        $cat_fetch_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        $existing_categories = $cat_fetch_stmt->fetchAll();
    }

    /* ==========================================================================
       POST HANDLER: NEW RESOURCE MATERIAL CREATION
       ========================================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_book'])) {
        $b_title   = trim($_POST['title'] ?? '');
        $b_author  = trim($_POST['author'] ?? '');
        $b_isbn    = trim($_POST['isbn'] ?? '');
        $b_cat     = trim($_POST['category'] ?? 'Education');
        $b_copies  = (int)($_POST['copies'] ?? 1);

        $book_cover_path = null;

        if (isset($_FILES['book_cover']) && $_FILES['book_cover']['error'] === UPLOAD_ERR_OK) {
            $file_tmp      = $_FILES['book_cover']['tmp_name'];
            $file_orig     = $_FILES['book_cover']['name'];
            $file_ext      = strtolower(pathinfo($file_orig, PATHINFO_EXTENSION));
            $allowed_exts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $target_upload_dir = 'books/thumbs/';
                if (!is_dir($target_upload_dir)) {
                    mkdir($target_upload_dir, 0755, true);
                }
                $new_cover_name = "book_" . time() . "_" . uniqid() . "." . $file_ext;
                $dest_file_path = $target_upload_dir . $new_cover_name;

                if (move_uploaded_file($file_tmp, $dest_file_path)) {
                    $book_cover_path = $dest_file_path;
                }
            }
        }

        if (!empty($b_title) && !empty($b_author)) {
            // Resolve text category choice directly to its relational ID (Auto-seeds if missing)
            $cat_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
            $cat_stmt->execute(['name' => $b_cat]);
            $category_row = $cat_stmt->fetch();

            if ($category_row) {
                $category_id = $category_row['id'];
            } else {
                $new_cat_stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, '')");
                $new_cat_stmt->execute(['name' => $b_cat]);
                $category_id = $pdo->lastInsertId();
            }

            $insert_sql = "INSERT INTO books (isbn, title, author, category_id, copies, cover_image)
                           VALUES (:isbn, :title, :author, :category_id, :copies, :cover)";
            $ins_stmt = $pdo->prepare($insert_sql);
            $ins_stmt->execute([
                'isbn'        => $b_isbn,
                'title'       => $b_title,
                'author'      => $b_author,
                'category_id' => $category_id,
                'copies'      => $b_copies,
                'cover'       => $book_cover_path
            ]);
            $success_msg = "Book registered directly to database infrastructure successfully.";

            // Re-fetch category reference collection in case a missing option auto-seeded
            $existing_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
        } else {
            $error_msg = "Please fill out required fields (Title and Author).";
        }
    }

    /* ==========================================================================
       POST HANDLER: INLINE UPDATE SAVE ENGINE (WITH COVER UPLOAD CAPABILITY)
       ========================================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit_book'])) {
        $edit_id     = (int)($_POST['book_id'] ?? 0);
        $edit_title  = trim($_POST['title'] ?? '');
        $edit_author = trim($_POST['author'] ?? '');
        $edit_isbn   = trim($_POST['isbn'] ?? '');
        $edit_cat    = trim($_POST['category'] ?? 'Education');
        $edit_copies = (int)($_POST['copies'] ?? 0);

        if ($edit_id > 0 && !empty($edit_title) && !empty($edit_author)) {
            // Resolve text category choice directly to its relational ID (Auto-seeds if missing)
            $cat_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
            $cat_stmt->execute(['name' => $edit_cat]);
            $category_row = $cat_stmt->fetch();

            if ($category_row) {
                $category_id = $category_row['id'];
            } else {
                $new_cat_stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, '')");
                $new_cat_stmt->execute(['name' => $edit_cat]);
                $category_id = $pdo->lastInsertId();
            }

            // Inline File Image Upload Processing Core Flow
            $updated_cover_path = null;
            if (isset($_FILES['edit_book_cover']) && $_FILES['edit_book_cover']['error'] === UPLOAD_ERR_OK) {
                $file_tmp     = $_FILES['edit_book_cover']['tmp_name'];
                $file_orig    = $_FILES['edit_book_cover']['name'];
                $file_ext     = strtolower(pathinfo($file_orig, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($file_ext, $allowed_exts)) {
                    $target_upload_dir = 'books/thumbs/';
                    if (!is_dir($target_upload_dir)) {
                        mkdir($target_upload_dir, 0755, true);
                    }
                    $new_cover_name = "book_" . time() . "_" . uniqid() . "." . $file_ext;
                    $dest_file_path = $target_upload_dir . $new_cover_name;

                    if (move_uploaded_file($file_tmp, $dest_file_path)) {
                        $updated_cover_path = $dest_file_path;

                        // Fetch old file pointer asset to purge storage disk leaks
                        $old_img_stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = :id");
                        $old_img_stmt->execute(['id' => $edit_id]);
                        $old_img_file = $old_img_stmt->fetchColumn();
                        if (!empty($old_img_file) && file_exists($old_img_file)) {
                            @unlink($old_img_file);
                        }
                    }
                }
            }

            // Construct SQL statement string based on asset presence vector criteria logic
            if ($updated_cover_path !== null) {
                $update_sql = "UPDATE books SET title = :title, author = :author, isbn = :isbn, category_id = :category_id, copies = :copies, cover_image = :cover WHERE id = :id";
                $bind_params = [
                    'title'       => $edit_title,
                    'author'      => $edit_author,
                    'isbn'        => $edit_isbn,
                    'category_id' => $category_id,
                    'copies'      => $edit_copies,
                    'cover'       => $updated_cover_path,
                    'id'          => $edit_id
                ];
            } else {
                $update_sql = "UPDATE books SET title = :title, author = :author, isbn = :isbn, category_id = :category_id, copies = :copies WHERE id = :id";
                $bind_params = [
                    'title'       => $edit_title,
                    'author'      => $edit_author,
                    'isbn'        => $edit_isbn,
                    'category_id' => $category_id,
                    'copies'      => $edit_copies,
                    'id'          => $edit_id
                ];
            }

            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($bind_params);
            $success_msg = "Changes preserved cleanly to database matrix infrastructure.";

            // Re-fetch category reference collection in case a missing option auto-seeded
            $existing_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
        } else {
            $error_msg = "Cannot update entity: Ensure required fields are completed.";
        }
    }

    /* ==========================================================================
       POST HANDLER: SECURE REMOVAL PIPELINE
       ========================================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_book'])) {
        $delete_id = (int)($_POST['book_id'] ?? 0);
        if ($delete_id > 0) {
            // Optional: Fetch image file location to unlink before record deletion
            $img_stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = :id");
            $img_stmt->execute(['id' => $delete_id]);
            $cover_file = $img_stmt->fetchColumn();
            if (!empty($cover_file) && file_exists($cover_file)) {
                @unlink($cover_file);
            }

            $delete_stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
            $delete_stmt->execute(['id' => $delete_id]);
            $success_msg = "Catalog structural target item dropped from infrastructure memory.";
        }
    }

    /* ==========================================================================
       GET DATA PIPELINE: SEARCH, FILTER & RELATIONAL JOIN EXPRESSIONS
       ========================================================================== */
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

    $base_select_sql .= " LIMIT :row_limit";

    $fetch_stmt = $pdo->prepare($base_select_sql);
    $fetch_stmt->bindValue(':row_limit', $items_limit, PDO::PARAM_INT);
    foreach ($query_params as $param_key => $param_val) {
        $fetch_stmt->bindValue(':' . $param_key, $param_val);
    }

    $fetch_stmt->execute();
    $registered_books_collection = $fetch_stmt->fetchAll();

} catch (\PDOException $e) {
    $error_msg = "Database Pipeline Error: " . $e->getMessage();
    $registered_books_collection = [];
}
?>

<style>
    .hidden-state { display: none !important; }
    .inline-edit-input { width: 100%; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; margin: 2px 0; }
    .metadata-action-box-right { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
    .book-cover-frame-yellowish { position: relative; overflow: hidden; }
    .inline-cover-modifier-label {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        background: rgba(15, 23, 42, 0.85);
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        padding: 6px 0;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .inline-cover-modifier-label:hover { background: rgba(231, 76, 60, 0.95); }
</style>

<main class="content-container books-canvas">
    <div class="books-inner-wrapper">

        <h1 class="books-main-title text-center">BOOKS AND MATERIAL</h1>
        <p class="books-subtitle-notice text-center">Enter a title, author, ISBN, or keyword description.</p>

        <form action="books.php" method="GET" class="search-filtering-form-node">
            <div class="search-input-field-row">
                <input type="text" name="search" class="search-bar-input" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-execution-trigger">
                    <svg class="search-svg-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </div>

            <div class="control-refinement-row-deck">
                <div class="dropdown-flex-group">
                    <div class="select-facade-container dropdown-sort">
                        <select name="sort" onchange="this.form.submit()" class="native-refinement-select">
                            <option value="newest" <?php echo $sort_selection === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                            <option value="oldest" <?php echo $sort_selection === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                            <option value="name" <?php echo $sort_selection === 'name' ? 'selected' : ''; ?>>Sort by: By title</option>
                        </select>
                    </div>
                    <div class="select-facade-container dropdown-limit">
                        <select name="limit" onchange="this.form.submit()" class="native-refinement-select">
                            <option value="10" <?php echo $items_limit === 10 ? 'selected' : ''; ?>>Show 10 items</option>
                            <option value="20" <?php echo $items_limit === 20 ? 'selected' : ''; ?>>Show 20 items</option>
                            <option value="50" <?php echo $items_limit === 50 ? 'selected' : ''; ?>>Show 50 items</option>
                        </select>
                    </div>
                </div>

                <div class="checkbox-filter-strip-row">
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_title" value="1" <?php echo $filter_title ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Title</label>
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_author" value="1" <?php echo $filter_author ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Author</label>
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_category" value="1" <?php echo $filter_category ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Category</label>
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_keyword" value="1" <?php echo $filter_keyword ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Keyword</label>
                </div>
            </div>
        </form>

        <?php if (!empty($success_msg)): ?><div class="system-status-toast status-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
        <?php if (!empty($error_msg)): ?><div class="system-status-toast status-error"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

        <section class="book-creation-deck-section">
            <form action="books.php" method="POST" enctype="multipart/form-data" class="book-creation-form">
                <div class="split-creation-layout-container">
                    <div class="creation-left-cover-picker-box">
                        <div class="book-cover-frame-yellowish" id="cover-preview-wrapper">
                            <div class="inner-book-vector" id="vector-artwork-blueprint"><div class="vector-circle"></div><div class="vector-divider"></div></div>
                            <img src="" alt="Cover thumbnail preview" class="fluid-cover-preview-img hidden-state" id="target-cover-preview">
                        </div>
                        <label for="book-cover-file-input" class="book-cover-trigger-btn">Change image</label>
                        <input type="file" name="book_cover" id="book-cover-file-input" accept="image/*" class="hidden-state" onchange="previewBookCoverFile(this)">
                    </div>

                    <div class="creation-right-inputs-stack">
                        <div class="input-field-group-row"><input type="text" name="title" placeholder="Title" class="creation-field-node-box" required></div>
                        <div class="input-field-group-row"><input type="text" name="author" placeholder="Author" class="creation-field-node-box" required></div>
                        <div class="input-field-group-row"><input type="text" name="isbn" placeholder="ISBN" class="creation-field-node-box"></div>
                        <div class="input-field-group-row">
                            <div class="select-facade-container selection-full-span">
                                <select name="category" class="native-refinement-select form-input-node" required>
                                    <?php foreach ($existing_categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="input-field-group-row"><input type="number" name="copies" placeholder="Number of copies" min="1" value="1" class="creation-field-node-box" required></div>
                    </div>
                </div>
                <div class="creation-execution-row-centered"><button type="submit" name="action_add_book" value="1" class="book-submit-execution-btn">Add</button></div>
            </form>
        </section>

        <div class="books-display-list-vertical-stack">
            <?php if (!empty($registered_books_collection)): ?>
                <?php foreach ($registered_books_collection as $book_row_data): ?>

                    <form action="books.php" method="POST" enctype="multipart/form-data" class="book-material-card-wrapper">
                        <input type="hidden" name="book_id" value="<?php echo $book_row_data['id']; ?>">

                        <div class="book-cover-frame-yellowish flex-shrink-0">
                            <?php if (!empty($book_row_data['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($book_row_data['cover_image']); ?>" alt="Material cover" class="fluid-cover-preview-img" id="cover-view-node-<?php echo $book_row_data['id']; ?>">
                            <?php else: ?>
                                <div class="inner-book-vector" id="vector-view-node-<?php echo $book_row_data['id']; ?>"><div class="vector-circle"></div><div class="vector-divider"></div></div>
                                <img src="" alt="Material cover" class="fluid-cover-preview-img hidden-state" id="cover-view-node-<?php echo $book_row_data['id']; ?>">
                            <?php endif; ?>

                            <label for="inline-file-<?php echo $book_row_data['id']; ?>" class="inline-cover-modifier-label input-edit-state hidden-state">Change Image</label>
                            <input type="file" name="edit_book_cover" id="inline-file-<?php echo $book_row_data['id']; ?>" accept="image/*" class="hidden-state" onchange="previewInlineBookCover(this, <?php echo $book_row_data['id']; ?>)">
                        </div>

                        <div class="book-metadata-lavender-block">
                            <div class="metadata-rows-stack-left">
                                <div class="metadata-line-row">
                                    <span class="meta-label">Title:</span>
                                    <span class="meta-data-val font-prominent txt-view-state"><?php echo htmlspecialchars($book_row_data['title']); ?></span>
                                    <input type="text" name="title" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['title']); ?>" required>
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">Author:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars($book_row_data['author']); ?></span>
                                    <input type="text" name="author" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['author']); ?>" required>
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">ISBN:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars(!empty($book_row_data['isbn']) ? $book_row_data['isbn'] : 'N/A'); ?></span>
                                    <input type="text" name="isbn" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['isbn']); ?>">
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">Category:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars($book_row_data['category_name'] ?? 'Unassigned'); ?></span>
                                    <select name="category" class="inline-edit-input input-edit-state hidden-state" required>
                                        <?php foreach ($existing_categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo ($cat['name'] === ($book_row_data['category_name'] ?? '')) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">Copies:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars($book_row_data['copies']); ?></span>
                                    <input type="number" name="copies" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['copies']); ?>" min="0" required>
                                </div>
                            </div>

                            <div class="metadata-action-box-right">
                                <button type="button" class="card-action-context-node btn-borrow btn-trigger-edit" onclick="enableInlineEditMode(this)">Edit</button>
                                <button type="submit" name="action_edit_book" class="card-action-context-node btn-borrow btn-trigger-save hidden-state" style="background-color: #2f855a;">Save</button>
                                <button type="submit" name="action_delete_book" class="card-action-context-node btn-watch" style="background-color: #e53e3e;" onclick="return confirm('Are you sure?');">Delete</button>
                            </div>
                        </div>
                    </form>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-results-fallback-card text-center"><p>No registered library resource catalog units matching search criteria details were located.</p></div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
function enableInlineEditMode(buttonElement) {
    const rootCardForm = buttonElement.closest('.book-material-card-wrapper');
    rootCardForm.querySelectorAll('.txt-view-state').forEach(element => element.classList.add('hidden-state'));
    rootCardForm.querySelectorAll('.input-edit-state').forEach(element => element.classList.remove('hidden-state'));
    buttonElement.classList.add('hidden-state');
    rootCardForm.querySelector('.btn-trigger-save').classList.remove('hidden-state');
}

// UPDATED: HANDLES REAL-TIME THUMBNAIL LIVE PREVIEWS FOR INLINE MATERIAL CARD EDITS
function previewInlineBookCover(fileNodeInput, bookId) {
    if (fileNodeInput.files && fileNodeInput.files[0]) {
        const imageStreamReader = new FileReader();
        imageStreamReader.onload = function (eventObj) {
            const visualImgNode = document.getElementById('cover-view-node-' + bookId);
            const vectorArtBlueprint = document.getElementById('vector-view-node-' + bookId);

            visualImgNode.src = eventObj.target.result;
            visualImgNode.classList.remove('hidden-state');

            if (vectorArtBlueprint) {
                vectorArtBlueprint.style.display = 'none';
            }
        };
        imageStreamReader.readAsDataURL(fileNodeInput.files[0]);
    }
}

function previewBookCoverFile(fileNodeInput) {
    if (fileNodeInput.files && fileNodeInput.files[0]) {
        const imageStreamReader = new FileReader();
        imageStreamReader.onload = function (eventObj) {
            const visualImgNode = document.getElementById('target-cover-preview');
            const vectorArtBlueprint = document.getElementById('vector-artwork-blueprint');

            visualImgNode.src = eventObj.target.result;
            visualImgNode.classList.remove('hidden-state');

            if (vectorArtBlueprint) {
                vectorArtBlueprint.style.display = 'none';
            }
        };
        imageStreamReader.readAsDataURL(fileNodeInput.files[0]);
    }
}
</script>

<?php include_once 'includes/footer.php'; ?>
