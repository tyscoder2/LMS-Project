<style>
    .hidden-state { display: none !important; }
    .inline-edit-input { width: 100%; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; margin: 2px 0; }
    .metadata-action-box-right { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
    .book-cover-frame-yellowish { position: relative; overflow: hidden; }
    .inline-cover-modifier-label {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        background: rgba(15, 23, 42, 0.85);
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        padding: 6px 0;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .inline-cover-modifier-label:hover { background: rgba(231, 76, 60, 0.95); }
</style>

<main class="content-container books-canvas">
    <div class="books-inner-wrapper">

        <h1 class="books-main-title text-center">BOOKS AND MATERIAL</h1>
        <p class="books-subtitle-notice text-center">Enter a title, author, ISBN, or keyword description.</p>

        <form action="index.php" method="GET" class="search-filtering-form-node">
            <input type="hidden" name="page" value="books">

            <div class="search-input-field-row">
                <input type="text" name="search" class="search-bar-input" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-execution-trigger">
                    <svg class="search-svg-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </div>

            <div class="control-refinement-row-deck">
                <div class="dropdown-flex-group">
                    <div class="select-facade-container dropdown-sort">
                        <select name="sort" onchange="this.form.submit()" class="native-refinement-select">
                            <option value="newest" <?php echo $sort_selection === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                            <option value="oldest" <?php echo $sort_selection === 'oldest' ? 'selected' : ''; ?>>Sort by: Oldest</option>
                            <option value="name" <?php echo $sort_selection === 'name' ? 'selected' : ''; ?>>Sort by: By title</option>
                        </select>
                    </div>
                    <div class="select-facade-container dropdown-limit">
                        <select name="limit" onchange="this.form.submit()" class="native-refinement-select">
                            <option value="10" <?php echo $items_limit === 10 ? 'selected' : ''; ?>>Show 10 items</option>
                            <option value="20" <?php echo $items_limit === 20 ? 'selected' : ''; ?>>Show 20 items</option>
                            <option value="50" <?php echo $items_limit === 50 ? 'selected' : ''; ?>>Show 50 items</option>
                        </select>
                    </div>
                </div>

                <div class="checkbox-filter-strip-row">
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_title" value="1" <?php echo $filters['title'] ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Title</label>
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_author" value="1" <?php echo $filters['author'] ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Author</label>
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_category" value="1" <?php echo $filters['category'] ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Category</label>
                    <label class="custom-checkbox-node"><input type="checkbox" name="f_keyword" value="1" <?php echo $filters['keyword'] ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="checkbox-box-graphic"></span> Keyword</label>
                </div>
            </div>
        </form>

        <?php if (!empty($success_msg)): ?>
            <div class="system-status-toast status-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="system-status-toast status-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <section class="book-creation-deck-section">
            <form action="index.php?page=books" method="POST" enctype="multipart/form-data" class="book-creation-form">
                <div class="split-creation-layout-container">
                    <div class="creation-left-cover-picker-box">
                        <div class="book-cover-frame-yellowish" id="cover-preview-wrapper">
                            <div class="inner-book-vector" id="vector-artwork-blueprint"><div class="vector-circle"></div><div class="vector-divider"></div></div>
                            <img src="" alt="Cover thumbnail preview" class="fluid-cover-preview-img hidden-state" id="target-cover-preview">
                        </div>
                        <label for="book-cover-file-input" class="book-cover-trigger-btn">Change image</label>
                        <input type="file" name="book_cover" id="book-cover-file-input" accept="image/*" class="hidden-state" onchange="previewBookCoverFile(this)">
                    </div>

                    <div class="creation-right-inputs-stack">
                        <div class="input-field-group-row"><input type="text" name="title" placeholder="Title" class="creation-field-node-box" required></div>
                        <div class="input-field-group-row"><input type="text" name="author" placeholder="Author" class="creation-field-node-box" required></div>
                        <div class="input-field-group-row"><input type="text" name="isbn" placeholder="ISBN" class="creation-field-node-box"></div>
                        <div class="input-field-group-row">
                            <div class="select-facade-container selection-full-span">
                                <select name="category" class="native-refinement-select form-input-node" required>
                                    <?php foreach ($existing_categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="input-field-group-row"><input type="number" name="copies" placeholder="Number of copies" min="1" value="1" class="creation-field-node-box" required></div>
                    </div>
                </div>
                <div class="creation-execution-row-centered"><button type="submit" name="action_add_book" value="1" class="book-submit-execution-btn">Add</button></div>
            </form>
        </section>

        <div class="books-display-list-vertical-stack">
            <?php if (!empty($registered_books_collection)): ?>
                <?php foreach ($registered_books_collection as $book_row_data): ?>

                    <form action="index.php?page=books&<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? ''); ?>" method="POST" enctype="multipart/form-data" class="book-material-card-wrapper">
                        <input type="hidden" name="book_id" value="<?php echo $book_row_data['id']; ?>">

                        <div class="book-cover-frame-yellowish flex-shrink-0">
                            <?php if (!empty($book_row_data['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($book_row_data['cover_image']); ?>" alt="Material cover" class="fluid-cover-preview-img" id="cover-view-node-<?php echo $book_row_data['id']; ?>">
                            <?php else: ?>
                                <div class="inner-book-vector" id="vector-view-node-<?php echo $book_row_data['id']; ?>"><div class="vector-circle"></div><div class="vector-divider"></div></div>
                                <img src="" alt="Material cover" class="fluid-cover-preview-img hidden-state" id="cover-view-node-<?php echo $book_row_data['id']; ?>">
                            <?php endif; ?>

                            <label for="inline-file-<?php echo $book_row_data['id']; ?>" class="inline-cover-modifier-label input-edit-state hidden-state">Change Image</label>
                            <input type="file" name="edit_book_cover" id="inline-file-<?php echo $book_row_data['id']; ?>" accept="image/*" class="hidden-state" onchange="previewInlineBookCover(this, <?php echo $book_row_data['id']; ?>)">
                        </div>

                        <div class="book-metadata-lavender-block">
                            <div class="metadata-rows-stack-left">
                                <div class="metadata-line-row">
                                    <span class="meta-label">Title:</span>
                                    <span class="meta-data-val font-prominent txt-view-state"><?php echo htmlspecialchars($book_row_data['title']); ?></span>
                                    <input type="text" name="title" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['title']); ?>" required>
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">Author:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars($book_row_data['author']); ?></span>
                                    <input type="text" name="author" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['author']); ?>" required>
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">ISBN:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars(!empty($book_row_data['isbn']) ? $book_row_data['isbn'] : 'N/A'); ?></span>
                                    <input type="text" name="isbn" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['isbn']); ?>">
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">Category:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars($book_row_data['category_name'] ?? 'Unassigned'); ?></span>
                                    <select name="category" class="inline-edit-input input-edit-state hidden-state" required>
                                        <?php foreach ($existing_categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo ($cat['name'] === ($book_row_data['category_name'] ?? '')) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="metadata-line-row">
                                    <span class="meta-label">Copies:</span>
                                    <span class="meta-data-val txt-view-state"><?php echo htmlspecialchars($book_row_data['copies']); ?></span>
                                    <input type="number" name="copies" class="inline-edit-input input-edit-state hidden-state" value="<?php echo htmlspecialchars($book_row_data['copies']); ?>" min="0" required>
                                </div>
                            </div>

                            <div class="metadata-action-box-right">
                                <button type="button" class="card-action-context-node btn-borrow btn-trigger-edit" onclick="enableInlineEditMode(this)">Edit</button>
                                <button type="submit" name="action_edit_book" class="card-action-context-node btn-borrow btn-trigger-save hidden-state" style="background-color: #2f855a;">Save</button>
                                <button type="submit" name="action_delete_book" class="card-action-context-node btn-watch" style="background-color: #e53e3e;" onclick="return confirm('Are you sure?');">Delete</button>
                            </div>
                        </div>
                    </form>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-results-fallback-card text-center"><p>No registered library resource catalog units matching search criteria details were located.</p></div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
function enableInlineEditMode(buttonElement) {
    const rootCardForm = buttonElement.closest('.book-material-card-wrapper');
    rootCardForm.querySelectorAll('.txt-view-state').forEach(element => element.classList.add('hidden-state'));
    rootCardForm.querySelectorAll('.input-edit-state').forEach(element => element.classList.remove('hidden-state'));
    buttonElement.classList.add('hidden-state');
    rootCardForm.querySelector('.btn-trigger-save').classList.remove('hidden-state');
}

function previewInlineBookCover(fileNodeInput, bookId) {
    if (fileNodeInput.files && fileNodeInput.files[0]) {
        const imageStreamReader = new FileReader();
        imageStreamReader.onload = function (eventObj) {
            const visualImgNode = document.getElementById('cover-view-node-' + bookId);
            const vectorArtBlueprint = document.getElementById('vector-view-node-' + bookId);

            visualImgNode.src = eventObj.target.result;
            visualImgNode.classList.remove('hidden-state');

            if (vectorArtBlueprint) {
                vectorArtBlueprint.style.display = 'none';
            }
        };
        imageStreamReader.readAsDataURL(fileNodeInput.files[0]);
    }
}

function previewBookCoverFile(fileNodeInput) {
    if (fileNodeInput.files && fileNodeInput.files[0]) {
        const imageStreamReader = new FileReader();
        imageStreamReader.onload = function (eventObj) {
            const visualImgNode = document.getElementById('target-cover-preview');
            const vectorArtBlueprint = document.getElementById('vector-artwork-blueprint');

            visualImgNode.src = eventObj.target.result;
            visualImgNode.classList.remove('hidden-state');

            if (vectorArtBlueprint) {
                vectorArtBlueprint.style.display = 'none';
            }
        };
        imageStreamReader.readAsDataURL(fileNodeInput.files[0]);
    }
}
</script>
