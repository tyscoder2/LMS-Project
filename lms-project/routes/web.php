<?php
// routes/web.php

// Require Controllers (Acting as a basic autoloader for now)
require_once '../app/Controllers/BookController.php';
require_once '../app/Controllers/BorrowerController.php';
require_once '../app/Controllers/TransactionController.php';

// Match URI and HTTP Method to respective controller actions
switch (true) {
    
    // --- Book Catalog Routes ---
    case ($uri === '/books' && $method === 'GET'):
        $controller = new BookController($db);
        $controller->index($_GET['search'] ?? '');
        break;

    case ($uri === '/books' && $method === 'POST'):
        $controller = new BookController($db);
        $controller->store($_POST);
        break;


    // --- Borrower Management Routes ---
    case ($uri === '/borrowers' && $method === 'GET'):
        $controller = new BorrowerController($db);
        $controller->index();
        break;

    case ($uri === '/borrowers' && $method === 'POST'):
        $controller = new BorrowerController($db);
        $controller->store($_POST);
        break;


    // --- Circulation / Transaction Routes ---
    case ($uri === '/transactions/borrow' && $method === 'POST'):
        $controller = new TransactionController($db);
        $controller->borrow($_POST['book_id'], $_POST['borrower_id']);
        break;

    case ($uri === '/transactions/return' && $method === 'POST'):
        $controller = new TransactionController($db);
        $controller->processReturn($_POST['transaction_id']);
        break;


    // --- Fallback / Default Route ---
    default:
        // Logically send users to the books catalog if path doesn't match
        header("Location: /books");
        exit;
}
?>