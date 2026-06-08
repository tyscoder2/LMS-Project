<style>
    .hidden-state { display: none !important; }
    .inline-edit-input { width: 100%; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; margin: 2px 0; display: block; box-sizing: border-box; }
    .usr-metadata-action-box-right { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
    .usr-card-action-context-node { text-align: center; cursor: pointer; border: none; padding: 6px 16px; border-radius: 4px; font-size: 14px; color: white; text-decoration: none; font-weight: 500; }
    .btn-edit { background-color: #4a5568; }
    .btn-save { background-color: #2f855a; }
    .btn-delete { background-color: #e53e3e; }
    .text-capitalize { text-transform: capitalize; }
</style>

<main class="content-container users-canvas">
    <div class="users-inner-wrapper">

        <h1 class="usr-main-title text-center">USER RECORDS</h1>
        <p class="usr-subtitle-notice text-center">Enter a name, username, or email.</p>

        <form action="index.php" method="GET" class="usr-filtering-form-node">
            <input type="hidden" name="page" value="users">

            <div class="usr-search-input-field-row">
                <input type="text" name="search" class="usr-search-bar-input"
                       placeholder="Search users..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="usr-search-execution-trigger">
                    <svg class="usr-search-svg-icon" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            <div class="usr-control-refinement-row-deck">

                <div class="usr-select-facade-container">
                    <select name="sort" onchange="this.form.submit()" class="usr-native-refinement-select">
                        <option value="newest" <?php echo $sort_selection === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                        <option value="oldest" <?php echo $sort_selection === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                        <option value="alphabetical" <?php echo $sort_selection === 'alphabetical' ? 'selected' : ''; ?>>Sort by: Username</option>
                    </select>
                </div>

                <div class="usr-checkbox-filter-strip-row">
                    <label class="usr-custom-checkbox-node">
                        <input type="checkbox" name="f_name" value="1" <?php echo $filters['name'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="usr-checkbox-box-graphic"></span> Name
                    </label>
                    <label class="usr-custom-checkbox-node">
                        <input type="checkbox" name="f_username" value="1" <?php echo $filters['username'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="usr-checkbox-box-graphic"></span> Username
                    </label>
                    <label class="usr-custom-checkbox-node">
                        <input type="checkbox" name="f_email" value="1" <?php echo $filters['email'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="usr-checkbox-box-graphic"></span> Email
                    </label>
                    <label class="usr-custom-checkbox-node">
                        <input type="checkbox" name="f_borrowers" value="1" <?php echo $filters['borrowers'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="usr-checkbox-box-graphic"></span> Borrowers
                    </label>
                </div>

            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="system-status-toast status-success" style="background-color: #c6f6d5; color: #22543d; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="system-status-toast status-error" style="background-color: #fed7d7; color: #742a2a; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="usr-display-list-vertical-stack">
            <?php if (!empty($users_collection)): ?>
                <?php foreach ($users_collection as $row): ?>

                    <form action="index.php?page=users&<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? ''); ?>" method="POST" class="usr-material-card-wrapper">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">

                        <div class="usr-graphic-frame-slate flex-shrink-0">
                            <div class="usr-avatar-silhouette-vector">
                                <div class="usr-head-node"></div>
                                <div class="usr-shoulders-node"></div>
                            </div>
                        </div>

                        <div class="usr-metadata-lavender-block">
                            <div class="usr-metadata-rows-stack-left">
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">Username:</span>
                                    <span class="usr-meta-data-val font-prominent txt-view-state"><?php echo htmlspecialchars($row['username']); ?></span>
                                    <input type="text" name="username" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($row['username']); ?>" required>
                                </div>
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">Student ID:</span>
                                    <span class="usr-meta-data-val txt-view-state"><?php echo htmlspecialchars($row['student_id'] ?: 'N/A'); ?></span>
                                    <input type="text" name="student_id" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($row['student_id'] ?? ''); ?>">
                                </div>
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">Full Name:</span>
                                    <span class="usr-meta-data-val txt-view-state"><?php echo htmlspecialchars($row['name'] ?: 'N/A'); ?></span>
                                    <input type="text" name="name" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($row['name'] ?? ''); ?>">
                                </div>
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">User ID:</span>
                                    <span class="usr-meta-data-val"><?php echo $row['id']; ?></span>
                                </div>
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">Role:</span>
                                    <span class="usr-meta-data-val text-capitalize txt-view-state"><?php echo htmlspecialchars($row['role']); ?></span>
                                    <input type="text" name="role" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($row['role']); ?>" required>
                                </div>
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">Email:</span>
                                    <span class="usr-meta-data-val txt-view-state"><?php echo htmlspecialchars($row['email'] ?: 'N/A'); ?></span>
                                    <input type="email" name="email" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>">
                                </div>
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">Course:</span>
                                    <span class="usr-meta-data-val txt-view-state"><?php echo htmlspecialchars($row['course'] ?: 'N/A'); ?></span>
                                    <select name="course" class="inline-edit-input input-edit-state hidden-state">
                                        <option value="">None / Not Applicable</option>
                                        <?php foreach ($course_options as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo ($row['course'] === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="usr-metadata-line-row">
                                    <span class="usr-meta-label">Contact:</span>
                                    <span class="usr-meta-data-val txt-view-state"><?php echo htmlspecialchars($row['contact'] ?: 'N/A'); ?></span>
                                    <input type="text" name="contact" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($row['contact'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="usr-metadata-action-box-right">
                                <button type="button" class="usr-card-action-context-node btn-edit btn-trigger-edit" onclick="enableInlineUserEdit(this)">Edit</button>
                                <button type="submit" name="action_edit_user" class="usr-card-action-context-node btn-save btn-trigger-save hidden-state">Save</button>
                                <button type="submit" name="action_delete_user" class="usr-card-action-context-node btn-delete" onclick="return confirm('Are you sure?');">Delete</button>
                            </div>
                        </div>

                    </form>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="usr-empty-results-fallback-card text-center">
                    <p>No user account registers matched the configured system search queries.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
function enableInlineUserEdit(buttonElement) {
    const rootCardForm = buttonElement.closest('.usr-material-card-wrapper');

    rootCardForm.querySelectorAll('.txt-view-state').forEach(el => el.classList.add('hidden-state'));
    rootCardForm.querySelectorAll('.input-edit-state').forEach(el => el.classList.remove('hidden-state'));

    buttonElement.classList.add('hidden-state');
    rootCardForm.querySelector('.btn-trigger-save').classList.remove('hidden-state');
}
</script>
