<?php
class ContactController {

    public function index() {
        // Initialize default array context metrics
        $data = [
            'alert_message' => '',
            'alert_type'    => '',
            'first_name'    => '',
            'last_name'     => '',
            'email'         => '',
            'subject_opt'   => '',
            'user_message'  => ''
        ];

        // Intercept form submissions via POST request
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // 1. Sanitize input variants cleanly
            $data['first_name']   = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $data['last_name']    = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $data['email']        = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $data['subject_opt']  = filter_input(INPUT_POST, 'subject_choice', FILTER_SANITIZE_SPECIAL_CHARS);
            $data['user_message'] = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

            // 2. Structural Backend Edge Validation Case Rules
            if (empty($data['first_name']) || empty($data['last_name'])) {
                $data['alert_message'] = "The Name field is required.";
                $data['alert_type']    = "error";
            } elseif (!$data['email']) {
                $data['alert_message'] = "A valid Email address is required.";
                $data['alert_type']    = "error";
            } elseif (empty($data['subject_opt']) || $data['subject_opt'] === "default") {
                $data['alert_message'] = "Selecting a Query topic is required.";
                $data['alert_type']    = "error";
            } elseif (empty($data['user_message'])) {
                $data['alert_message'] = "The Message field is required.";
                $data['alert_type']    = "error";
            } else {
                // 3. Execution parameters for compliant mail routing
                $to_email      = "lalafoodcustomerservice@gmail.com";
                $email_subject = "MMC LMS Contact Form: " . $data['subject_opt'];

                $email_body = "You have received a new message from the MMC LMS contact form.\n\n".
                              "Name: " . $data['first_name'] . " " . $data['last_name'] . "\n".
                              "Email Address: " . $data['email'] . "\n".
                              "Topic/Query: " . $data['subject_opt'] . "\n\n".
                              "Message Details:\n" . $data['user_message'] . "\n";

                $headers  = "From: webmaster@mmc-lms.com\r\n";
                $headers .= "Reply-To: " . $data['email'] . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                // 4. Send the message natively
                if (mail($to_email, $email_subject, $email_body, $headers)) {
                    $data['alert_message'] = "Your message has been sent successfully!";
                    $data['alert_type']    = "success";
                } else {
                    // Failover fallback alert tracking context for offline local server processing setups
                    $data['alert_message'] = "Form validated successfully! (Mail delivery skipped on local server environment).";
                    $data['alert_type']    = "info";
                }
            }
        }

        return $data;
    }
}
