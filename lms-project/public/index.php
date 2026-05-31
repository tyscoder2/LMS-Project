<?php
session_start();
// Quick simulation toggle for logged-in state visualization
// $_SESSION['user_id'] = 1;

$page_title = "Home";
include_once 'includes/header.php';

// Organized mock data subsets for precise category rendering
$featured_main = [
    'title' => 'Introduction to Software Engineering', 'author' => 'Dr. E. Reynolds', 'isbn' => '978-3-16-148410-0',
    'cover' => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?auto=format&fit=crop&w=400&q=80'
];

$featured_gallery = [
    ['title' => 'Web Dev with PHP 8', 'author' => 'A. Mansfield', 'isbn' => '978-1-80107-187-1', 'cover' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Database Systems', 'author' => 'C. J. Date', 'isbn' => '978-0-32119-784-0', 'cover' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Data Structures 101', 'author' => 'R. Lafore', 'isbn' => '978-0-67232-453-6', 'cover' => 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Modern UI Patterns', 'author' => 'V. Giguere', 'isbn' => '978-1-49204-459-7', 'cover' => 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'The Digital Mind', 'author' => 'W. S. McCulloch', 'isbn' => '978-0-26213-294-7', 'cover' => 'https://images.unsplash.com/photo-1516979187457-637abb4f9353?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Systems Architecture', 'author' => 'M. Fowler', 'isbn' => '978-0-32112-742-6', 'cover' => 'https://images.unsplash.com/photo-1506880018603-83d5b814b5a6?auto=format&fit=crop&w=150&q=80']
];

$new_main = [
    'title' => 'AI and Ethics in 2026', 'author' => 'Prof. S. Vance', 'isbn' => '978-9-12345-678-9',
    'cover' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=400&q=80'
];

$new_gallery = [
    ['title' => 'Cloud Foundations', 'author' => 'T. J. Watson', 'isbn' => '978-0-13412-405-6', 'cover' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Cybersecurity Essentials', 'author' => 'K. Mitnick', 'isbn' => '978-1-11936-334-7', 'cover' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Discrete Mathematics', 'author' => 'K. Rosen', 'isbn' => '978-0-07338-309-5', 'cover' => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Compilers & Parsers', 'author' => 'A. V. Aho', 'isbn' => '978-0-32148-681-3', 'cover' => 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?auto=format&fit=crop&w=150&q=80'],
    ['title' => 'Agile Frameworks', 'author' => 'K. Schwaber', 'isbn' => '978-0-73561-993-7', 'cover' => 'https://images.unsplash.com/photo-1531403009284-440f080d1e12?auto=format&fit=crop&w=150&q=80']
];
?>

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
                        <img src="<?php echo $featured_main['cover']; ?>" alt="Featured Display" class="real-book-cover target-large-img">
                        <div class="book-info-overlay">
                            <h4 class="target-large-title"><?php echo $featured_main['title']; ?></h4>
                            <p class="target-large-author"><?php echo $featured_main['author']; ?></p>
                            <span class="badge target-large-isbn">ISBN: <?php echo $featured_main['isbn']; ?></span>
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
                            <img src="<?php echo $book['cover']; ?>" alt="Mini Book" class="real-book-cover-sm">
                            <div class="mini-tooltip"><strong><?php echo $book['title']; ?></strong></div>
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
                        <img src="<?php echo $new_main['cover']; ?>" alt="New Display" class="real-book-cover target-large-img">
                        <div class="book-info-overlay">
                            <h4 class="target-large-title"><?php echo $new_main['title']; ?></h4>
                            <p class="target-large-author"><?php echo $new_main['author']; ?></p>
                            <span class="badge target-large-isbn">ISBN: <?php echo $new_main['isbn']; ?></span>
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
                            <img src="<?php echo $book['cover']; ?>" alt="Mini Book" class="real-book-cover-sm">
                            <div class="mini-tooltip"><strong><?php echo $book['title']; ?></strong></div>
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
                        <img src="<?php echo $featured_gallery[4]['cover']; ?>" alt="Popular Display" class="real-book-cover target-large-img">
                        <div class="book-info-overlay">
                            <h4 class="target-large-title"><?php echo $featured_gallery[4]['title']; ?></h4>
                            <p class="target-large-author"><?php echo $featured_gallery[4]['author']; ?></p>
                            <span class="badge target-large-isbn">ISBN: <?php echo $featured_gallery[4]['isbn']; ?></span>
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
                            <img src="<?php echo $book['cover']; ?>" alt="Mini Book" class="real-book-cover-sm">
                            <div class="mini-tooltip"><strong><?php echo $book['title']; ?></strong></div>
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

<script src="<?php echo $base_url; ?>/js/gallery.js"></script>

<?php include_once 'includes/footer.php'; ?>
