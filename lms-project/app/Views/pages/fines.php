<main class="content-container fines-canvas">
    <div class="fines-inner-wrapper">

        <h1 class="fn-main-title text-center">FINE RECORDS</h1>

        <p class="fn-subtitle-notice text-center">
            <?php echo $is_staff ? "Enter a fine ID or username." : "Enter a fine ID."; ?>
        </p>

        <form action="index.php" method="GET" class="fn-filtering-form-node">
            <input type="hidden" name="page" value="fines">

            <div class="fn-search-input-field-row">
                <input type="text" name="search" class="fn-search-bar-input"
                       placeholder="Search..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                <button type="submit" class="fn-search-execution-trigger">
                    <svg class="fn-search-svg-icon" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div class="fn-control-refinement-row-deck">

                <div class="fn-select-facade-container">
                    <select name="sort" onchange="this.form.submit()" class="fn-native-refinement-select">
                        <option value="newest" <?php echo ($sort_selection ?? 'newest') === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                        <option value="oldest" <?php echo ($sort_selection ?? 'newest') === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                    </select>
                </div>

                <div class="fn-checkbox-filter-strip-row">
                    <label class="fn-custom-checkbox-node">
                        <input type="checkbox" name="f_id" value="1" <?php echo ($filter_id ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="fn-checkbox-box-graphic"></span> ID
                    </label>

                    <?php if ($is_staff ?? false): ?>
                        <label class="fn-custom-checkbox-node">
                            <input type="checkbox" name="f_username" value="1" <?php echo ($filter_username ?? false) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="fn-checkbox-box-graphic"></span> Username
                        </label>
                    <?php endif; ?>
                </div>

            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="fn-system-status-toast fn-status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="fn-system-status-toast fn-status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="fn-display-list-vertical-stack">
            <?php if (!empty($fines_collection)): ?>
                <?php foreach ($fines_collection as $row): ?>

                    <div class="fn-material-card-wrapper">

                        <div class="fn-graphic-frame-slate flex-shrink-0">
                            <div class="fn-inner-currency-vector">
                                <div class="fn-vector-circle outer-ring"></div>
                                <div class="fn-vector-circle inner-ring"></div>
                            </div>
                        </div>

                        <div class="fn-metadata-lavender-block">
                            <div class="fn-metadata-rows-stack-left">
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Fine ID:</span>
                                    <span class="fn-meta-data-val">FN-<?php echo str_pad($row['fine_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Transaction ID:</span>
                                    <span class="fn-meta-data-val">TX-<?php echo str_pad($row['transaction_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Borrower:</span>
                                    <span class="fn-meta-data-val font-prominent"><?php echo htmlspecialchars($row['username']); ?> (User ID: <?php echo $row['user_uuid']; ?>)</span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Amount:</span>
                                    <span class="fn-meta-data-val">PHP <?php echo number_format($row['amount'], 2); ?></span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Paid:</span>
                                    <span class="fn-meta-data-val">
                                        <?php echo ((int)$row['paid'] === 1) ? 'Yes' : '<span class="fn-outstanding-tag">No (Pending Settle)</span>'; ?>
                                    </span>
                                </div>
                                <div class="fn-metadata-line-row">
                                    <span class="fn-meta-label">Paid Date:</span>
                                    <span class="fn-meta-data-val">
                                        <?php echo ($row['paid_date'] !== null) ? date('m/d/Y', strtotime($row['paid_date'])) : 'TBA'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="fn-metadata-action-box-right">
                                <?php if (((int)$row['paid'] === 0)): ?>
                                    <form action="index.php?page=fines" method="POST" onsubmit="return confirm('Are you certain that you have paid the fines? Any false positives may have further penalties!');">
                                        <input type="hidden" name="fine_id" value="<?php echo $row['fine_id']; ?>">
                                        <button type="submit" name="action_settle_fine" value="1" class="fn-card-action-context-node">Paid</button>
                                    </form>
                                <?php else: ?>
                                    <div class="fn-completed-stamp-badge">Cleared</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="fn-empty-results-fallback-card text-center">
                    <p>No penalty records matching filter coordinates were located inside the system index.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>
