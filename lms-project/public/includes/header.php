<?php
// Ensure session safety if not already initialized globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * STRICT AUTHENTICATION STATE VERIFICATION (Zero Simulation Fallbacks)
 * Using !empty() ensures that if $_SESSION['user_id'] is null, 0, false,
 * or an empty string, it correctly and safely evaluates to false.
 */
$is_logged_in = (!empty($_SESSION['user_id']) && isset($_SESSION['role']));

/* ==========================================================================
   AUTHENTICATION ROUTE GUARD (Prevents Logged-In Access to Login/Register)
   ========================================================================== */
if ($is_logged_in) {
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    if ($current_page === 'login.php' || $current_page === 'register.php') {
        echo "<script>
            alert('Already logged in. Please log out for these actions.');
            window.location.href = 'profile.php';
        </script>";
        exit(); // Stop parsing the rest of the unauthorized file
    }
}

/* ==========================================================================
   DYNAMIC BASE URL ANCHOR (Senior Dev Path Trapping Protection)
   ========================================================================== */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$current_script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Look for the main public folder boundary to establish an absolute base URL route
$public_folder_position = strpos($current_script_dir, '/public');
if ($public_folder_position !== false) {
    // Truncates paths right at the public container directory
    $clean_root_dir = substr($current_script_dir, 0, $public_folder_position + 7);
} else {
    $clean_root_dir = $current_script_dir;
}

$base_url = rtrim($protocol . $host . $clean_root_dir, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | MMC LMS" : "MMC Library Management System"; ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="header-top">
            <a href="<?php echo $base_url; ?>/index.php" class="logo-area">
                <div class="logo-placeholder">
                    <img src="imgs/MMC_logo.png" alt="MMC Logo">
                </div>
                <span class="brand-name">MMC<br><small>LMS</small></span>
            </a>

            <nav class="main-nav">
                <a href="<?php echo $base_url; ?>/index.php" class="nav-link">Home</a>
                <a href="<?php echo $base_url; ?>/about.php" class="nav-link">About</a>
                <a href="<?php echo $base_url; ?>/contact.php" class="nav-link">Contact</a>
            </nav>

            <div class="search-bar-container">
                <form action="<?php echo $base_url; ?>/search.php" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search title or author..." aria-label="Search">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <div class="header-sub-nav">
            <?php if (!$is_logged_in): ?>
                <div class="auth-actions state-logged-out">
                    <a href="<?php echo $base_url; ?>/login.php" class="auth-link">Login/Register</a>
                </div>
            <?php else: ?>
                <div class="auth-actions state-logged-in">
                    <div class="user-menu-left">
                        <a href="<?php echo $base_url; ?>/profile.php" class="auth-link">Profile</a>
                        <a href="<?php echo $base_url; ?>/settings.php" class="auth-link">Settings</a>
                        <a href="<?php echo $base_url; ?>/management.php" class="auth-link">Manage</a>
                    </div>
                    <div class="user-menu-right">
                        <a href="<?php echo $base_url; ?>/success.php?action=logout" class="auth-link logout-trigger">Logout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>
