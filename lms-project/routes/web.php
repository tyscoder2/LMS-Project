<?php
// routes/web.php

// Core Application Core Autoloader Fallbacks
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/BookController.php';
require_once __DIR__ . '/../app/Controllers/BorrowerController.php';
require_once __DIR__ . '/../app/Controllers/CatalogController.php';
require_once __DIR__ . '/../app/Controllers/ContactController.php';
require_once __DIR__ . '/../app/Controllers/FineController.php';
require_once __DIR__ . '/../app/Controllers/ManagementController.php';
require_once __DIR__ . '/../app/Controllers/ProfileController.php';
require_once __DIR__ . '/../app/Controllers/ReservationController.php';
require_once __DIR__ . '/../app/Controllers/SettingsController.php';
require_once __DIR__ . '/../app/Controllers/SitemapController.php';
require_once __DIR__ . '/../app/Controllers/TransactionController.php';

/**
 * Capture environment metrics for route determination processing
 */
$page   = $_GET['page'] ?? 'search';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch (true) {

    // --- M1: User Authentication Routes ---
    case ($page === 'login'):
        $controller = new AuthController($db);
        $method === 'POST' ? $controller->authenticate($_POST) : $controller->showLoginForm();
        break;

    case ($page === 'logout'):
        $controller = new AuthController($db);
        $controller->terminateSession();
        break;

    case ($page === 'register'):
        $controller = new AuthController($db);
        $method === 'POST' ? $controller->register($_POST) : $controller->showRegisterForm();
        break;


    // --- M2 & M6: Search Engine, Book Catalog & Reports ---
    case ($page === 'search'):
        // Uses dedicated CatalogController for optimized public/student resource mapping
        $controller = new CatalogController($db);
        $controller->renderDiscoveryCanvas($_GET['search'] ?? '', $_GET['category'] ?? '');
        break;

    case ($page === 'books'):
        // BookController handles strict administrative ledger maintenance actions (CRUD)
        $controller = new BookController($db);
        if ($method === 'POST') {
            isset($_POST['action_delete']) ? $controller->destroy($_POST['id']) : $controller->store($_POST);
        } else {
            $controller->index($_GET['search'] ?? '');
        }
        break;


    // --- M3: Borrower Profile Management ---
    case ($page === 'borrowers'):
        $controller = new BorrowerController($db);
        $method === 'POST' ? $controller->store($_POST) : $controller->index();
        break;

    case ($page === 'profile'):
        $controller = new ProfileController($db);
        $method === 'POST' ? $controller->updatePersonalInformation($_POST) : $controller->viewSummaryCard();
        break;


    // --- M4: Circulation & Transaction Operations ---
    case ($page === 'transactions'):
        $controller = new TransactionController($db);
        $viewData = $controller->index(); // Evaluates POST requests for action_return_book internally
        break;

    case ($page === 'returned' && $method === 'GET'):
        $controller = new TransactionController($db);
        $viewData = $controller->returnConfirmation();
        break;

    case ($page === 'borrowed' && $method === 'GET'):
        $controller = new TransactionController($db);
        $viewData = $controller->borrowConfirmation();
        break;


    // --- M5: Holds, Reservations & Fines Ledger ---
    case ($page === 'reservations'):
        $controller = new ReservationController($db);
        $method === 'POST' ? $controller->createHoldRequest($_POST) : $controller->displayUserQueue();
        break;

    case ($page === 'fines'):
        $controller = new FineController($db);
        $method === 'POST' ? $controller->processPaymentAction($_POST) : $controller->auditOutstandingBalances();
        break;


    // --- Back-Office Global Infrastructure Layouts ---
    case ($page === 'management' || $page === 'dashboard'):
        $controller = new ManagementController($db);
        $controller->renderAnalyticalOverviewMetrics(); // Generates reports on circulation & inventory counts
        break;

    case ($page === 'settings'):
        $controller = new SettingsController($db);
        $method === 'POST' ? $controller->saveConfigurationOverrides($_POST) : $controller->displaySystemPanels();
        break;

    case ($page === 'contact'):
        $controller = new ContactController($db);
        $method === 'POST' ? $controller->sendSupportTicket($_POST) : $controller->displayContactForm();
        break;

    case ($page === 'sitemap'):
        $controller = new SitemapController($db);
        $controller->generateIndexMapXML();
        break;


    // --- Fallback Routing Protection Block ---
    default:
        header("Location: index.php?page=search");
        exit();
}
