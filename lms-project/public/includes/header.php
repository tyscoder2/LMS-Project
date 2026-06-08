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
            <a href="<?php echo $base_url; ?>/index.php?page=home" class="logo-area">
                <div class="logo-placeholder">
                    <img src="imgs/MMC_logo.png" alt="MMC Logo">
                </div>
                <span class="brand-name">MMC<br><small>LMS</small></span>
            </a>

            <nav class="main-nav">
                <a href="<?php echo $base_url; ?>/index.php?page=home" class="nav-link">Home</a>
                <a href="<?php echo $base_url; ?>/index.php?page=about" class="nav-link">About</a>
                <a href="<?php echo $base_url; ?>/index.php?page=contact" class="nav-link">Contact</a>
            </nav>

            <div class="search-bar-container">
                <form action="<?php echo $base_url; ?>/index.php" method="GET" class="search-form">
                    <input type="hidden" name="page" value="search">
                    <input type="text" name="search" placeholder="Search title or author..." aria-label="Search">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <div class="header-sub-nav">
            <?php if (!$is_logged_in): ?>
                <div class="auth-actions state-logged-out">
                    <a href="<?php echo $base_url; ?>/index.php?page=login" class="auth-link">Login/Register</a>
                </div>
            <?php else: ?>
                <div class="auth-actions state-logged-in">
                    <div class="user-menu-left">
                        <a href="<?php echo $base_url; ?>/index.php?page=profile" class="auth-link">Profile</a>
                        <a href="<?php echo $base_url; ?>/index.php?page=settings" class="auth-link">Settings</a>
                        <a href="<?php echo $base_url; ?>/index.php?page=management" class="auth-link">Manage</a>
                    </div>
                    <div class="user-menu-right">
                        <a href="<?php echo $base_url; ?>/index.php?page=logout" class="auth-link logout-trigger">Logout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>
