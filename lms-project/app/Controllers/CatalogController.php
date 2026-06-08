<?php
class CatalogController {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    public function index() {
        $success_msg = "";
        $error_msg   = "";

        $user_is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

        /* ==========================================================================
           POST HANDLER: BORROW TRANSACTION & RESERVATION PIPELINE
           ========================================================================== */
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_action']) && $this->db !== null) {
            if (!$user_is_logged_in) {
                $error_msg = "Authentication required: Please sign in to issue catalog requests.";
            } else {
                $action_type    = $_POST['book_action'];
                $target_book_id = (int)($_POST['book_id'] ?? 0);
                $current_user_id = $_SESSION['user_id'];

                try {
                    // Pull the borrower profile tied to the user login session identity
                    $borrower_stmt = $this->db->prepare("SELECT id FROM borrowers WHERE user_id = :uid");
                    $borrower_stmt->execute(['uid' => $current_user_id]);
                    $borrower_record = $borrower_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$borrower_record) {
                        throw new \Exception("Your account does not have an active profile inside the system borrowers directory.");
                    }

                    $borrower_id = $borrower_record['id'];

                    if ($action_type === 'borrow') {
                        $this->db->beginTransaction();

                        // Lock row for update to verify real-time inventory levels accurately
                        $lock_stmt = $this->db->prepare("SELECT copies FROM books WHERE id = :id FOR UPDATE");
                        $lock_stmt->execute(['id' => $target_book_id]);
                        $book_record = $lock_stmt->fetch(PDO::FETCH_ASSOC);

                        if ($book_record && $book_record['copies'] > 0) {
                            // 1. Decrement inventory copies value
                            $dec_stmt = $this->db->prepare("UPDATE books SET copies = copies - 1 WHERE id = :id");
                            $dec_stmt->execute(['id' => $target_book_id]);

                            // 2. Generate a transaction ledger record entry
                            $tx_stmt = $this->db->prepare("INSERT INTO transactions (book_id, borrower_id, borrow_date, due_date) VALUES (:bid, :borr_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY))");
                            $tx_stmt->execute([
                                'bid'     => $target_book_id,
                                'borr_id' => $borrower_id
                            ]);

                            $new_transaction_id = $this->db->lastInsertId();
                            $this->db->commit();

                            header("Location: index.php?page=borrowed&transaction_id=" . $new_transaction_id);
                            exit();
                        } else {
                            $this->db->rollBack();
                            $error_msg = "No physical copies remaining. Please place a reservation instead.";
                        }
                    } elseif ($action_type === 'reserve') {
                        // Create item entry trace inside reservations table
                        $res_stmt = $this->db->prepare("INSERT INTO reservations (book_id, borrower_id, reserved_date, status) VALUES (:bid, :borr_id, CURDATE(), 'pending')");
                        $res_stmt->execute([
                            'bid'     => $target_book_id,
                            'borr_id' => $borrower_id
                        ]);

                        $new_reservation_id = $this->db->lastInsertId();
                        header("Location: index.php?page=borrowed&reservation_id=" . $new_reservation_id);
                        exit();
                    }
                } catch (\Exception $ex) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
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

        if ($this->db !== null) {
            $query_parts  = [];
            $query_params = [];

            if (!empty($search_query)) {
                $sub_conditions = [];
                if ($filter_title) {
                    $sub_conditions[] = "books.title LIKE :s_title";
                    $query_params['s_title'] = "%$search_query%";
                }
                if ($filter_author) {
                    $sub_conditions[] = "books.author LIKE :s_author";
                    $query_params['s_author'] = "%$search_query%";
                }
                if ($filter_category) {
                    $sub_conditions[] = "categories.name LIKE :s_cat";
                    $query_params['s_cat'] = "%$search_query%";
                }
                if ($filter_keyword) {
                    $sub_conditions[] = "books.isbn LIKE :s_isbn";
                    $query_params['s_isbn'] = "%$search_query%";
                }

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
                case 'oldest':
                    $base_select_sql .= " ORDER BY books.id ASC";
                    break;
                case 'name':
                    $base_select_sql .= " ORDER BY books.title ASC";
                    break;
                case 'newest':
                default:
                    $base_select_sql .= " ORDER BY books.id DESC";
                    break;
            }

            try {
                $fetch_stmt = $this->db->prepare($base_select_sql);
                foreach ($query_params as $param_key => $param_val) {
                    $fetch_stmt->bindValue(':' . $param_key, $param_val);
                }
                $fetch_stmt->execute();
                $registered_books_collection = $fetch_stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\PDOException $ex) {
                $error_msg = "Query Retrieval Error: " . $ex->getMessage();
            }
        }

        return [
            'success_msg'                 => $success_msg,
            'error_msg'                   => $error_msg,
            'search_query'                => $search_query,
            'sort_selection'              => $sort_selection,
            'filter_title'                => $filter_title,
            'filter_author'               => $filter_author,
            'filter_category'             => $filter_category,
            'filter_keyword'              => $filter_keyword,
            'registered_books_collection' => $registered_books_collection,
            'user_is_logged_in'           => $user_is_logged_in
        ];
    }
}
