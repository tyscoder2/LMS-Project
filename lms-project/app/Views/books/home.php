<main class="content-container">

    <section class="hero-headline-section">
        <h1 class="hero-title">“BE A CHANGEMAKER”</h1>
        <div class="hero-banner-image">
            <img src="imgs/MMC_library_shelves.jpg" alt="MMC Library Bookshelf">
        </div>
    </section>

    <section id="about" class="grid-row split-50-50 bg-dusty-rose">
        <div class="grid-text-column">
            <h2 class="section-heading">THE MMC-LMS</h2>
            <p>Introducing the Marinduque Midwest College Library Management System. Search the college library for learning materials, research, and more.</p>
            <p class="cta-text">Are you a student needing to borrow a book?<br><strong>LOGIN/REGISTER NOW!</strong></p>
        </div>
        <div class="grid-image-column">
            <img src="imgs/MMC_library_entrance.jpg" alt="Library Entrance">
        </div>
    </section>

    <section class="grid-row split-50-50 reversal-mobile gallery-section-wrapper">
        <div class="grid-gallery-column bg-lavender">
            <div class="gallery-wrapper">
                <div class="featured-large-display">
                    <div class="book-card-large">
                        <img src="<?php echo htmlspecialchars($featured_main['cover']); ?>" alt="Featured Display" class="real-book-cover target-large-img">
                        <div class="book-info-overlay">
                            <h4 class="target-large-title"><?php echo htmlspecialchars($featured_main['title']); ?></h4>
                            <p class="target-large-author"><?php echo htmlspecialchars($featured_main['author']); ?></p>
                            <span class="badge target-large-isbn">ISBN: <?php echo htmlspecialchars($featured_main['isbn']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mini-book-strip">
                    <?php foreach ($featured_gallery as $book): ?>
                        <div class="book-card-mini"
                             data-title="<?php echo htmlspecialchars($book['title']); ?>"
                             data-author="<?php echo htmlspecialchars($book['author']); ?>"
                             data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                             data-cover="<?php echo htmlspecialchars($book['cover']); ?>">
                            <img src="<?php echo htmlspecialchars($book['cover']); ?>" alt="Mini Book" class="real-book-cover-sm">
                            <div class="mini-tooltip"><strong><?php echo htmlspecialchars($book['title']); ?></strong></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="grid-text-column bg-dusty-rose">
            <h2 class="section-heading">FEATURED</h2>
            <p>Sent through our latest recommendations!</p>
        </div>
    </section>

    <section class="grid-row split-50-50 gallery-section-wrapper">
        <div class="grid-text-column bg-dusty-rose">
            <h2 class="section-heading">NEW</h2>
            <p>Check out our newest titles and materials!</p>
        </div>
        <div class="grid-gallery-column bg-lavender">
            <div class="gallery-wrapper">
                <div class="featured-large-display">
                    <div class="book-card-large">
                        <img src="<?php echo htmlspecialchars($new_main['cover']); ?>" alt="New Display" class="real-book-cover target-large-img">
                        <div class="book-info-overlay">
                            <h4 class="target-large-title"><?php echo htmlspecialchars($new_main['title']); ?></h4>
                            <p class="target-large-author"><?php echo htmlspecialchars($new_main['author']); ?></p>
                            <span class="badge target-large-isbn">ISBN: <?php echo htmlspecialchars($new_main['isbn']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mini-book-strip">
                    <?php foreach ($new_gallery as $book): ?>
                        <div class="book-card-mini"
                             data-title="<?php echo htmlspecialchars($book['title']); ?>"
                             data-author="<?php echo htmlspecialchars($book['author']); ?>"
                             data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                             data-cover="<?php echo htmlspecialchars($book['cover']); ?>">
                            <img src="<?php echo htmlspecialchars($book['cover']); ?>" alt="Mini Book" class="real-book-cover-sm">
                            <div class="mini-tooltip"><strong><?php echo htmlspecialchars($book['title']); ?></strong></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="grid-row split-50-50 reversal-mobile gallery-section-wrapper">
        <div class="grid-gallery-column bg-lavender">
            <div class="gallery-wrapper">
                <div class="featured-large-display">
                    <div class="book-card-large">
                        <img src="<?php echo htmlspecialchars($popular_main['cover']); ?>" alt="Popular Display" class="real-book-cover target-large-img">
                        <div class="book-info-overlay">
                            <h4 class="target-large-title"><?php echo htmlspecialchars($popular_main['title']); ?></h4>
                            <p class="target-large-author"><?php echo htmlspecialchars($popular_main['author']); ?></p>
                            <span class="badge target-large-isbn">ISBN: <?php echo htmlspecialchars($popular_main['isbn']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mini-book-strip">
                    <?php foreach ($popular_gallery as $book): ?>
                        <div class="book-card-mini"
                             data-title="<?php echo htmlspecialchars($book['title']); ?>"
                             data-author="<?php echo htmlspecialchars($book['author']); ?>"
                             data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
                             data-cover="<?php echo htmlspecialchars($book['cover']); ?>">
                            <img src="<?php echo htmlspecialchars($book['cover']); ?>" alt="Mini Book" class="real-book-cover-sm">
                            <div class="mini-tooltip"><strong><?php echo htmlspecialchars($book['title']); ?></strong></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="grid-text-column bg-dusty-rose">
            <h2 class="section-heading">POPULAR</h2>
            <p>View here our hottest materials!</p>
        </div>
    </section>

    <section id="contact" class="grid-row split-50-50 bg-dusty-rose">
        <div class="grid-text-column">
            <h2 class="section-heading">CONTACT</h2>
            <p>Have a query? Can't find a title you were looking for? Or want to donate to the LMS?</p>
            <a href="contact.php" class="contact-link-btn">Contact us!</a>
        </div>
        <div class="grid-image-column">
            <img src="imgs/MMC_contact_banner.jpg" alt="LMS Support Desk Workstation">
        </div>
    </section>
</main>
