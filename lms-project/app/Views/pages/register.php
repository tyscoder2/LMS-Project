<main class="content-container register-page-canvas">
    <div class="register-inner-wrapper text-center">

        <div class="register-logo-container">
            <img src="imgs/MMC_logo.png" class="register-brand-seal" alt="Marinduque Midwest College Seal 1945">
        </div>

        <h1 class="register-main-title">REGISTER</h1>

        <?php if (!empty($alert_message)): ?>
            <div class="form-alert alert-<?php echo $alert_type; ?>" style="max-width: 420px; margin: 0 auto 20px auto;">
                <p><?php echo htmlspecialchars($alert_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="index.php?page=register" method="POST" class="register-native-form">

            <div class="register-control-group">
                <input type="text" name="username" placeholder="Username" required autocomplete="username"
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>

            <div class="register-control-group">
                <input type="password" name="password" placeholder="Password" required autocomplete="new-password">
            </div>

            <div class="register-control-group">
                <input type="email" name="email" placeholder="Email" required autocomplete="email"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <hr style="border: 0; border-top: 1px dashed #e2e8f0; margin: 20px 0;">

            <div class="register-control-group">
                <input type="text" name="name" placeholder="Full Name" required autocomplete="name"
                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>

            <div class="register-control-group">
                <input type="text" name="student_id" placeholder="Student ID Number" required
                       value="<?php echo isset($student_id) ? htmlspecialchars($student_id) : ''; ?>">
            </div>

            <div class="register-control-group">
                <input type="text" name="contact_number" placeholder="Contact Number" required autocomplete="tel"
                       value="<?php echo isset($contact) ? htmlspecialchars($contact) : ''; ?>">
            </div>

            <div class="register-control-group dropdown-arrow-wrapper">
                <select name="course" required>
                    <option value="" disabled selected hidden>Course</option>
                    <option value="BSCS" <?php echo (isset($course) && $course === 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                    <option value="BEEd" <?php echo (isset($course) && $course === 'BEEd') ? 'selected' : ''; ?>>BEEd</option>
                    <option value="BSEd" <?php echo (isset($course) && $course === 'BSEd') ? 'selected' : ''; ?>>BSEd</option>
                </select>
            </div>

            <div class="register-buttons-action-row">
                <button type="submit" name="submit_registration" class="btn-register-action">Register</button>
            </div>

        </form>

    </div>
</main>
