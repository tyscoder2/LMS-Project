<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Register";
include_once 'includes/header.php';

// Form Handling Processing Engine
$alert_message = "";
$alert_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_registration'])) {
    // 1. Capture and trim inputs cleanly (Avoid pre-query HTML escaping mutations)
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $email      = trim($_POST['email'] ?? '');
    $name       = trim($_POST['name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $contact    = trim($_POST['contact_number'] ?? '');
    $course     = trim($_POST['course'] ?? '');

    // Validate inputs (All fields are strictly required)
    if (empty($username) || empty($password) || empty($email) || empty($name) || empty($student_id) || empty($contact) || empty($course)) {
        $alert_message = "Please complete all fields with valid information.";
        $alert_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = "Please provide a structurally valid email address.";
        $alert_type = "error";
    } else {
        /* ==========================================================================
            DATABASE REGISTRATION ENGINE (Relational PDO Transaction Pipeline)
           ========================================================================== */
        $host = 'localhost';
        $db   = 'lms_project';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Precise Lookup: Isolate if username or email conflicts exist within USERS table
            $check_user_stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = :username OR email = :email LIMIT 1");
            $check_user_stmt->execute([
                'username' => $username,
                'email'    => $email
            ]);
            $existing_user = $check_user_stmt->fetch();

            // Relational Lookup: Isolate if student_id conflicts exist within BORROWERS table
            $check_borrower_stmt = $pdo->prepare("SELECT student_id FROM borrowers WHERE student_id = :student_id LIMIT 1");
            $check_borrower_stmt->execute([
                'student_id' => $student_id
            ]);
            $existing_borrower = $check_borrower_stmt->fetch();

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
                $pdo->beginTransaction();

                // Hash password securely via BCRYPT algorithm
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $default_role    = 'student';

                // STEP 1: Insert core credential payload into the USERS structural matrix
                $user_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, email) VALUES (:username, :password_hash, :role, :email)");
                $user_stmt->execute([
                    'username'      => $username,
                    'password_hash' => $hashed_password,
                    'role'          => $default_role,
                    'email'         => $email
                ]);

                // Track the newly provisioned relational primary key auto-increment ID
                $new_user_id = $pdo->lastInsertId();

                // STEP 2: Insert borrower metadata details linked cleanly via user_id foreign key
                $borrower_stmt = $pdo->prepare("INSERT INTO borrowers (user_id, student_id, name, course, contact) VALUES (:user_id, :student_id, :name, :course, :contact)");
                $borrower_stmt->execute([
                    'user_id'    => $new_user_id,
                    'student_id' => $student_id,
                    'name'       => $name,
                    'course'     => $course,
                    'contact'    => $contact
                ]);

                // Commit transaction modifications securely across tables simultaneously
                $pdo->commit();

                // Redirect directly to success feedback screen mapping cleanly back to login page portal
                $success_msg = urlencode("Registration successful! You can now log in.");
                header("Location: success.php?msg=" . $success_msg . "&redirect=login.php");
                exit();
            }

        } catch (\PDOException $e) {
            // Roll back active relational queries state adjustments if an infrastructure exception trips
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $alert_message = "System Exception encountered during registration save protocol: " . $e->getMessage();
            $alert_type = "error";
        }
    }
}
?>

<main class="content-container register-page-canvas">
    <div class="register-inner-wrapper text-center">

        <div class="register-logo-container">
            <img src="imgs/MMC_logo.png" class="register-brand-seal" alt="Marinduque Midwest College Seal 1945">
        </div>

        <h1 class="register-main-title">REGISTER</h1>

        <?php if (!empty($alert_message)): ?>
            <div class="form-alert alert-<?php echo $alert_type; ?>" style="max-width: 420px; margin: 0 auto 20px auto;">
                <p><?php echo htmlspecialchars($alert_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="register-native-form">

            <div class="register-control-group">
                <input type="text" name="username" placeholder="Username" required autocomplete="username"
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>

            <div class="register-control-group">
                <input type="password" name="password" placeholder="Password" required autocomplete="new-password">
            </div>

            <div class="register-control-group">
                <input type="email" name="email" placeholder="Email" required autocomplete="email"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <hr style="border: 0; border-top: 1px dashed #e2e8f0; margin: 20px 0;">

            <div class="register-control-group">
                <input type="text" name="name" placeholder="Full Name" required autocomplete="name"
                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>

            <div class="register-control-group">
                <input type="text" name="student_id" placeholder="Student ID Number" required
                       value="<?php echo isset($student_id) ? htmlspecialchars($student_id) : ''; ?>">
            </div>

            <div class="register-control-group">
                <input type="text" name="contact_number" placeholder="Contact Number" required autocomplete="tel"
                       value="<?php echo isset($contact) ? htmlspecialchars($contact) : ''; ?>">
            </div>

            <div class="register-control-group dropdown-arrow-wrapper">
                <select name="course" required>
                    <option value="" disabled selected hidden>Course</option>
                    <option value="BSCS" <?php echo (isset($course) && $course === 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                    <option value="BEEd" <?php echo (isset($course) && $course === 'BEEd') ? 'selected' : ''; ?>>BEEd</option>
                    <option value="BSEd" <?php echo (isset($course) && $course === 'BSEd') ? 'selected' : ''; ?>>BSEd</option>
                </select>
            </div>

            <div class="register-buttons-action-row">
                <button type="submit" name="submit_registration" class="btn-register-action">Register</button>
            </div>

        </form>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
