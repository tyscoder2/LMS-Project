<main class="content-container reservations-canvas">
    <div class="reservations-inner-wrapper">

        <h1 class="res-main-title text-center">RESERVATIONS</h1>

        <p class="res-subtitle-notice text-center">
            <?php echo $is_staff ? "Enter a reservation ID, title, or username." : "Search by reservation ID or book title."; ?>
        </p>

        <form action="index.php" method="GET" class="res-filtering-form-node">
            <input type="hidden" name="page" value="reservations">

            <div class="res-search-input-field-row">
                <input type="text" name="search" class="res-search-bar-input"
                       placeholder="Search..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
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

        <div class="res-display-list-vertical-stack" style="max-width: 760px; margin: 0 auto; padding: 0 10px;">
            <?php if (!empty($reservations_collection)): ?>
                <?php foreach ($reservations_collection as $row): ?>

                    <div class="res-material-card-wrapper status-<?php echo $row['status']; ?>" style="display: flex; margin-bottom: 24px; border-radius: 0px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); min-height: 160px; height: auto;">

                        <div class="res-graphic-frame-slate flex-shrink-0" style="width: 180px; background-color: #f34336; display: flex; align-items: center; justify-content: center; padding: 12px; box-sizing: border-box;">
                            <?php if (!empty($row['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['cover_image']); ?>"
                                     alt="Cover image for <?php echo htmlspecialchars($row['book_title']); ?>"
                                     style="width: 100%; height: auto; object-fit: contain; max-height: 220px; align-self: center; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                            <?php else: ?>
                                <div class="inner-book-vector" style="width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 10px; box-sizing: border-box;">
                                    <div class="vector-line" style="width: 80%; height: 4px; background-color: #ffffff; margin-bottom: 8px; border-radius: 2px;"></div>
                                    <div class="vector-line" style="width: 60%; height: 4px; background-color: #ffffff; margin-bottom: 8px; border-radius: 2px; align-self: flex-start; margin-left: 10%;"></div>
                                    <div class="vector-line" style="width: 70%; height: 4px; background-color: #ffffff; border-radius: 2px; align-self: flex-start; margin-left: 10%;"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="res-metadata-lavender-block" style="flex-grow: 1; background-color: #d1b2ff; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center;">
                            <div class="res-metadata-rows-stack-left" style="line-height: 1.6; color: #000000; font-family: sans-serif; font-size: 0.95rem; padding-right: 15px;">
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Reservation ID: </span>
                                    <span class="res-meta-data-val">RS-<?php echo str_pad($row['reservation_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Book Requested: </span>
                                    <span class="res-meta-data-val" style="font-weight: bold;"><?php echo htmlspecialchars($row['book_title']); ?></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Borrower: </span>
                                    <span class="res-meta-data-val"><?php echo htmlspecialchars($row['borrower_name']); ?> (<?php echo htmlspecialchars($row['username']); ?>)</span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Date Reserved: </span>
                                    <span class="res-meta-data-val"><?php echo date('m/d/Y', strtotime($row['reserved_date'])); ?></span>
                                </div>
                                <div class="res-metadata-line-row">
                                    <span class="res-meta-label">Current Status: </span>
                                    <span class="res-meta-data-val" style="text-transform: capitalize;"><?php echo htmlspecialchars($row['status']); ?></span>
                                </div>
                            </div>

                            <div class="res-metadata-action-box-right" style="display: flex; flex-direction: column; gap: 10px; min-width: 110px; flex-shrink: 0;">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form action="index.php?page=reservations" method="POST" onsubmit="return confirm('Fulfill reservation and convert to active transaction output?');" style="margin: 0;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                        <button type="submit" name="action_fulfill" value="1" class="res-btn-action res-btn-fulfill" style="width: 100%; padding: 6px 16px; background-color: #b1b1b1; border: 1px solid #7a7a7a; border-radius: 12px; font-size: 0.9rem; font-family: sans-serif; cursor: pointer; color: #000000; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">Fulfill</button>
                                    </form>

                                    <form action="index.php?page=reservations" method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?');" style="margin: 0;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                        <button type="submit" name="action_cancel" value="1" class="res-btn-action res-btn-cancel" style="width: 100%; padding: 6px 16px; background-color: #b1b1b1; border: 1px solid #7a7a7a; border-radius: 12px; font-size: 0.9rem; font-family: sans-serif; cursor: pointer; color: #000000; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <div class="res-completed-stamp-badge stamp-<?php echo $row['status']; ?>" style="text-align: center; font-weight: bold; font-family: sans-serif; padding: 6px; border: 2px dashed currentColor; border-radius: 4px; transform: rotate(-5deg); color: <?php echo $row['status'] === 'fulfilled' ? '#2e7d32' : '#c62828'; ?>;">
                                        <?php echo strtoupper($row['status']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="fn-empty-results-fallback-card text-center" style="background: #ffffff; padding: 40px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <p style="color: #666666; font-family: sans-serif;">No book reservation tracking metrics matches your chosen parameters inside the index system ledger.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>
