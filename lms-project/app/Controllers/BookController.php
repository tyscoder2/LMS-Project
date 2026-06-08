<?php
require_once __DIR__ . '/../Models/Book.php';

class BookController {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    public function index() {
        $bookModel = new Book($this->db);

        $placeholder_cover = 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=400&q=80';
        $default_book = [
            'title'  => 'No Books Available',
            'author' => 'System',
            'isbn'   => '000-0-00-000000-0',
            'cover'  => $placeholder_cover
        ];

        try {
            $featured_set = $bookModel->getFeaturedBooks(7);
            $new_set      = $bookModel->getNewBooks(6);
            $popular_set  = $bookModel->getPopularBooks(7);

            $data['featured_main']    = $featured_set[0] ?? $default_book;
            $data['featured_gallery'] = array_slice($featured_set, 1);

            $data['new_main']         = $new_set[0] ?? $default_book;
            $data['new_gallery']      = array_slice($new_set, 1);

            $data['popular_main']     = $popular_set[0] ?? $default_book;
            $data['popular_gallery']  = array_slice($popular_set, 1);

        } catch (\Exception $e) {
            $data['featured_main']    = $data['new_main'] = $data['popular_main'] = $default_book;
            $data['featured_gallery'] = $data['new_gallery'] = $data['popular_gallery'] = [];
        }

        return $data;
    }

    public function about() {
        return [
            'page_title' => 'About'
        ];
    }

    // SEAMLESS INTEGRATION: New FAQ Method
    public function faq() {
        return [
            'page_title' => 'FAQ'
        ];
    }

    /**
     * Centralized handling engine for administrative catalog management operations
     */
    public function manageBooks() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Strict Authentication Gatekeeping Core
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_role = $_SESSION['role'] ?? 'student';
        if (strtolower($user_role) === 'student') {
            header("Location: index.php?page=error");
            exit();
        }

        $bookModel = new Book($this->db);
        $success_msg = "";
        $error_msg = "";

        // Process Incoming Command Pipeline
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // ACTION: Register new resource material item
            if (isset($_POST['action_add_book'])) {
                $title  = trim($_POST['title'] ?? '');
                $author = trim($_POST['author'] ?? '');

                if (!empty($title) && !empty($author)) {
                    $cover_path = $this->handleFileUpload('book_cover');
                    $category_id = $bookModel->resolveCategoryId(trim($_POST['category'] ?? 'Education'));

                    $bookData = [
                        'isbn'        => trim($_POST['isbn'] ?? ''),
                        'title'       => $title,
                        'author'      => $author,
                        'category_id' => $category_id,
                        'copies'      => (int)($_POST['copies'] ?? 1),
                        'cover_image' => $cover_path
                    ];

                    $bookModel->createBook($bookData);
                    $success_msg = "Book registered directly to database infrastructure successfully.";
                } else {
                    $error_msg = "Please fill out required fields (Title and Author).";
                }
            }

            // ACTION: Apply inline update configurations
            if (isset($_POST['action_edit_book'])) {
                $edit_id = (int)($_POST['book_id'] ?? 0);
                $title   = trim($_POST['title'] ?? '');
                $author  = trim($_POST['author'] ?? '');

                if ($edit_id > 0 && !empty($title) && !empty($author)) {
                    $updated_cover = $this->handleFileUpload('edit_book_cover');

                    if ($updated_cover !== null) {
                        $old_cover = $bookModel->getCoverImagePath($edit_id);
                        if (!empty($old_cover) && file_exists($old_cover)) {
                            @unlink($old_cover);
                        }
                    }

                    $category_id = $bookModel->resolveCategoryId(trim($_POST['category'] ?? 'Education'));

                    $updateData = [
                        'title'       => $title,
                        'author'      => $author,
                        'isbn'        => trim($_POST['isbn'] ?? ''),
                        'category_id' => $category_id,
                        'copies'      => (int)($_POST['copies'] ?? 0),
                        'cover_image' => $updated_cover
                    ];

                    $bookModel->updateBook($edit_id, $updateData);
                    $success_msg = "Changes preserved cleanly to database matrix infrastructure.";
                } else {
                    $error_msg = "Cannot update entity: Ensure required fields are completed.";
                }
            }

            // ACTION: Process structural context deletion pipeline
            if (isset($_POST['action_delete_book'])) {
                $delete_id = (int)($_POST['book_id'] ?? 0);
                if ($delete_id > 0) {
                    $old_cover = $bookModel->getCoverImagePath($delete_id);
                    if (!empty($old_cover) && file_exists($old_cover)) {
                        @unlink($old_cover);
                    }
                    $bookModel->deleteBook($delete_id);
                    $success_msg = "Catalog structural target item dropped from infrastructure memory.";
                }
            }
        }

        // Parse GET Search Parameters & View Refinement Options
        $search_query   = trim($_GET['search'] ?? '');
        $sort_selection = $_GET['sort'] ?? 'newest';
        $items_limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        if (!in_array($items_limit, [10, 20, 50])) { $items_limit = 10; }

        $filters = [
            'title'    => isset($_GET['f_title']),
            'author'   => isset($_GET['f_author']),
            'category' => isset($_GET['f_category']),
            'keyword'  => isset($_GET['f_keyword'])
        ];

        if (!$filters['title'] && !$filters['author'] && !$filters['category'] && !$filters['keyword']) {
            $filters['title'] = $filters['author'] = $filters['category'] = $filters['keyword'] = true;
        }

        $existing_categories = $bookModel->getCategoriesWithSeeder();
        $registered_books_collection = $bookModel->getAllBooksWithFilters($search_query, $sort_selection, $items_limit, $filters);

        return [
            'registered_books_collection' => $registered_books_collection,
            'existing_categories'         => $existing_categories,
            'search_query'                => $search_query,
            'sort_selection'              => $sort_selection,
            'items_limit'                 => $items_limit,
            'filters'                     => $filters,
            'success_msg'                 => $success_msg,
            'error_msg'                   => $error_msg
        ];
    }

    /**
     * Managed Utility processing function handles physical server image upload storage tracking maps
     */
    private function handleFileUpload($inputFieldName) {
        if (isset($_FILES[$inputFieldName]) && $_FILES[$inputFieldName]['error'] === UPLOAD_ERR_OK) {
            $file_tmp     = $_FILES[$inputFieldName]['tmp_name'];
            $file_orig    = $_FILES[$inputFieldName]['name'];
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
                    return $dest_file_path;
                }
            }
        }
        return null;
    }
}
