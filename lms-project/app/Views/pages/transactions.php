<style>
    /* Injected layout adjustment to preserve aspect ratios safely within frame blocks */
    .tx-graphic-frame-coral { overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #fce8e6; }
    .tx-book-cover-img { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>

<main class="content-container transactions-canvas">
    <div class="transactions-inner-wrapper">

        <h1 class="tx-main-title text-center">TRANSACTION RECORDS</h1>

        <p class="tx-subtitle-notice text-center">
            <?php echo $is_staff ? "Enter a title, author, ISBN, or username." : "Enter a title, author, or ISBN."; ?>
        </p>

        <form action="index.php" method="GET" class="tx-filtering-form-node">
            <input type="hidden" name="page" value="transactions">

            <div class="tx-search-input-field-row">
                <input type="text" name="search" class="tx-search-bar-input"
                       placeholder="Search..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                <button type="submit" class="tx-search-execution-trigger">
                    <svg class="usr-search-svg-icon" viewBox="0 0 24 24" style="width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:2;">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div class="tx-control-refinement-row-deck">

                <div class="tx-select-facade-container">
                    <select name="sort" onchange="this.form.submit()" class="tx-native-refinement-select">
                        <option value="newest" <?php echo ($sort_selection ?? 'newest') === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                        <option value="oldest" <?php echo ($sort_selection ?? 'newest') === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                    </select>
                </div>

                <div class="tx-checkbox-filter-strip-row">
                    <label class="tx-custom-checkbox-node">
                        <input type="checkbox" name="f_title" value="1" <?php echo ($filter_title ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="tx-checkbox-box-graphic"></span> Title
                    </label>
                    <label class="tx-custom-checkbox-node">
                        <input type="checkbox" name="f_author" value="1" <?php echo ($filter_author ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="tx-checkbox-box-graphic"></span> Author
                    </label>

                    <?php if ($is_staff ?? false): ?>
                        <label class="tx-custom-checkbox-node">
                            <input type="checkbox" name="f_username" value="1" <?php echo ($filter_username ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="tx-checkbox-box-graphic"></span> Username
                        </label>
                    <?php endif; ?>
                </div>

                <div class="tx-export-action-group">
                    <a href="export.php" class="tx-export-data-trigger btn-csv" title="Download Excel CSV Document">
                        <svg viewBox="0 0 24 24" class="export-svg-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Export CSV</span>
                    </a>
                    <a href="export_pdf.php" target="_blank" class="tx-export-data-trigger btn-pdf" title="Generate Report PDF">
                        <svg viewBox="0 0 24 24" class="export-svg-icon"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        <span>Export PDF</span>
                    </a>
                </div>

            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="tx-system-status-toast tx-status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="tx-system-status-toast tx-status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="tx-display-list-vertical-stack">
            <?php if (!empty($transactions_collection)): ?>
                <?php foreach ($transactions_collection as $row): ?>

                    <div class="tx-material-card-wrapper">

                        <div class="tx-graphic-frame-coral flex-shrink-0">
                            <?php if (!empty($row['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['cover_image']); ?>"
                                     alt="<?php echo htmlspecialchars($row['book_title']); ?> Cover"
                                     class="tx-book-cover-img">
                            <?php else: ?>
                                <div class="tx-inner-document-vector">
                                    <div class="tx-vector-line wide"></div>
                                    <div class="tx-vector-line wide"></div>
                                    <div class="tx-vector-line short"></div>
                                    <div class="tx-vector-line short"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tx-metadata-lavender-block">
                            <div class="tx-metadata-rows-stack-left">
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Transaction ID:</span>
                                    <span class="tx-meta-data-val">TX-<?php echo str_pad($row['tx_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Book:</span>
                                    <span class="tx-meta-data-val font-prominent"><?php echo htmlspecialchars($row['book_title']); ?> (ID: <?php echo $row['book_uuid']; ?>)</span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Borrower:</span>
                                    <span class="tx-meta-data-val"><?php echo htmlspecialchars($row['username']); ?> (User ID: <?php echo $row['user_uuid']; ?>)</span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Borrow Date:</span>
                                    <span class="tx-meta-data-val"><?php echo date('m/d/Y', strtotime($row['borrow_date'])); ?></span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Due Date:</span>
                                    <span class="tx-meta-data-val"><?php echo date('m/d/Y', strtotime($row['due_date'])); ?></span>
                                </div>
                                <div class="tx-metadata-line-row">
                                    <span class="tx-meta-label">Return Date:</span>
                                    <span class="tx-meta-data-val">
                                        <?php
                                        if (!empty($row['return_date']) && $row['return_date'] !== '0000-00-00') {
                                            echo date('m/d/Y', strtotime($row['return_date'])) . " (Fines: PHP " . number_format($row['fines'], 2) . ")";
                                        } else {
                                            echo '<span class="tx-pending-tag">Active Outbound Loan</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="tx-metadata-action-box-right">
                                <?php if (empty($row['return_date']) || $row['return_date'] === '0000-00-00'): ?>
                                    <form action="index.php?page=transactions" method="POST" onsubmit="return confirm('Confirm processing check-in for this material return sequence?');">
                                        <input type="hidden" name="transaction_id" value="<?php echo $row['tx_id']; ?>">
                                        <button type="submit" name="action_return_book" value="1" class="tx-card-action-context-node">Return</button>
                                    </form>
                                <?php else: ?>
                                    <div class="tx-completed-stamp-badge">Settled</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="tx-empty-results-fallback-card text-center">
                    <p>No active database transaction interactions matching filter coordinates were found.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>
