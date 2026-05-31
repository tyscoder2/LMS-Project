<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Intercept specific business actions passed via URL query states
$action       = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$redirect_url = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL) ?: 'index.php';
$message      = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'ACTION SUCCESSFUL!';

/* ==========================================================================
   LOGOUT INTERCEPT ENGINE
   ========================================================================== */
if ($action === 'logout') {
    $message = 'LOGGED OUT SUCCESSFULLY!';
    $redirect_url = 'index.php'; // Direct back to public homepage view

    // Clear all active session runtime memory keys
    $_SESSION = [];

    // Expire the session tracking cookie directly on the browser client
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Completely terminate the session registration token on the host server
    session_destroy();
}

// Set dynamic title and include system header (renders guest state if logged out)
$page_title = "Action Successful";
include_once 'includes/header.php';
?>

<main class="content-container success-page-canvas">
    <div class="success-inner-wrapper text-center">

        <div class="success-logo-container">
            <img src="imgs/MMC_logo.png" class="success-brand-seal" alt="Marinduque Midwest College Seal 1945">
        </div>

        <h1 class="success-main-title"><?php echo strtoupper($message); ?></h1>

        <div class="success-continue-wrapper">
            <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="success-continue-link">Continue</a>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
