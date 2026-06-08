<?php

class ManagementController {

    /**
     * Protects the workspace route and handles user role extraction matrix
     */
    public function index() {
        // Initialize fallback session safety nets
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Programmatic Auth Route Interceptor Guard
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        // Capture safe user role value context
        $user_role = $_SESSION['role'] ?? 'student';

        // Deliver unified scope structure back to the orchestrator layer
        return [
            'user_role' => strtolower($user_role)
        ];
    }
}
