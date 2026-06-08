<main class="content-container login-page-canvas">
    <div class="login-inner-wrapper text-center">

        <div class="login-logo-container">
            <img src="imgs/MMC_logo.png" class="login-brand-seal" alt="Marinduque Midwest College Seal 1945">
        </div>

        <h1 class="login-main-title">LOGIN</h1>

        <?php if (!empty($alert_message)): ?>
            <div class="form-alert alert-<?php echo $alert_type; ?>" style="max-width: 420px; margin: 0 auto 20px auto;">
                <p><?php echo htmlspecialchars($alert_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="index.php?page=login" method="POST" class="login-native-form">

            <div class="login-control-group">
                <input type="text" name="username" placeholder="Username" required autocomplete="username"
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>

            <div class="login-control-group">
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            </div>

            <div class="login-buttons-action-row">
                <button type="submit" name="submit_login" class="btn-login-action">Login</button>
                <a href="index.php?page=register" class="btn-login-action link-style-btn">Register</a>
            </div>

            <div class="forgot-password-wrapper">
                <a href="index.php?page=contact" class="forgot-password-link">Forgot password</a>
            </div>

        </form>

    </div>
</main>
