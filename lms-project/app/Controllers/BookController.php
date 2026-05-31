<?php
/**
 * BookController.php
 * Intercepts incoming catalog requests, processes query filters,
 * handles cover uploads, and coordinates with the Book model.
 */

class BookController {
    private $bookModel;

    public function __construct() {
        // Delegate database access patterns entirely to the Model layer
        require_once __DIR__ . '/../Models/Book.php';
        $this->bookModel = new Book();
    }

    /**
     * Display the main catalog list view
     */
    public function index() {
        // Extract inputs from the URL parameters
        $searchText = trim($_GET['search'] ?? '');
        $sortBy     = $_GET['sort'] ?? 'newest';

        $activeFilters = [
            'title'    => isset($_GET['f_title']),
            'author'   => isset($_GET['f_author']),
            'category' => isset($_GET['f_category']),
            'keyword'  => isset($_GET['f_keyword'])
        ];

        // Default to title/author searching if no checkboxes are active
        if (!array_filter($activeFilters)) {
            $activeFilters['title'] = true;
            $activeFilters['author'] = true;
        }

        // Delegate query assembly directly to the Model
        $books = $this->bookModel->all($searchText, $activeFilters, $sortBy);

        // Load the view and extract the variables safely
        require_once __DIR__ . '/../Views/books/index.php';
    }

    /**
     * Process creation of a new catalog entity via POST
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /books");
            exit();
        }

        $data = [
            'title'    => trim($_POST['title'] ?? ''),
            'author'   => trim($_POST['author'] ?? ''),
            'isbn'     => trim($_POST['isbn'] ?? ''),
            'category' => trim($_POST['category'] ?? 'Education'),
            'copies'   => (int)($_POST['copies'] ?? 1)
        ];

        $fileData = $_FILES['cover_image'] ?? null;

        // Ask model to execute validation rules and file system writes
        $result = $this->bookModel->create($data, $fileData);

        if ($result['status']) {
            $_SESSION['success'] = $result['message'];
            header("Location: /books");
        } else {
            $_SESSION['error'] = $result['message'];
            header("Location: /books/create");
        }
        exit();
    }
}
