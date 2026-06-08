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
            <?php echo $is_reservation ? "BOOK RESERVED!" : "BOOK BORROWED!"; ?>
        </h1>

        <p class="borrow-advisory-text">
            <?php if ($is_reservation): ?>
                This resource is currently out of stock. You have been added to the queue.<br>
                We will contact you as soon as a copy becomes available.
            <?php else: ?>
                Be sure to claim your book at the library immediately<br>
                and return by its due date to avoid penalty fines.
            <?php endif; ?>
        </p>

        <div class="borrow-meta-data-block">
            <p><?php echo $is_reservation ? "Reservation ID" : "Transaction ID"; ?>: <span class="data-node"><?php echo htmlspecialchars($details['display_id']); ?></span></p>
            <p>Book: <span class="data-node"><?php echo htmlspecialchars($details['book_title']); ?> (<?php echo htmlspecialchars($details['book_id']); ?>)</span></p>
            <p>Borrower: <span class="data-node"><?php echo htmlspecialchars($details['user_name']); ?> (<?php echo htmlspecialchars($details['user_id']); ?>)</span></p>
            <p><?php echo $is_reservation ? "Reserved Date" : "Borrow Date"; ?>: <span class="data-node"><?php echo htmlspecialchars($details['primary_date']); ?></span></p>
            <p>Due Date: <span class="data-node"><?php echo htmlspecialchars($details['due_date']); ?></span></p>
        </div>

        <div class="borrow-action-link-container">
            <a href="index.php?page=search" class="borrow-continue-link">Continue</a>
        </div>

    </div>
</main>
