<?php
// Initialize page state configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "About";
include_once 'includes/header.php';
?>

<main class="content-container">

    <section class="hero-headline-section">
        <h1 class="hero-title">WHAT IS THE MMC-LMS?</h1>
        <div class="hero-banner-image">
            <img src="imgs/MMC_library_entrance.jpg" alt="MMC Library Entrance Header">
        </div>
    </section>

    <section class="grid-row split-50-50 bg-dusty-rose">
        <div class="grid-text-column">
            <h2 class="section-heading">DIGITAL LIBRARY</h2>
            <p>The Marinduque Midwest College Library Management System hosts all of the college's library material online! It was created as part of MMC's ambition to improve its digital infrastructure in the age of data and information.</p>
        </div>
        <div class="grid-image-column">
            <img src="imgs/MMC_computers.jpg" alt="Digital Library Media Center">
        </div>
    </section>

    <section class="grid-row split-50-50 reversal-mobile">
        <div class="grid-image-column">
            <img src="imgs/MMC_library.jpg" alt="Library Study Spaces">
        </div>
        <div class="grid-text-column bg-dusty-rose">
            <h2 class="section-heading">CONVENIENCE</h2>
            <p>With a few clicks, search for what MMC's library has to offer. See a title that you need or interests you? Borrow it in a few minutes!</p>
        </div>
    </section>

    <section class="grid-row split-50-50 bg-dusty-rose">
        <div class="grid-text-column">
            <h2 class="section-heading">ARCHIVE</h2>
            <p>Ever wonder what the MMC has in its library aside from textbooks? Find past research, cultural output, and more with the LMS!</p>
        </div>
        <div class="grid-image-column">
            <img src="imgs/MMC_library_shelves.jpg" alt="LMS Document Archives">
        </div>
    </section>

    <section class="grid-row split-50-50 reversal-mobile">
        <div class="grid-image-column">
            <img src="imgs/MMC_library_reading.jpg" alt="Simple Book Checkout Process">
        </div>
        <div class="grid-text-column bg-dusty-rose">
            <h2 class="section-heading">SIMPLICITY</h2>
            <p>The LMS is designed to be straightforward, easy to use, and quick. Browse, check, and borrow.</p>
        </div>
    </section>

    <section id="contact" class="grid-row split-50-50 bg-dusty-rose">
        <div class="grid-text-column">
            <h2 class="section-heading">CONTACT</h2>
            <p>Have more questions? Have a query that is not in our <a href="faq.php" class="inline-link">FAQ</a>?</p>
            <a href="contact.php" class="contact-link-btn">Contact us!</a>
        </div>
        <div class="grid-image-column">
            <img src="imgs/MMC_contact_banner.jpg" alt="LMS Support Helpdesk Area">
        </div>
    </section>

</main>

<?php include_once 'includes/footer.php'; ?>
