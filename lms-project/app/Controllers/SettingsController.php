<?php

class SettingsController {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    /**
     * Intercepts post verification modifications and structures input parameters
     */
    public function index() {
        // Enforce Authentication Guard directly at the application routing level
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $current_user_id = $_SESSION['user_id'];
        $error_msg = "";

        try {
            // FETCH CURRENT VALUES VIA RELATIONAL CROSS-JOIN FOR INPUT POPULATION
            $u_stmt = $this->db->prepare("
                SELECT u.id, u.username, u.email, u.role, u.created_at,
                       b.name AS full_name, b.student_id, b.course, b.contact
                FROM users u
                LEFT JOIN borrowers b ON u.id = b.user_id
                WHERE u.id = :id
                LIMIT 1
            ");
            $u_stmt->execute(['id' => $current_user_id]);
            $user_data = $u_stmt->fetch(PDO::FETCH_ASSOC);

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

                // 1. Handle Multipart Profile Image Upload (Saved inside the web public context)
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
                $dup_stmt = $this->db->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id LIMIT 1");
                $dup_stmt->execute([
                    'username' => $input_username,
                    'email'    => $input_email,
                    'id'       => $current_user_id
                ]);

                // 3. Uniqueness Validation Check (Student Alphanumeric ID)
                $dup_student = false;
                if ($user_data['role'] === 'student' && !empty($input_student_id)) {
                    $stud_stmt = $this->db->prepare("SELECT id FROM borrowers WHERE student_id = :student_id AND user_id != :id LIMIT 1");
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
                    $this->db->beginTransaction();

                    // Update core identity parameters inside USERS table
                    $user_up = $this->db->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
                    $user_up->execute([
                        'username' => $input_username,
                        'email'    => $input_email,
                        'id'       => $current_user_id
                    ]);

                    // Update profile information inside BORROWERS table
                    if ($user_data['role'] === 'student') {
                        $borr_up = $this->db->prepare("UPDATE borrowers SET student_id = :student_id, course = :course, contact = :contact WHERE user_id = :id");
                        $borr_up->execute([
                            'student_id' => $input_student_id,
                            'course'     => $input_course,
                            'contact'    => $input_contact,
                            'id'         => $current_user_id
                        ]);
                    }

                    $this->db->commit();

                    // Direct programmatic transfer upon successful save action
                    header("Location: index.php?page=profile");
                    exit();
                }
            }

        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $error_msg = "Database Synchronization Interrupted: " . $e->getMessage();
        }

        // Map operational variables out to structural configuration scope arrays
        return [
            'user_data'      => $user_data ?? [],
            'error_msg'      => $error_msg,
            'display_name'   => $user_data['full_name'] ?? ucfirst($user_data['username'] ?? 'User'),
            'display_role'   => $user_data['role'] ?? "student",
            'display_joined' => isset($user_data['created_at']) ? date('m/d/Y', strtotime($user_data['created_at'])) : "MM/DD/YYYY",
            'display_id'     => (isset($user_data['role']) && $user_data['role'] === 'student') ? $user_data['id'] : "Staff Acc #" . ($user_data['id'] ?? ''),
            'profile_pic'    => $_SESSION['profile_pic'] ?? null
        ];
    }
}
