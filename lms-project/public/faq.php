<?php
// Initialize session checking and state configurations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "FAQ";
include_once 'includes/header.php';
?>

<main class="content-container">

    <section class="hero-headline-section">
        <h1 class="hero-title">FREQUENTLY ASKED QUESTIONS</h1>
        <div class="hero-banner-image">
            <img src="imgs/MMC_students.jpg" alt="MMC Students Learning Space Banner">
        </div>
    </section>

    <section class="faq-accordion-section">
        <div class="accordion-inner-container">

            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false">
                    <span class="question-text">How do I borrow a book?</span>
                    <span class="chevron-icon-box"><i class="fas fa-caret-down"></i></span>
                </button>
                <div class="accordion-panel">
                    <div class="panel-content-inner">
                        <p>To borrow a book, simply search for the title using our LMS search tool, check its availability status, and click "Reserve". Bring your valid MMC Student ID card to the physical library desk within 24 hours to finalize checkout with our library staff.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false">
                    <span class="question-text">Can I donate books?</span>
                    <span class="chevron-icon-box"><i class="fas fa-caret-down"></i></span>
                </button>
                <div class="accordion-panel">
                    <div class="panel-content-inner">
                        <p>Yes, donations are highly appreciated! MMC Library accepts academic textbooks, reference materials, historical accounts, and literary works. Please reach out via our Contact page to schedule a donation evaluation drop-off.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false">
                    <span class="question-text">How are fines calculated?</span>
                    <span class="chevron-icon-box"><i class="fas fa-caret-down"></i></span>
                </button>
                <div class="accordion-panel">
                    <div class="panel-content-inner">
                        <p>Standard materials accrue overdue fines at a rate of ₱5.00 per school day following the designated return date. Reserve section items or specialized high-demand research documentation accrue fines at a higher standard rate. Fines can be settled directly at the cashier or the library desk.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false">
                    <span class="question-text">Can I reserve books?</span>
                    <span class="chevron-icon-box"><i class="fas fa-caret-down"></i></span>
                </button>
                <div class="accordion-panel">
                    <div class="panel-content-inner">
                        <p>Yes! Registered students who are logged into the LMS can place active holds on books that are currently loaned out by other readers. You will receive an automated system dashboard update when the material is returned and held for your collection.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false">
                    <span class="question-text">Can I request books?</span>
                    <span class="chevron-icon-box"><i class="fas fa-caret-down"></i></span>
                </button>
                <div class="accordion-panel">
                    <div class="panel-content-inner">
                        <p>If our catalog does not currently have a textbook or research paper necessary for your curriculum studies, you can file a material procurement request through your profile interface or contact the library committee for sourcing consideration.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false">
                    <span class="question-text">Can I extend book borrowing?</span>
                    <span class="chevron-icon-box"><i class="fas fa-caret-down"></i></span>
                </button>
                <div class="accordion-panel">
                    <div class="panel-content-inner">
                        <p>Loan extensions can be executed once through your user dashboard under "Active Borrows" prior to the return due date, provided that no other student has placed a priority hold on that specific piece of inventory.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section id="contact" class="grid-row split-50-50 bg-dusty-rose">
        <div class="grid-text-column">
            <h2 class="section-heading">CONTACT</h2>
            <p>Have more questions? Have a query that is not in our FAQ?</p>
            <a href="contact.php" class="contact-link-btn">Contact us!</a>
        </div>
        <div class="grid-image-column">
            <img src="imgs/MMC_contact_banner.jpg" alt="LMS Support Helpdesk Area">
        </div>
    </section>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const triggers = document.querySelectorAll(".accordion-trigger");

    triggers.forEach(trigger => {
        trigger.addEventListener("click", function () {
            const isExpanded = this.getAttribute("aria-expanded") === "true";
            const panel = this.nextElementSibling;

            // Toggle current target state attributes
            this.setAttribute("aria-expanded", !isExpanded);

            if (!isExpanded) {
                panel.style.maxHeight = panel.scrollHeight + "px";
                panel.style.opacity = "1";
            } else {
                panel.style.maxHeight = null;
                panel.style.opacity = "0";
            }
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
