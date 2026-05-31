<?php
/**
 * Book.php
 * Handles direct database operations, input validation, and physical
 * cover graphic disk operations for library catalog entities.
 */

class Book {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../../config/database.php';
        $this->db = Database::connect();
    }

    /**
     * Pulls filtered book catalog collections based on search states
     */
    public function all($searchText = '', $activeFilters = [], $sortBy = 'newest') {
        $query_parts = [];
        $query_params = [];
        $sql = "SELECT * FROM books";

        if (!empty($searchText)) {
            $sub_conditions = [];

            if (!empty($activeFilters['title'])) {
                $sub_conditions[] = "title LIKE :s_title";
                $query_params['s_title'] = "%$searchText%";
            }
            if (!empty($activeFilters['author'])) {
                $sub_conditions[] = "author LIKE :s_author";
                $query_params['s_author'] = "%$searchText%";
            }
            if (!empty($activeFilters['category'])) {
                $sub_conditions[] = "category LIKE :s_cat";
                $query_params['s_cat'] = "%$searchText%";
            }
            if (!empty($activeFilters['keyword'])) {
                $sub_conditions[] = "isbn LIKE :s_isbn";
                $query_params['s_isbn'] = "%$searchText%";
            }

            if (!empty($sub_conditions)) {
                $query_parts[] = "(" . implode(" OR ", $sub_conditions) . ")";
            }
        }

        if (!empty($query_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_parts);
        }

        // Apply visual catalog sort presentation preferences
        switch ($sortBy) {
            case 'oldest':
                $sql .= " ORDER BY id ASC";
                break;
            case 'year':
                $sql .= " ORDER BY created_at DESC, title ASC";
                break;
            case 'name':
                $sql .= " ORDER BY title ASC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY id DESC";
                break;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($query_params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Model Error [Book::all]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Commits a new catalog record to database memory while executing image stream saves
     */
    public function create($data, $fileData = null) {
        $coverPath = null;

        // Perform asset stream upload operations
        if ($fileData && isset($fileData['error']) && $fileData['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../../public/uploads/books/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filename = "book_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                if (move_uploaded_file($fileData['tmp_name'], $uploadDir . $filename)) {
                    $coverPath = 'uploads/books/' . $filename;
                }
            } else {
                return ['status' => false, 'message' => 'Unsupported image asset format structure.'];
            }
        }

        try {
            $sql = "INSERT INTO books (title, author, isbn, category, copies, total_copies, cover_image, created_at)
                    VALUES (:title, :author, :isbn, :category, :copies, :copies, :cover, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'title'    => $data['title'],
                'author'   => $data['author'],
                'isbn'     => $data['isbn'],
                'category' => $data['category'],
                'copies'   => $data['copies'],
                'cover'    => $coverPath
            ]);

            return ['status' => true, 'message' => 'New catalog asset successfully saved to index registries.'];
        } catch (\PDOException $e) {
            error_log("Model Error [Book::create]: " . $e->getMessage());
            return ['status' => false, 'message' => 'Database record commitment execution crash.'];
        }
    }
}
