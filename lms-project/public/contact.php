<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Contact Us";
include_once 'includes/header.php';

// Form Handling Processing Engine
$alert_message = "";
$alert_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Sanitize and catch user inputs
    $first_name   = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $last_name    = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email        = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $subject_opt  = filter_input(INPUT_POST, 'subject_choice', FILTER_SANITIZE_SPECIAL_CHARS);
    $user_message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

    // 2. Strict Backend Validation: Ensure name, email, query, and message are completely filled and valid
    if (empty($first_name) || empty($last_name)) {
        $alert_message = "The Name field is required.";
        $alert_type = "error";
    } elseif (!$email) {
        $alert_message = "A valid Email address is required.";
        $alert_type = "error";
    } elseif (empty($subject_opt) || $subject_opt === "default") {
        $alert_message = "Selecting a Query topic is required.";
        $alert_type = "error";
    } elseif (empty($user_message)) {
        $alert_message = "The Message field is required.";
        $alert_type = "error";
    } else {
        // 3. Setup core email settings if all requirements pass
        $to_email = "lalafoodcustomerservice@gmail.com";
        $email_subject = "MMC LMS Contact Form: " . $subject_opt;

        // Assemble clean message package body
        $email_body = "You have received a new message from the MMC LMS contact form.\n\n".
                      "Name: " . $first_name . " " . $last_name . "\n".
                      "Email Address: " . $email . "\n".
                      "Topic/Query: " . $subject_opt . "\n\n".
                      "Message Details:\n" . $user_message . "\n";

        // Construct compliant mail transmission headers
        $headers = "From: webmaster@mmc-lms.com\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // 4. Dispatch Email natively
        if (mail($to_email, $email_subject, $email_body, $headers)) {
            $alert_message = "Your message has been sent successfully!";
            $alert_type = "success";
        } else {
            // Fallback flag if your local environment doesn't have an outbound SMTP mail server configured
            $alert_message = "Form validated successfully! (Mail delivery skipped on local server environment).";
            $alert_type = "info";
        }
    }
}
?>

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

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="lms-native-form">
                <div class="form-flex-columns">

                    <div class="form-column fields-stack-left">
                        <div class="form-control-group">
                            <input type="text" name="first_name" placeholder="First Name" required
                                   value="<?php echo isset($first_name) && $alert_type !== 'success' ? $first_name : ''; ?>">
                        </div>

                        <div class="form-control-group">
                            <input type="text" name="last_name" placeholder="Last Name" required
                                   value="<?php echo isset($last_name) && $alert_type !== 'success' ? $last_name : ''; ?>">
                        </div>

                        <div class="form-control-group">
                            <input type="email" name="email" placeholder="Email" required
                                   value="<?php echo isset($email) && $alert_type !== 'success' ? $email : ''; ?>">
                        </div>

                        <div class="form-control-group custom-select-arrow">
                            <select name="subject_choice" required>
                                <option value="default" disabled <?php echo !isset($subject_opt) ? 'selected' : ''; ?>>What is your message about?</option>
                                <option value="Borrowing Materials Inquiry" <?php echo (isset($subject_opt) && $subject_opt == "Borrowing Materials Inquiry") ? 'selected' : ''; ?>>Borrowing Materials Inquiry</option>
                                <option value="Account and Logins issue" <?php echo (isset($subject_opt) && $subject_opt == "Account and Logins issue") ? 'selected' : ''; ?>>Account and Logins issue</option>
                                <option value="Book / Research Donation" <?php echo (isset($subject_opt) && $subject_opt == "Book / Research Donation") ? 'selected' : ''; ?>>Book / Research Donation</option>
                                <option value="General Technical Question" <?php echo (isset($subject_opt) && $subject_opt == "General Technical Question") ? 'selected' : ''; ?>>General Technical Question</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-column field-textarea-right">
                        <div class="form-control-group height-100">
                            <textarea name="message" placeholder="Your message" required><?php echo isset($user_message) && $alert_type !== 'success' ? $user_message : ''; ?></textarea>
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

<?php include_once 'includes/footer.php'; ?>
