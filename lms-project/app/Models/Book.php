<?php
class Book {
    private $db;
    private $placeholder_cover = 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=400&q=80';

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    // Fetch collections sorted alphabetically
    public function getFeaturedBooks($limit = 7) {
        $sql = "SELECT isbn, title, author, IFNULL(cover_image, :cover) AS cover
                FROM books ORDER BY title ASC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cover', $this->placeholder_cover, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Fetch chronological insertions by primary key
    public function getNewBooks($limit = 6) {
        $sql = "SELECT isbn, title, author, IFNULL(cover_image, :cover) AS cover
                FROM books ORDER BY id DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cover', $this->placeholder_cover, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Compute dynamic layout rows based on borrow metric actions
    public function getPopularBooks($limit = 7) {
        $sql = "SELECT b.isbn, b.title, b.author, IFNULL(b.cover_image, :cover) AS cover, COUNT(t.id) AS borrow_count
                FROM books b
                LEFT JOIN transactions t ON b.id = t.book_id
                GROUP BY b.id
                ORDER BY borrow_count DESC, b.title ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cover', $this->placeholder_cover, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieve all available category structures, executing fallback seeding if empty
     */
    public function getCategoriesWithSeeder() {
        $stmt = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $stmt->fetchAll();

        if (empty($categories)) {
            $seeder_fallbacks = ['Education', 'Fiction', 'Technology', 'History', 'Science'];
            foreach ($seeder_fallbacks as $seed_name) {
                $seed_stmt = $this->db->prepare("INSERT IGNORE INTO categories (name, description) VALUES (:name, '')");
                $seed_stmt->execute(['name' => $seed_name]);
            }
            $stmt = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
            $categories = $stmt->fetchAll();
        }
        return $categories;
    }

    /**
     * Resolve a category name string directly into its unique relational identifier
     */
    public function resolveCategoryId($categoryName) {
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
        $stmt->execute(['name' => $categoryName]);
        $row = $stmt->fetch();

        if ($row) {
            return $row['id'];
        }

        $insert_stmt = $this->db->prepare("INSERT INTO categories (name, description) VALUES (:name, '')");
        $insert_stmt->execute(['name' => $categoryName]);
        return $this->db->lastInsertId();
    }

    /**
     * Fetch records dynamically utilizing flexible filtration settings and row constraints
     */
    public function getAllBooksWithFilters($search_query, $sort_selection, $items_limit, $filters) {
        $query_parts = [];
        $query_params = [];

        if (!empty($search_query)) {
            $sub_conditions = [];
            if ($filters['title']) {
                $sub_conditions[] = "books.title LIKE :s_title";
                $query_params['s_title'] = "%$search_query%";
            }
            if ($filters['author']) {
                $sub_conditions[] = "books.author LIKE :s_author";
                $query_params['s_author'] = "%$search_query%";
            }
            if ($filters['category']) {
                $sub_conditions[] = "categories.name LIKE :s_cat";
                $query_params['s_cat'] = "%$search_query%";
            }
            if ($filters['keyword']) {
                $sub_conditions[] = "books.isbn LIKE :s_isbn";
                $query_params['s_isbn'] = "%$search_query%";
            }

            if (!empty($sub_conditions)) {
                $query_parts[] = "(" . implode(" OR ", $sub_conditions) . ")";
            }
        }

        $base_sql = "SELECT books.*, categories.name AS category_name
                     FROM books
                     LEFT JOIN categories ON books.category_id = categories.id";

        if (!empty($query_parts)) {
            $base_sql .= " WHERE " . implode(" AND ", $query_parts);
        }

        switch ($sort_selection) {
            case 'oldest':
                $base_sql .= " ORDER BY books.id ASC";
                break;
            case 'name':
                $base_sql .= " ORDER BY books.title ASC";
                break;
            case 'newest':
            default:
                $base_sql .= " ORDER BY books.id DESC";
                break;
        }

        $base_sql .= " LIMIT :row_limit";

        $stmt = $this->db->prepare($base_sql);
        $stmt->bindValue(':row_limit', $items_limit, PDO::PARAM_INT);
        foreach ($query_params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a new library resource item entry
     */
    public function createBook($data) {
        $sql = "INSERT INTO books (isbn, title, author, category_id, copies, cover_image)
                VALUES (:isbn, :title, :author, :category_id, :copies, :cover)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'isbn'        => $data['isbn'],
            'title'       => $data['title'],
            'author'      => $data['author'],
            'category_id' => $data['category_id'],
            'copies'      => $data['copies'],
            'cover'       => $data['cover_image']
        ]);
    }

    /**
     * Modify structural configurations of an existing catalog book entry
     */
    public function updateBook($id, $data) {
        if (!empty($data['cover_image'])) {
            $sql = "UPDATE books SET title = :title, author = :author, isbn = :isbn,
                                     category_id = :category_id, copies = :copies, cover_image = :cover
                    WHERE id = :id";
            $params = [
                'title'       => $data['title'],
                'author'      => $data['author'],
                'isbn'        => $data['isbn'],
                'category_id' => $data['category_id'],
                'copies'      => $data['copies'],
                'cover'       => $data['cover_image'],
                'id'          => $id
            ];
        } else {
            $sql = "UPDATE books SET title = :title, author = :author, isbn = :isbn,
                                     category_id = :category_id, copies = :copies
                    WHERE id = :id";
            $params = [
                'title'       => $data['title'],
                'author'      => $data['author'],
                'isbn'        => $data['isbn'],
                'category_id' => $data['category_id'],
                'copies'      => $data['copies'],
                'id'          => $id
            ];
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Purge a catalog item record by its unique reference identifier
     */
    public function deleteBook($id) {
        $stmt = $this->db->prepare("DELETE FROM books WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Fetch the assigned cover image storage track of a designated entity
     */
    public function getCoverImagePath($id) {
        $stmt = $this->db->prepare("SELECT cover_image FROM books WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchColumn();
    }
}
