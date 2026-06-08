<?php
/* ==========================================================================
   GLOBAL SESSION & INITIALIZATION ENGINE
   ========================================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = (!empty($_SESSION['user_id']) && isset($_SESSION['role']));

/* ==========================================================================
   DYNAMIC BASE URL PATH TRAPPING PROTECTION
   ========================================================================== */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$current_script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

$public_folder_position = strpos($current_script_dir, '/public');
if ($public_folder_position !== false) {
    $clean_root_dir = substr($current_script_dir, 0, $public_folder_position + 7);
} else {
    $clean_root_dir = $current_script_dir;
}
$base_url = rtrim($protocol . $host . $clean_root_dir, '/');

/* ==========================================================================
   AUTHENTICATION ROUTE GUARD (Middleware Interceptor Pattern)
   ========================================================================== */
$page = $_GET['page'] ?? 'home';

if ($is_logged_in && ($page === 'login' || $page === 'register')) {
    echo "<script>
        alert('Already logged in. Please log out for these actions.');
        window.location.href = 'index.php?page=profile';
    </script>";
    exit();
}

/* ==========================================================================
   APPLICATION DECOUPLED MVC ROUTING DISPATCH MATRIX
   ========================================================================== */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/BookController.php';
require_once __DIR__ . '/../app/Controllers/FineController.php';
require_once __DIR__ . '/../app/Controllers/ContactController.php';
require_once __DIR__ . '/../app/Controllers/CatalogController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/ProfileController.php';
require_once __DIR__ . '/../app/Controllers/SettingsController.php';
require_once __DIR__ . '/../app/Controllers/SitemapController.php';
require_once __DIR__ . '/../app/Controllers/ManagementController.php';
require_once __DIR__ . '/../app/Controllers/BorrowerController.php';
require_once __DIR__ . '/../app/Controllers/ReservationController.php';
require_once __DIR__ . '/../app/Controllers/TransactionController.php';

$dbClass = new Database();
$pdoConn = $dbClass->connect();

$bookController        = new BookController($pdoConn);
$fineController        = new FineController($pdoConn);
$contactController     = new ContactController();
$catalogController     = new CatalogController($pdoConn);
$authController        = new AuthController($pdoConn);
$profileController     = new ProfileController($pdoConn);
$settingsController    = new SettingsController($pdoConn);
$sitemapController     = new SitemapController();
$managementController  = new ManagementController();
$borrowerController    = new BorrowerController($pdoConn);
$reservationController = new ReservationController($pdoConn);
$transactionController = new TransactionController($pdoConn);

switch ($page) {
    case 'profile':
        $viewPayload = $profileController->index();
        extract($viewPayload);
        $page_title  = "User Profile";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/profile.php';
        include_once 'includes/footer.php';
        break;

    case 'settings':
        $viewPayload = $settingsController->index();
        extract($viewPayload);
        $page_title  = "Account Settings";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/settings.php';
        include_once 'includes/footer.php';
        break;

    case 'sitemap':
        $viewPayload = $sitemapController->index();
        extract($viewPayload);
        $page_title  = "Site Map";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/sitemap.php';
        include_once 'includes/footer.php';
        break;

    case 'management':
        $viewPayload = $managementController->index();
        extract($viewPayload);
        $page_title  = "Management Control Panel";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/management.php';
        include_once 'includes/footer.php';
        break;

    case 'users':
        $viewPayload = $borrowerController->manageUsers();
        extract($viewPayload);
        $page_title = "User Records";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/borrowers/users.php';
        include_once 'includes/footer.php';
        break;

    case 'books':
        $viewPayload = $bookController->manageBooks();
        extract($viewPayload);
        $page_title = "Books and Material Management";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/books/manage.php';
        include_once 'includes/footer.php';
        break;

    case 'fines':
        $viewPayload = $fineController->index();
        extract($viewPayload);
        $page_title = "Fine Records";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/fines.php';
        include_once 'includes/footer.php';
        break;

    case 'reservations':
        $viewPayload = $reservationController->index();
        extract($viewPayload);
        $page_title = "Book Reservation Records";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/reservations.php';
        include_once 'includes/footer.php';
        break;

    case 'transactions':
        $viewPayload = $transactionController->index();
        extract($viewPayload);
        $page_title = "Transaction Records";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/transactions.php';
        include_once 'includes/footer.php';
        break;

    case 'returned': // Processes return log confirmations
        $viewPayload = $transactionController->returnConfirmation();
        extract($viewPayload);
        $page_title = "Book Drop-Off Complete";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/returned.php';
        include_once 'includes/footer.php';
        break;

    case 'borrowed': // Processes checkout and reservation confirmations
        $viewPayload = $transactionController->borrowConfirmation();
        extract($viewPayload);
        $page_title = $is_reservation ? "Book Reserved Confirmation" : "Book Borrowed Confirmation";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/borrowed.php';
        include_once 'includes/footer.php';
        break;

    case 'register':
        $viewPayload = $authController->register();
        extract($viewPayload);
        $page_title  = "Register";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/register.php';
        include_once 'includes/footer.php';
        break;

    case 'error':
        $viewPayload = $authController->error();
        extract($viewPayload);
        $page_title  = "Error Encountered";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/error.php';
        include_once 'includes/footer.php';
        break;

    case 'logout':
        $viewPayload = $authController->logout();
        extract($viewPayload);
        $page_title   = "Logged Out";
        $is_logged_in = false;

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/success.php';
        include_once 'includes/footer.php';
        break;

    case 'success':
        $viewPayload = $authController->success();
        extract($viewPayload);
        $page_title  = "Action Successful";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/success.php';
        include_once 'includes/footer.php';
        break;

    case 'login':
        $viewPayload = $authController->login();
        extract($viewPayload);
        $page_title = "Login";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/login.php';
        include_once 'includes/footer.php';
        break;

    case 'search':
        $viewPayload = $catalogController->index();
        extract($viewPayload);
        $page_title = "Books and Material Catalog";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/search.php';
        ?>
        <script src="<?php echo $base_url; ?>/js/search.js"></script>
        <?php
        include_once 'includes/footer.php';
        break;

    case 'contact':
        $viewPayload = $contactController->index();
        extract($viewPayload);
        $page_title  = "Contact Us";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/contact.php';
        include_once 'includes/footer.php';
        break;

    case 'about':
        $viewPayload = $bookController->about();
        extract($viewPayload);

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/books/about.php';
        include_once 'includes/footer.php';
        break;

    case 'faq':
        $viewPayload = $bookController->faq();
        extract($viewPayload);

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/pages/faq.php';
        ?>
        <script src="<?php echo $base_url; ?>/js/accordion.js"></script>
        <?php
        include_once 'includes/footer.php';
        break;

    case 'lrc':
    case 'forgot-password':
        $page_title = ucfirst($page);

        include_once 'includes/header.php';
        echo "<main class='content-container'><h2>" . $page_title . " Core Context Dashboard Layer Workspace</h2></main>";
        include_once 'includes/footer.php';
        break;

    case 'home':
    default:
        $viewPayload = $bookController->index();
        extract($viewPayload);
        $page_title = "Home";

        include_once 'includes/header.php';
        require_once __DIR__ . '/../app/Views/books/home.php';
        ?>
        <script src="<?php echo $base_url; ?>/js/gallery.js"></script>
        <?php
        include_once 'includes/footer.php';
        break;
}
?>
