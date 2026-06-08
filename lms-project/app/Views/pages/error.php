<main class="content-container error-page-canvas">
    <div class="error-inner-wrapper text-center">

        <div class="error-logo-container">
            <img src="imgs/MMC_logo.png" class="error-brand-seal" alt="Marinduque Midwest College Seal 1945">
        </div>

        <h1 class="error-main-title"><?php echo htmlspecialchars(strtoupper($error_message)); ?></h1>

        <div class="error-return-wrapper">
            <a href="index.php?page=home" class="error-return-link">Return to Home</a>
        </div>

    </div>
</main>
