<main class="content-container reservations-canvas">
    <div class="reservations-inner-wrapper">

        <h1 class="res-main-title text-center">RESERVATIONS</h1>

        <p class="res-subtitle-notice text-center">
            <?php echo $is_staff ? "Enter a reservation ID, book title, or username." : "Search by reservation ID or book title."; ?>
        </p>

        <form action="index.php" method="GET" class="res-filtering-form-node">
            <input type="hidden" name="page" value="reservations">

            <div class="res-search-input-field-row">
                <input type="text" name="search" class="res-search-bar-input"
                       placeholder="Search reservations..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                <button type="submit" class="res-search-execution-trigger">
                    <svg class="res-search-svg-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div class="res-control-refinement-row-deck">

                <div class="res-select-facade-container">
                    <select name="sort" onchange="this.form.submit()" class="res-native-refinement-select">
                        <option value="newest" <?php echo ($sort_selection ?? 'newest') === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                        <option value="oldest" <?php echo ($sort_selection ?? 'newest') === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                    </select>
                </div>

                <div class="res-checkbox-filter-strip-row">
                    <label class="res-custom-checkbox-node">
                        <input type="checkbox" name="f_id" value="1" <?php echo ($filter_id ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="res-checkbox-box-graphic"></span> ID
                    </label>

                    <label class="res-custom-checkbox-node">
                        <input type="checkbox" name="f_title" value="1" <?php echo ($filter_title ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="res-checkbox-box-graphic"></span> Title
                    </label>

                    <?php if ($is_staff ?? false): ?>
                        <label class="res-custom-checkbox-node">
                            <input type="checkbox" name="f_username" value="1" <?php echo ($filter_username ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="res-checkbox-box-graphic"></span> Username
                        </label>
                    <?php endif; ?>
                </div>

            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="res-system-status-toast res-status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="res-system-status-toast res-status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="res-display-list-vertical-stack">
            <?php if (!empty($reservations_collection)): ?>
                <?php foreach ($reservations_collection as $row): ?>

                    <div class="res-material-card-wrapper status-<?php echo $row['status']; ?>">

                        <div class="res-graphic-frame-slate flex-shrink-0">
                            <div class="res-inner-book-vector">
                                <i class="fas fa-bookmark"></i>
                            </div>
                        </div>

                        <div class="res-metadata-lavender-block">
                            <div class="res-metadata-rows-stack-left">
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Reservation ID:</span>
                                    <span class="res-meta-data-val">RS-<?php echo str_pad($row['reservation_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Book Requested:</span>
                                    <span class="res-meta-data-val font-prominent"><?php echo htmlspecialchars($row['book_title']); ?> <small style="color:#666;">(ISBN: <?php echo htmlspecialchars($row['isbn']); ?>)</small></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Borrower:</span>
                                    <span class="res-meta-data-val"><?php echo htmlspecialchars($row['borrower_name']); ?> (<?php echo htmlspecialchars($row['username']); ?>)</span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Date Reserved:</span>
                                    <span class="res-meta-data-val"><?php echo date('m/d/Y', strtotime($row['reserved_date'])); ?></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Current Status:</span>
                                    <span class="res-meta-data-val">
                                        <span class="res-status-badge badge-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <div class="res-metadata-action-box-right">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <div class="res-action-button-group">
                                        <form action="index.php?page=reservations" method="POST" onsubmit="return confirm('Fulfill reservation and convert to active transaction output?');" style="display:inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                            <button type="submit" name="action_fulfill" value="1" class="res-btn-action res-btn-fulfill">Fulfill</button>
                                        </form>

                                        <form action="index.php?page=reservations" method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?');" style="display:inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                            <button type="submit" name="action_cancel" value="1" class="res-btn-action res-btn-cancel">Cancel</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="res-completed-stamp-badge stamp-<?php echo $row['status']; ?>">
                                        <?php echo strtoupper($row['status']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="fn-empty-results-fallback-card text-center">
                    <p>No book reservation tracking metrics matches your chosen parameters inside the index system ledger.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>
