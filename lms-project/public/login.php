<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Login";
include_once 'includes/header.php';

// Form Handling Processing Engine
$alert_message = "";
$alert_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_login'])) {
    // Capture raw inputs (Prepared statements handle SQL injection safety; trimming avoids whitespace bugs)
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $alert_message = "Please enter both username and password.";
        $alert_type = "error";
    } else {
        /* ==========================================================================
            DATABASE AUTHENTICATION HOOK (Production-Grade PDO Engine)
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

            // Unified Query: Pulls core account details AND matching profile metadata from borrowers
            $query = "SELECT u.id, u.username, u.password_hash, u.role,
                             b.id AS borrower_id, b.student_id, b.name AS borrower_name
                      FROM users u
                      LEFT JOIN borrowers b ON u.id = b.user_id
                      WHERE u.username = :username LIMIT 1";

            $stmt = $pdo->prepare($query);
            $stmt->execute(['username' => $username]);
            $user_account = $stmt->fetch();

            // Strict evaluation: verify password hash AND ensure username matches case-sensitively (===)
            if ($user_account && $user_account['username'] === $username && password_verify($password, $user_account['password_hash'])) {

                // Populate secure session storage state arrays
                $_SESSION['user_id']       = $user_account['id'];
                $_SESSION['username']      = $user_account['username'];
                $_SESSION['role']          = strtolower($user_account['role'] ?? 'student');

                // Cache relational student metrics if they exist in the borrowers ledger
                $_SESSION['borrower_id']   = $user_account['borrower_id'] ?? null;
                $_SESSION['student_id']    = $user_account['student_id'] ?? null;
                $_SESSION['borrower_name'] = $user_account['borrower_name'] ?? $user_account['username'];

                // Redirect cleanly to success engine routing them into their dashboard profile
                header("Location: success.php?msg=Login+Successful!&redirect=profile.php");
                exit();
            } else {
                $alert_message = "Invalid username or password credentials.";
                $alert_type = "error";
            }

        } catch (\PDOException $e) {
            // Secure exception handler blocks structural data leaking to front-end UI
            $alert_message = "System Exception: Unable to establish an authentication hand-shake with infrastructure database.";
            $alert_type = "error";
        }
    }
}
?>

<main class="content-container login-page-canvas">
    <div class="login-inner-wrapper text-center">

        <div class="login-logo-container">
            <img src="imgs/MMC_logo.png" class="login-brand-seal" alt="Marinduque Midwest College Seal 1945">
        </div>

        <h1 class="login-main-title">LOGIN</h1>

        <?php if (!empty($alert_message)): ?>
            <div class="form-alert alert-<?php echo $alert_type; ?>" style="max-width: 420px; margin: 0 auto 20px auto;">
                <p><?php echo htmlspecialchars($alert_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-native-form">

            <div class="login-control-group">
                <input type="text" name="username" placeholder="Username" required autocomplete="username"
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>

            <div class="login-control-group">
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            </div>

            <div class="login-buttons-action-row">
                <button type="submit" name="submit_login" class="btn-login-action">Login</button>
                <a href="register.php" class="btn-login-action link-style-btn">Register</a>
            </div>

            <div class="forgot-password-wrapper">
                <a href="forgot-password.php" class="forgot-password-link">Forgot password</a>
            </div>

        </form>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
