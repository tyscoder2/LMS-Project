<main class="content-container">

    <section class="hero-headline-section">
        <h1 class="hero-title">CONTACT US</h1>
        <div class="hero-banner-image">
            <img src="imgs/MMC_contact_banner.jpg" alt="LMS Desk Workflow Banner">
        </div>
    </section>

    <section class="form-intro-container text-center">
        <p class="lead-text">Contact us with the form below and we'll get back to you as soon as possible. Thank you!</p>
    </section>

    <section class="contact-form-section bg-dusty-rose">
        <div class="form-inner-wrapper">
            <h2 class="form-block-subtitle">FORM</h2>

            <?php if (!empty($alert_message)): ?>
                <div class="form-alert alert-<?php echo $alert_type; ?>">
                    <p><?php echo $alert_message; ?></p>
                </div>
            <?php endif; ?>

            <form action="index.php?page=contact" method="POST" class="lms-native-form">
                <div class="form-flex-columns">

                    <div class="form-column fields-stack-left">
                        <div class="form-control-group">
                            <input type="text" name="first_name" placeholder="First Name" required
                                   value="<?php echo (!empty($first_name) && $alert_type !== 'success') ? htmlspecialchars($first_name) : ''; ?>">
                        </div>

                        <div class="form-control-group">
                            <input type="text" name="last_name" placeholder="Last Name" required
                                   value="<?php echo (!empty($last_name) && $alert_type !== 'success') ? htmlspecialchars($last_name) : ''; ?>">
                        </div>

                        <div class="form-control-group">
                            <input type="email" name="email" placeholder="Email" required
                                   value="<?php echo (!empty($email) && $alert_type !== 'success') ? htmlspecialchars($email) : ''; ?>">
                        </div>

                        <div class="form-control-group custom-select-arrow">
                            <select name="subject_choice" required>
                                <option value="default" disabled <?php echo empty($subject_opt) ? 'selected' : ''; ?>>What is your message about?</option>
                                <option value="Query" <?php echo ($subject_opt === "Query") ? 'selected' : ''; ?>>Query</option>
                                <option value="Suggestion" <?php echo ($subject_opt === "Suggestion") ? 'selected' : ''; ?>>Suggestion</option>
                                <option value="Report" <?php echo ($subject_opt === "Report") ? 'selected' : ''; ?>>Report</option>
                                <option value="Other" <?php echo ($subject_opt === "Other") ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-column field-textarea-right">
                        <div class="form-control-group height-100">
                            <textarea name="message" placeholder="Your message" required><?php echo (!empty($user_message) && $alert_type !== 'success') ? htmlspecialchars($user_message) : ''; ?></textarea>
                        </div>
                    </div>

                </div>

                <div class="form-action-row-centered">
                    <button type="submit" class="form-submit-btn-classic">Send</button>
                </div>
            </form>
        </div>
    </section>

</main>
