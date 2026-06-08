<?php
class AuthController {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    /**
     * Handles authentication matching and persistent session login state hydration
     */
    public function login() {
        $alert_message = "";
        $alert_type    = "";
        $username      = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_login'])) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $alert_message = "Please enter both username and password.";
                $alert_type    = "error";
            } else {
                try {
                    $query = "SELECT u.id, u.username, u.password_hash, u.role,
                                     b.id AS borrower_id, b.student_id, b.name AS borrower_name
                              FROM users u
                              LEFT JOIN borrowers b ON u.id = b.user_id
                              WHERE u.username = :username LIMIT 1";

                    $stmt = $this->db->prepare($query);
                    $stmt->execute(['username' => $username]);
                    $user_account = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user_account && $user_account['username'] === $username && password_verify($password, $user_account['password_hash'])) {

                        $_SESSION['user_id']       = $user_account['id'];
                        $_SESSION['username']      = $user_account['username'];
                        $_SESSION['role']          = strtolower($user_account['role'] ?? 'student');

                        $_SESSION['borrower_id']   = $user_account['borrower_id'] ?? null;
                        $_SESSION['student_id']    = $user_account['student_id'] ?? null;
                        $_SESSION['borrower_name'] = $user_account['borrower_name'] ?? $user_account['username'];

                        header("Location: index.php?page=success&msg=Login+Successful!&redirect=index.php?page=profile");
                        exit();
                    } else {
                        $alert_message = "Invalid username or password credentials.";
                        $alert_type    = "error";
                    }

                } catch (\PDOException $e) {
                    $alert_message = "System Exception: Unable to establish an authentication hand-shake with infrastructure database.";
                    $alert_type    = "error";
                }
            }
        }

        return [
            'alert_message' => $alert_message,
            'alert_type'    => $alert_type,
            'username'      => $username
        ];
    }

    /**
     * SEAMLESS INTEGRATION: Manages input validation and relational database transaction execution loops
     */
    public function register() {
        $alert_message = "";
        $alert_type    = "";

        // Initialize dynamic field parameters for presentation hydration matching
        $username   = "";
        $email      = "";
        $name       = "";
        $student_id = "";
        $contact    = "";
        $course     = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_registration'])) {
            $username   = trim($_POST['username'] ?? '');
            $password   = $_POST['password'] ?? '';
            $email      = trim($_POST['email'] ?? '');
            $name       = trim($_POST['name'] ?? '');
            $student_id = trim($_POST['student_id'] ?? '');
            $contact    = trim($_POST['contact_number'] ?? '');
            $course     = trim($_POST['course'] ?? '');

            // Validate all strictly required inputs
            if (empty($username) || empty($password) || empty($email) || empty($name) || empty($student_id) || empty($contact) || empty($course)) {
                $alert_message = "Please complete all fields with valid information.";
                $alert_type    = "error";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $alert_message = "Please provide a structurally valid email address.";
                $alert_type    = "error";
            } else {
                try {
                    // Precise Lookup: Isolate if username or email conflicts exist within USERS table
                    $check_user_stmt = $this->db->prepare("SELECT username, email FROM users WHERE username = :username OR email = :email LIMIT 1");
                    $check_user_stmt->execute([
                        'username' => $username,
                        'email'    => $email
                    ]);
                    $existing_user = $check_user_stmt->fetch(PDO::FETCH_ASSOC);

                    // Relational Lookup: Isolate if student_id conflicts exist within BORROWERS table
                    $check_borrower_stmt = $this->db->prepare("SELECT student_id FROM borrowers WHERE student_id = :student_id LIMIT 1");
                    $check_borrower_stmt->execute([
                        'student_id' => $student_id
                    ]);
                    $existing_borrower = $check_borrower_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing_user) {
                        if (strtolower($existing_user['username']) === strtolower($username)) {
                            $alert_message = "Username is already registered. Please try another.";
                        } else {
                            $alert_message = "Email address is already registered. Please try logging in.";
                        }
                        $alert_type = "error";
                    } elseif ($existing_borrower) {
                        $alert_message = "This Student ID Number is already registered inside the system ledger.";
                        $alert_type = "error";
                    } else {
                        // Initialize database transaction boundary
                        $this->db->beginTransaction();

                        // Hash password securely via BCRYPT algorithm
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $default_role    = 'student';

                        // STEP 1: Insert core credential payload into the USERS structural matrix
                        $user_stmt = $this->db->prepare("INSERT INTO users (username, password_hash, role, email) VALUES (:username, :password_hash, :role, :email)");
                        $user_stmt->execute([
                            'username'      => $username,
                            'password_hash' => $hashed_password,
                            'role'          => $default_role,
                            'email'         => $email
                        ]);

                        // Track the newly provisioned relational primary key auto-increment ID
                        $new_user_id = $this->db->lastInsertId();

                        // STEP 2: Insert borrower metadata details linked cleanly via user_id foreign key
                        $borrower_stmt = $this->db->prepare("INSERT INTO borrowers (user_id, student_id, name, course, contact) VALUES (:user_id, :student_id, :name, :course, :contact)");
                        $borrower_stmt->execute([
                            'user_id'    => $new_user_id,
                            'student_id' => $student_id,
                            'name'       => $name,
                            'course'     => $course,
                            'contact'    => $contact
                        ]);

                        // Commit transaction modifications securely across tables simultaneously
                        $this->db->commit();

                        // Route into single entry success hub redirecting cleanly back to login case step
                        header("Location: index.php?page=success&msg=" . urlencode("Registration successful! You can now log in.") . "&redirect=index.php?page=login");
                        exit();
                    }

                } catch (\PDOException $e) {
                    // Roll back active relational queries state adjustments if an infrastructure exception trips
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    $alert_message = "System Exception encountered during registration save protocol: " . $e->getMessage();
                    $alert_type    = "error";
                }
            }
        }

        return [
            'alert_message' => $alert_message,
            'alert_type'    => $alert_type,
            'username'      => $username,
            'email'         => $email,
            'name'          => $name,
            'student_id'    => $student_id,
            'contact'       => $contact,
            'course'        => $course
        ];
    }

    /**
     * Terminates state auth contexts and builds logged-out payload parameters
     */
    public function logout() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        return [
            'message'      => 'LOGGED OUT SUCCESSFULLY!',
            'redirect_url' => 'index.php?page=home'
        ];
    }

    /**
     * Safely parses, sanitizes, and routes custom success tracking alerts
     */
    public function success() {
        $raw_msg = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_SPECIAL_CHARS);
        $raw_redirect = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL);

        $message      = !empty($raw_msg) ? $raw_msg : 'ACTION SUCCESSFUL!';
        $redirect_url = !empty($raw_redirect) ? $raw_redirect : 'index.php?page=home';

        if (strpos($redirect_url, '.php') !== false && strpos($redirect_url, 'index.php') === false) {
            $cleaned_page = str_replace('.php', '', $redirect_url);
            $redirect_url = "index.php?page=" . $cleaned_page;
        }

        return [
            'message'      => $message,
            'redirect_url' => $redirect_url
        ];
    }

    /**
     * Parses and filters incoming system error message exceptions
     */
    public function error() {
        $raw_msg = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_SPECIAL_CHARS);
        $message = !empty($raw_msg) ? $raw_msg : 'OOPS! THERE WAS AN ERROR';

        return [
            'error_message' => $message
        ];
    }
}
