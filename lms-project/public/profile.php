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

$page_title = "User Profile";
include_once 'includes/header.php';

/* ==========================================================================
    DYNAMIC SOURCE MATRIX (Extracting Profile and Financial Details)
   ========================================================================== */
$current_user_id = $_SESSION['user_id'];

// Default structured fallbacks matching the mockup design schema
$user_profile = [
    'name'             => "User",
    'role'             => $_SESSION['role'] ?? "student",
    'joined_date'      => "N/A",
    'username'         => $_SESSION['username'] ?? "username",
    'id_number'        => "N/A", // Will display Student ID for students, User ID for staff
    'email'            => "N/A",
    'course'           => "N/A",
    'contact'          => "N/A",
    'profile_picture'  => null, // Kept for UI asset fallback handling
    'total_txns'       => 0,
    'times_fined'      => 0,
    'total_fines_paid' => "PHP 0.00"
];

// Execute actual database queries using PDO
$host = 'localhost';
$db   = 'lms_project'; // Corrected database context
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Fetch core user credentials combined with matching profiles from borrowers table
    $query = "SELECT u.id AS user_id, u.username, u.email, u.role, u.created_at,
                     b.id AS borrower_id, b.student_id, b.name AS borrower_name, b.course, b.contact
              FROM users u
              LEFT JOIN borrowers b ON u.id = b.user_id
              WHERE u.id = :id LIMIT 1";

    $u_stmt = $pdo->prepare($query);
    $u_stmt->execute(['id' => $current_user_id]);
    $db_user = $u_stmt->fetch();

    if ($db_user) {
        $user_profile['username']    = $db_user['username'];
        $user_profile['role']        = $db_user['role'];
        $user_profile['email']       = $db_user['email'];
        $user_profile['joined_date'] = date('m/d/Y', strtotime($db_user['created_at']));

        // Differentiate formatting logic for Students vs Management Staff
        if (!empty($db_user['borrower_id'])) {
            $user_profile['name']      = $db_user['borrower_name'];
            $user_profile['id_number'] = $db_user['student_id']; // Displays school issued Student ID
            $user_profile['course']    = $db_user['course'] ?: "N/A";
            $user_profile['contact']   = $db_user['contact'] ?: "N/A";

            $borrower_id = $db_user['borrower_id'];

            // Compute Live Transaction counts linked via borrower profile keys
            $t_stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM transactions WHERE borrower_id = :borrower_id");
            $t_stmt->execute(['borrower_id' => $borrower_id]);
            $user_profile['total_txns'] = $t_stmt->fetch()['total'];

            // Compute accurate fine occurrences and sum total paid collections
            $f_query = "SELECT COUNT(f.id) AS total_fined,
                               SUM(CASE WHEN f.paid = 1 THEN f.amount ELSE 0.00 END) AS total_paid
                        FROM fines f
                        INNER JOIN transactions t ON f.transaction_id = t.id
                        WHERE t.borrower_id = :borrower_id";

            $f_stmt = $pdo->prepare($f_query);
            $f_stmt->execute(['borrower_id' => $borrower_id]);
            $fine_data = $f_stmt->fetch();

            if ($fine_data) {
                $user_profile['times_fined']      = $fine_data['total_fined'] ?? 0;
                $user_profile['total_fines_paid'] = "PHP " . number_format((float)($fine_data['total_paid'] ?? 0.00), 2);
            }
        } else {
            // Admin/Librarian fallback styling assignments
            $user_profile['name']      = ucfirst($db_user['username']);
            $user_profile['id_number'] = "Staff Acc #" . $db_user['user_id'];
            $user_profile['course']    = "Management Matrix";
        }
    }
} catch (\PDOException $e) {
    // Fail gracefully back onto defaults if connection drops
    $alert_message = "Error loading database profile metrics.";
}
?>

<main class="content-container profile-canvas">
    <div class="profile-inner-wrapper">

        <h1 class="profile-main-title">PROFILE</h1>

        <div class="profile-upper-deck">

            <div class="profile-media-box">
                <?php if (!empty($user_profile['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user_profile['profile_picture']); ?>"
                         alt="Profile picture" class="profile-avatar-fluid">
                <?php else: ?>
                    <div class="profile-avatar-fallback">
                        <div class="fallback-vector-head"></div>
                        <div class="fallback-vector-torso"></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-bio-data-block">
                <div class="bio-header-row">
                    <h2 class="profile-user-headline">
                        <?php echo htmlspecialchars($user_profile['name']); ?><br>
                        <span class="profile-role-subtext">(<?php echo htmlspecialchars($user_profile['role']); ?>)</span>
                    </h2>
                    <a href="settings.php" class="profile-action-btn border-capsule">Settings</a>
                </div>

                <div class="bio-grid-matrix">
                    <div class="matrix-row">
                        <span class="matrix-key">Joined:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['joined_date']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Username:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['username']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">ID Number:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['id_number']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Email:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['email']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Course:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['course']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Contact:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['contact']); ?></span>
                    </div>
                </div>
            </div>

        </div>

        <hr class="profile-section-divider">

        <div class="profile-lower-deck">
            <div class="metrics-stack">
                <div class="matrix-row text-wide">
                    <span class="matrix-key bold-label">Transactions:</span>
                    <span class="matrix-val numeric-node"><?php echo (int)$user_profile['total_txns']; ?></span>
                </div>
                <div class="matrix-row text-wide">
                    <span class="matrix-key bold-label">Fined:</span>
                    <span class="matrix-val numeric-node"><?php echo (int)$user_profile['times_fined']; ?></span>
                </div>
                <div class="matrix-row text-wide">
                    <span class="matrix-key bold-label">Total fines paid:</span>
                    <span class="matrix-val numeric-node"><?php echo htmlspecialchars($user_profile['total_fines_paid']); ?></span>
                </div>
            </div>

            <?php if (in_array($user_profile['role'], ['admin', 'librarian'])): ?>
                <div class="profile-management-trigger-container">
                    <a href="management.php" class="profile-action-btn border-capsule">Management</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
