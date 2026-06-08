<main class="content-container borrow-success-canvas">
    <div class="borrow-success-inner-wrapper text-center">

        <div class="borrow-graphic-box">
            <?php if (!empty($details['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($details['cover_image']); ?>"
                     alt="Cover image for <?php echo htmlspecialchars($details['book_title']); ?>"
                     class="borrowed-book-cover-fluid">
            <?php else: ?>
                <div class="inner-book-vector">
                    <div class="vector-circle"></div>
                    <div class="vector-divider"></div>
                </div>
            <?php endif; ?>
        </div>

        <h1 class="borrow-success-title">
            BOOK RETURNED!
        </h1>

        <p class="borrow-advisory-text">
            The book has been successfully checked back into the library inventory.<br>
            Any outstanding late fees have been updated on your account profile.
        </p>

        <div class="borrow-meta-data-block">
            <p>Transaction ID: <span class="data-node"><?php echo htmlspecialchars($details['display_id']); ?></span></p>
            <p>Book: <span class="data-node"><?php echo htmlspecialchars($details['book_title']); ?> (<?php echo htmlspecialchars($details['book_id']); ?>)</span></p>
            <p>Borrower: <span class="data-node"><?php echo htmlspecialchars($details['user_name']); ?> (<?php echo htmlspecialchars($details['user_id']); ?>)</span></p>
            <p>Borrow Date: <span class="data-node"><?php echo htmlspecialchars($details['primary_date']); ?></span></p>
            <p>Due Date: <span class="data-node"><?php echo htmlspecialchars($details['due_date']); ?></span></p>
            <p>Date Returned: <span class="data-node" style="color: #2ecc71; font-weight: bold;"><?php echo htmlspecialchars($details['return_date']); ?></span></p>
            <p>Fines Assessed: <span class="data-node" style="color: <?php echo (floatval(str_replace('$', '', $details['fines'])) > 0) ? '#e74c3c' : 'inherit'; ?>; font-weight: bold;"><?php echo htmlspecialchars($details['fines']); ?></span></p>
        </div>

        <div class="borrow-action-link-container">
            <a href="index.php?page=search" class="borrow-continue-link">Continue</a>
        </div>

    </div>
</main>
