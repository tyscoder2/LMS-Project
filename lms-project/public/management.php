<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user isn't authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Capture user role from session auth architecture
$user_role = $_SESSION['role'] ?? 'student';

// Defensive Route Guard: Base students have no administrative clearance
/* if ($user_role === 'student') {
    header("Location: profile.php");
    exit();
} */

$page_title = "Management Control Panel";
include_once 'includes/header.php';
?>

<main class="content-container manage-canvas">
    <div class="manage-inner-wrapper">

        <h1 class="manage-main-title">MANAGE:</h1>

        <section class="manage-section-group">
            <h2 class="manage-section-subtitle">STUDENT MANAGEMENT</h2>
            <div class="manage-cards-row">

                <a href="transactions.php" class="manage-dashboard-card card-terracotta">
                    <div class="card-vector-space">
                        <div class="icon-transactions-stack">
                            <div class="paper-layer sheet-back"></div>
                            <div class="paper-layer sheet-front">
                                <div class="paper-line"></div>
                                <div class="paper-line"></div>
                                <div class="paper-line"></div>
                                <div class="paper-line"></div>
                            </div>
                        </div>
                    </div>
                    <span class="manage-card-label">TRANSACTIONS</span>
                </a>

                <a href="fines.php" class="manage-dashboard-card card-indigo">
                    <div class="card-vector-space">
                        <div class="icon-fines-coins">
                            <div class="coin-layer coin-back"></div>
                            <div class="coin-layer coin-front"></div>
                        </div>
                    </div>
                    <span class="manage-card-label">FINES</span>
                </a>

                <a href="reservations.php" class="manage-dashboard-card card-red">
                    <div class="card-vector-space">
                        <div class="icon-reservations-paper">
                            <div class="paper-line short"></div>
                            <div class="paper-line wide"></div>
                            <div class="paper-line wide"></div>
                            <div class="paper-line mid"></div>
                        </div>
                    </div>
                    <span class="manage-card-label">RESERVATIONS</span>
                </a>

            </div>
        </section>

        <?php if (in_array($user_role, ['admin', 'librarian'])): ?>
            <section class="manage-section-group">
                <h2 class="manage-section-subtitle">LIBRARIAN MANAGEMENT</h2>
                <div class="manage-cards-row">

                    <a href="books.php" class="manage-dashboard-card card-gold">
                        <div class="card-vector-space">
                            <div class="inner-book-vector">
                                <div class="vector-circle"></div>
                                <div class="vector-divider"></div>
                            </div>
                        </div>
                        <span class="manage-card-label">BOOKS AND MATERIALS</span>
                    </a>

                </div>
            </section>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
            <section class="manage-section-group">
                <h2 class="manage-section-subtitle">ADMINISTRATIVE MANAGEMENT</h2>
                <div class="manage-cards-row">

                    <a href="users.php" class="manage-dashboard-card card-green">
                        <div class="card-vector-space">
                            <div class="profile-avatar-fallback">
                                <div class="fallback-vector-head"></div>
                                <div class="fallback-vector-torso"></div>
                            </div>
                        </div>
                        <span class="manage-card-label">USERS</span>
                    </a>

                </div>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
