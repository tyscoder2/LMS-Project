<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Site Map";
include_once 'includes/header.php';

// Security Role Extraction
$is_logged_in = !empty($_SESSION['user_id']);
$user_role    = strtolower($_SESSION['role'] ?? '');

/* ==========================================================================
   DYNAMIC LINK MATRIX GENERATOR
   ========================================================================== */
// Core base links accessible to all visitors
$sitemap_links = [
    ['url' => 'index.php', 'label' => 'Home'],
    ['url' => 'about.php', 'label' => 'About'],
    ['url' => 'contact.php', 'label' => 'Contact'],
    ['url' => 'search.php', 'label' => 'Search'],
    ['url' => 'faq.php', 'label' => 'FAQ'],
    ['url' => 'sitemap.php', 'label' => 'Site Map']
];

// Contextual authorization routes
if ($is_logged_in) {
    // Standard authenticated routes accessible to all profiles
    $sitemap_links[] = ['url' => 'profile.php', 'label' => 'Profile'];
    $sitemap_links[] = ['url' => 'settings.php', 'label' => 'Settings'];
    $sitemap_links[] = ['url' => 'management.php', 'label' => 'Management'];
    $sitemap_links[] = ['url' => 'transactions.php', 'label' => 'Transactions'];
    $sitemap_links[] = ['url' => 'fines.php', 'label' => 'Fines'];
    $sitemap_links[] = ['url' => 'reservations.php', 'label' => 'Reservations'];

    // Librarian tier additions (Admins inherit management actions)
    if ($user_role === 'librarian' || $user_role === 'admin') {
        $sitemap_links[] = ['url' => 'books.php', 'label' => 'Books'];
    }

    // Super Administrator tier additions
    if ($user_role === 'admin') {
        $sitemap_links[] = ['url' => 'users.php', 'label' => 'Users'];
    }
}

/* ==========================================================================
   3-COLUMN MATHEMATICAL BALANCING ENGINE
   ========================================================================== */
$total_items = count($sitemap_links);
$items_per_column = ceil($total_items / 3);

// Slices data sequentially to maintain standard alphabetical/top-to-bottom reading orders
$sitemap_columns = array_chunk($sitemap_links, $items_per_column);
?>

<main class="content-container sitemap-canvas">
    <div class="sitemap-inner-wrapper">

        <h1 class="sitemap-main-title text-center">SITE MAP</h1>

        <div class="sitemap-grid-container">

            <?php foreach ($sitemap_columns as $column_links): ?>
                <div class="sitemap-column">
                    <ul class="sitemap-list">
                        <?php foreach ($column_links as $link): ?>
                            <?php
                                // Detect if processing the current active viewport target
                                $is_active = (basename($_SERVER['SCRIPT_NAME']) === $link['url']);
                                $class_assignment = $is_active ? 'sitemap-link active-page' : 'sitemap-link';
                            ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>" class="<?php echo $class_assignment; ?>">
                                    <?php echo htmlspecialchars($link['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
