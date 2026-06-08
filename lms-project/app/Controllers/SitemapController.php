<?php

class SitemapController {

    /**
     * Iterates through access controls and structures balanced column slices
     */
    public function index() {
        // Core Security Extraction Matrix
        $is_logged_in = !empty($_SESSION['user_id']);
        $user_role    = strtolower($_SESSION['role'] ?? '');
        $current_page = $_GET['page'] ?? 'home';

        // 1. Establish core baseline paths available to all visitors
        $sitemap_links = [
            ['url' => 'index.php?page=home', 'label' => 'Home', 'key' => 'home'],
            ['url' => 'index.php?page=about', 'label' => 'About', 'key' => 'about'],
            ['url' => 'index.php?page=contact', 'label' => 'Contact', 'key' => 'contact'],
            ['url' => 'index.php?page=search', 'label' => 'Search', 'key' => 'search'],
            ['url' => 'index.php?page=faq', 'label' => 'FAQ', 'key' => 'faq'],
            ['url' => 'index.php?page=sitemap', 'label' => 'Site Map', 'key' => 'sitemap']
        ];

        // 2. Evaluate identity and stack contextual authorized links
        if ($is_logged_in) {
            $sitemap_links[] = ['url' => 'index.php?page=profile', 'label' => 'Profile', 'key' => 'profile'];
            $sitemap_links[] = ['url' => 'index.php?page=settings', 'label' => 'Settings', 'key' => 'settings'];
            $sitemap_links[] = ['url' => 'index.php?page=management', 'label' => 'Management', 'key' => 'management'];
            $sitemap_links[] = ['url' => 'index.php?page=transactions', 'label' => 'Transactions', 'key' => 'transactions'];
            $sitemap_links[] = ['url' => 'index.php?page=fines', 'label' => 'Fines', 'key' => 'fines'];
            $sitemap_links[] = ['url' => 'index.php?page=reservations', 'label' => 'Reservations', 'key' => 'reservations'];

            // Librarian/Staff Access Rules
            if ($user_role === 'librarian' || $user_role === 'admin') {
                $sitemap_links[] = ['url' => 'index.php?page=books', 'label' => 'Books', 'key' => 'books'];
            }

            // Super Admin Authorization Rules
            if ($user_role === 'admin') {
                $sitemap_links[] = ['url' => 'index.php?page=users', 'label' => 'Users', 'key' => 'users'];
            }
        }

        // 3. Mathematical Balancing Engine Calculations
        $total_items = count($sitemap_links);
        $items_per_column = ceil($total_items / 3);

        // Splice dataset down into structured columns
        $sitemap_columns = array_chunk($sitemap_links, $items_per_column);

        // Return scope configurations as payload array
        return [
            'sitemap_columns' => $sitemap_columns,
            'current_page'    => $current_page
        ];
    }
}
