<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set dynamic title and include system header
$page_title = "Error Encountered";
include_once 'includes/header.php';

// Check if a custom error message or code was passed via a GET parameter
$error_message = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'OOPS! THERE WAS AN ERROR';
?>

<main class="content-container error-page-canvas">
    <div class="error-inner-wrapper text-center">

        <div class="error-logo-container">
            <img src="imgs/MMC_logo.png" class="error-brand-seal" alt="Marinduque Midwest College Seal 1945">
        </div>

        <h1 class="error-main-title"><?php echo strtoupper($error_message); ?></h1>

        <div class="error-return-wrapper">
            <a href="index.php" class="error-return-link">Return to Home</a>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
