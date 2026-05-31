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

$page_title = "Account Settings";
include_once 'includes/header.php';

$current_user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Database configuration settings
$host = 'localhost';
$db   = 'lms_project';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // FETCH CURRENT VALUES VIA RELATIONAL CROSS-JOIN FOR INPUT POPULATION
    $u_stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role, u.created_at,
               b.name AS full_name, b.student_id, b.course, b.contact
        FROM users u
        LEFT JOIN borrowers b ON u.id = b.user_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $u_stmt->execute(['id' => $current_user_id]);
    $user_data = $u_stmt->fetch();

    if (!$user_data) {
        die("User record context missing.");
    }

    /* ==========================================================================
        POST REQUEST PROCESSOR (MANAGES RE-ROUTING AND UPDATES)
       ========================================================================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input_username   = trim($_POST['username'] ?? '');
        $input_email      = trim($_POST['email'] ?? '');
        $input_student_id = trim($_POST['student_id'] ?? '');
        $input_course     = trim($_POST['course'] ?? '');
        $input_contact    = trim($_POST['contact'] ?? '');

        // 1. Handle Multipart Profile Image Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['profile_pic']['tmp_name'];
            $file_name     = $_FILES['profile_pic']['name'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $upload_dir = 'uploads/profile_pics/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $new_file_name = "user_" . $current_user_id . "_" . time() . "." . $file_extension;
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    $_SESSION['profile_pic'] = $dest_path;
                }
            }
        }

        // 2. Uniqueness Validation Check (Core User Account Credentials)
        $dup_stmt = $pdo->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id LIMIT 1");
        $dup_stmt->execute([
            'username' => $input_username,
            'email'    => $input_email,
            'id'       => $current_user_id
        ]);

        // 3. Uniqueness Validation Check (Student Alphanumeric ID)
        $dup_student = false;
        if ($user_data['role'] === 'student' && !empty($input_student_id)) {
            $stud_stmt = $pdo->prepare("SELECT id FROM borrowers WHERE student_id = :student_id AND user_id != :id LIMIT 1");
            $stud_stmt->execute([
                'student_id' => $input_student_id,
                'id'         => $current_user_id
            ]);
            if ($stud_stmt->fetch()) {
                $dup_student = true;
            }
        }

        if ($dup_stmt->fetch()) {
            $error_msg = "Username or Email address is already taken by another account.";
        } elseif ($dup_student) {
            $error_msg = "The specified Student ID is already assigned to another student.";
        } else {
            // 4. Execute Multi-Table Safe Transaction Pipeline
            $pdo->beginTransaction();

            // Update core identity parameters inside USERS table
            $user_up = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
            $user_up->execute([
                'username' => $input_username,
                'email'    => $input_email,
                'id'       => $current_user_id
            ]);

            // Update profile information inside BORROWERS table (Including editable Student ID)
            if ($user_data['role'] === 'student') {
                $borr_up = $pdo->prepare("UPDATE borrowers SET student_id = :student_id, course = :course, contact = :contact WHERE user_id = :id");
                $borr_up->execute([
                    'student_id' => $input_student_id,
                    'course'     => $input_course,
                    'contact'    => $input_contact,
                    'id'         => $current_user_id
                ]);
            }

            $pdo->commit();

            // Direct programmatic transfer upon successful save action
            header("Location: profile.php");
            exit();
        }
    }

} catch (\PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_msg = "Database Synchronization Interrupted: " . $e->getMessage();
}

// Map parameters out to structural presentation targets
$display_name   = $user_data['full_name'] ?? ucfirst($user_data['username']);
$display_role   = $user_data['role'] ?? "student";
$display_joined = isset($user_data['created_at']) ? date('m/d/Y', strtotime($user_data['created_at'])) : "MM/DD/YYYY";
$display_id     = ($user_data['role'] === 'student') ? $user_data['id'] : "Staff Acc #" . $user_data['id'];
$profile_pic    = $_SESSION['profile_pic'] ?? null;
?>

<main class="content-container profile-canvas">
    <div class="profile-inner-wrapper">

        <h1 class="profile-main-title">SETTINGS</h1>

        <?php if (!empty($error_msg)): ?>
            <div class="form-alert alert-error" style="max-width: 500px; margin: 0 auto 20px auto; color: red; text-align: center;">
                <p><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        <?php endif; ?>

        <form action="settings.php" method="POST" enctype="multipart/form-data" class="settings-form-wrapper">

            <div class="profile-upper-deck">

                <div class="settings-media-stack">
                    <div class="profile-media-box">
                        <?php if (!empty($profile_pic)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>?t=<?php echo time(); ?>"
                                 alt="Profile picture" class="profile-avatar-fluid" id="avatar-preview">
                        <?php else: ?>
                            <div class="profile-avatar-fallback" id="fallback-graphics">
                                <div class="fallback-vector-head"></div>
                                <div class="fallback-vector-torso"></div>
                            </div>
                            <img src="" alt="Preview image" class="profile-avatar-fluid hidden" id="avatar-preview">
                        <?php endif; ?>
                    </div>

                    <div class="file-upload-trigger-container">
                        <label for="file-upload-input" class="profile-action-btn border-capsule text-center pointer-node">Upload pic</label>
                        <input type="file" name="profile_pic" id="file-upload-input" accept="image/*" class="hidden-input-node" onchange="previewImageFile(this)">
                    </div>
                </div>

                <div class="profile-bio-data-block">
                    <div class="bio-header-row">
                        <h2 class="profile-user-headline">
                            <?php echo htmlspecialchars($display_name); ?><br>
                            <span class="profile-role-subtext">(<?php echo htmlspecialchars($display_role); ?>)</span>
                        </h2>

                        <div class="settings-btn-vertical-stack">
                            <button type="submit" class="profile-action-btn border-capsule execution-node">Save</button>
                            <a href="profile.php" class="profile-action-btn border-capsule fallback-cancel-node">Cancel</a>
                        </div>
                    </div>

                    <div class="bio-grid-matrix">

                        <div class="matrix-row">
                            <span class="matrix-key">Joined:</span>
                            <span class="matrix-val static-text-node"><?php echo htmlspecialchars($display_joined); ?></span>
                        </div>

                        <div class="matrix-row">
                            <label for="input-username" class="matrix-key">Username:</label>
                            <input type="text" name="username" id="input-username" class="settings-interactive-input"
                                   value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
                        </div>

                        <div class="matrix-row">
                            <span class="matrix-key">ID Number:</span>
                            <span class="matrix-val static-text-node"><?php echo htmlspecialchars($display_id); ?></span>
                        </div>

                        <?php if ($display_role === 'student'): ?>
                            <div class="matrix-row">
                                <label for="input-student-id" class="matrix-key">Student ID:</label>
                                <input type="text" name="student_id" id="input-student-id" class="settings-interactive-input"
                                       value="<?php echo htmlspecialchars($user_data['student_id'] ?? ''); ?>" required>
                            </div>
                        <?php endif; ?>

                        <div class="matrix-row">
                            <label for="input-email" class="matrix-key">Email:</label>
                            <input type="email" name="email" id="input-email" class="settings-interactive-input"
                                   value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                        </div>

                        <?php if ($display_role === 'student'): ?>
                            <div class="matrix-row">
                                <label for="select-course" class="matrix-key">Course:</label>
                                <div class="custom-select-wrapper">
                                    <select name="course" id="select-course" class="settings-interactive-input dropdown-node">
                                        <option value="BSCS" <?php echo ($user_data['course'] ?? '') === 'BSCS' ? 'selected' : ''; ?>>BSCS</option>
                                        <option value="BEEd" <?php echo ($user_data['course'] ?? '') === 'BEEd' ? 'selected' : ''; ?>>BEEd</option>
                                        <option value="BSEd" <?php echo ($user_data['course'] ?? '') === 'BSEd' ? 'selected' : ''; ?>>BSEd</option>
                                    </select>
                                </div>
                            </div>

                            <div class="matrix-row">
                                <label for="input-contact" class="matrix-key">Contact:</label>
                                <input type="text" name="contact" id="input-contact" class="settings-interactive-input"
                                       value="<?php echo htmlspecialchars($user_data['contact'] ?? ''); ?>">
                            </div>
                        <?php else: ?>
                            <div class="matrix-row">
                                <span class="matrix-key">Context:</span>
                                <span class="matrix-val static-text-node">Management Account Matrix</span>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </form>

        <div class="settings-footer-disclaimer">
            <p class="disclaimer-text">Note: Changes to account credentials modify active authentication systems instantaneously.</p>
        </div>

    </div>
</main>

<script>
function previewImageFile(inputNode) {
    if (inputNode.files && inputNode.files[0]) {
        const fileReader = new FileReader();
        fileReader.onload = function (e) {
            const previewImg = document.getElementById('avatar-preview');
            const fallbackGraphic = document.getElementById('fallback-graphics');

            previewImg.src = e.target.result;
            previewImg.classList.remove('hidden');

            if (fallbackGraphic) {
                fallbackGraphic.style.display = 'none';
            }
        };
        fileReader.readAsDataURL(inputNode.files[0]);
    }
}
</script>

<?php include_once 'includes/footer.php'; ?>
