<style>
.profile-canvas {
    background-color: #ebdcd5 !important;
    padding: 60px 0 !important;
    min-height: 80vh;
}
.profile-inner-wrapper {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
    color: #000000;
}
.profile-main-title {
    color: #000000;
    margin: 0 0 40px 0;
    letter-spacing: 0.5px;
}
.profile-upper-deck {
    display: flex;
    gap: 45px;
    align-items: flex-start;
    margin-bottom: 30px;
}
.settings-media-stack {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
}
.profile-media-box {
    width: 290px;
    height: 290px;
    background-color: #7eb68d !important;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.profile-bio-data-block {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 290px;
}
.bio-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
}
.profile-user-headline {
    color: #000000;
    margin: 0;
    line-height: 1.15;
}
.profile-role-subtext {
    display: inline-block;
    margin-top: 2px;
}
.settings-btn-vertical-stack {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.profile-action-btn {
    display: inline-block;
    background-color: #bcada4 !important;
    border: 1px solid #000000 !important;
    border-radius: 12px !important;
    padding: 6px 22px !important;
    color: #000000 !important;
    text-decoration: none !important;
    cursor: pointer;
    text-align: center;
}
.profile-action-btn.execution-node {
    background-color: #7eb68d !important;
}
.file-upload-trigger-container {
    width: 100%;
}
.file-upload-trigger-container .profile-action-btn {
    display: block;
    width: 100%;
    box-sizing: border-box;
}
.hidden-input-node {
    display: none !important;
}
.bio-grid-matrix {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.matrix-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}
.settings-interactive-input {
    width: 60%;
    padding: 6px 10px;
    border: 1px solid #000000;
    background-color: #ffffff;
    color: #000000;
    border-radius: 4px;
    box-sizing: border-box;
}
.settings-interactive-input.dropdown-node {
    width: 100%;
}
.custom-select-wrapper {
    width: 60%;
}
.static-text-node {
    text-align: right;
}
.settings-footer-disclaimer {
    margin-top: 40px;
    border-top: 1px solid #bcada4;
    padding-top: 15px;
}
.disclaimer-text {
    font-size: 0.9rem;
    color: #555555;
    font-style: italic;
}
.profile-media-box .profile-avatar-fluid {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-media-box .profile-avatar-fluid.hidden {
    display: none;
}
.profile-media-box .profile-avatar-fallback {
    width: 220px;
    height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
}
.profile-media-box .fallback-vector-head {
    width: 105px;
    height: 105px;
    border: 5px solid #ffffff;
    border-radius: 50%;
    box-sizing: border-box;
    margin-bottom: 4px;
}
.profile-media-box .fallback-vector-torso {
    width: 175px;
    height: 75px;
    border: 5px solid #ffffff;
    border-bottom: none;
    border-radius: 90px 90px 0 0;
    box-sizing: border-box;
}
</style>

<main class="content-container profile-canvas">
    <div class="profile-inner-wrapper">

        <h1 class="profile-main-title">SETTINGS</h1>

        <?php if (!empty($error_msg)): ?>
            <div class="form-alert alert-error" style="max-width: 500px; margin: 0 auto 20px auto; color: red; text-align: center; font-weight: bold;">
                <p><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        <?php endif; ?>

        <form action="index.php?page=settings" method="POST" enctype="multipart/form-data" class="settings-form-wrapper">

            <div class="profile-upper-deck">

                <div class="settings-media-stack">
                    <div class="profile-media-box">
                        <?php if (!empty($profile_pic)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>?t=<?php echo time(); ?>"
                                 alt="Profile picture" class="profile-avatar-fluid" id="avatar-preview">
                        <?php else: ?>
                            <div class="profile-avatar-fallback" id="fallback-graphics">
                                <div class="fallback-vector-head"></div>
                                <div class="fallback-vector-torso"></div>
                            </div>
                            <img src="" alt="Preview image" class="profile-avatar-fluid hidden" id="avatar-preview">
                        <?php endif; ?>
                    </div>

                    <div class="file-upload-trigger-container">
                        <label for="file-upload-input" class="profile-action-btn border-capsule text-center pointer-node">Upload pic</label>
                        <input type="file" name="profile_pic" id="file-upload-input" accept="image/*" class="hidden-input-node" onchange="previewImageFile(this)">
                    </div>
                </div>

                <div class="profile-bio-data-block">
                    <div class="bio-header-row">
                        <h2 class="profile-user-headline">
                            <?php echo htmlspecialchars($display_name); ?><br>
                            <span class="profile-role-subtext">(<?php echo htmlspecialchars($display_role); ?>)</span>
                        </h2>

                        <div class="settings-btn-vertical-stack">
                            <button type="submit" class="profile-action-btn border-capsule execution-node">Save</button>
                            <a href="index.php?page=profile" class="profile-action-btn border-capsule fallback-cancel-node">Cancel</a>
                        </div>
                    </div>

                    <div class="bio-grid-matrix">

                        <div class="matrix-row">
                            <span class="matrix-key">Joined:</span>
                            <span class="matrix-val static-text-node"><?php echo htmlspecialchars($display_joined); ?></span>
                        </div>

                        <div class="matrix-row">
                            <label for="input-username" class="matrix-key">Username:</label>
                            <input type="text" name="username" id="input-username" class="settings-interactive-input"
                                   value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
                        </div>

                        <div class="matrix-row">
                            <span class="matrix-key">ID Number:</span>
                            <span class="matrix-val static-text-node"><?php echo htmlspecialchars($display_id); ?></span>
                        </div>

                        <?php if ($display_role === 'student'): ?>
                            <div class="matrix-row">
                                <label for="input-student-id" class="matrix-key">Student ID:</label>
                                <input type="text" name="student_id" id="input-student-id" class="settings-interactive-input"
                                       value="<?php echo htmlspecialchars($user_data['student_id'] ?? ''); ?>" required>
                            </div>
                        <?php endif; ?>

                        <div class="matrix-row">
                            <label for="input-email" class="matrix-key">Email:</label>
                            <input type="email" name="email" id="input-email" class="settings-interactive-input"
                                   value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                        </div>

                        <?php if ($display_role === 'student'): ?>
                            <div class="matrix-row">
                                <label for="select-course" class="matrix-key">Course:</label>
                                <div class="custom-select-wrapper">
                                    <select name="course" id="select-course" class="settings-interactive-input dropdown-node">
                                        <option value="BSCS" <?php echo ($user_data['course'] ?? '') === 'BSCS' ? 'selected' : ''; ?>>BSCS</option>
                                        <option value="BEEd" <?php echo ($user_data['course'] ?? '') === 'BEEd' ? 'selected' : ''; ?>>BEEd</option>
                                        <option value="BSEd" <?php echo ($user_data['course'] ?? '') === 'BSEd' ? 'selected' : ''; ?>>BSEd</option>
                                    </select>
                                </div>
                            </div>

                            <div class="matrix-row">
                                <label for="input-contact" class="matrix-key">Contact:</label>
                                <input type="text" name="contact" id="input-contact" class="settings-interactive-input"
                                       value="<?php echo htmlspecialchars($user_data['contact'] ?? ''); ?>">
                            </div>
                        <?php else: ?>
                            <div class="matrix-row">
                                <span class="matrix-key">Context:</span>
                                <span class="matrix-val static-text-node">Management Account Matrix</span>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </form>

        <div class="settings-footer-disclaimer">
            <p class="disclaimer-text">Note: Changes to account credentials modify active authentication systems instantaneously.</p>
        </div>

    </div>
</main>

<script>
function previewImageFile(inputNode) {
    if (inputNode.files && inputNode.files[0]) {
        const fileReader = new FileReader();
        fileReader.onload = function (e) {
            const previewImg = document.getElementById('avatar-preview');
            const fallbackGraphic = document.getElementById('fallback-graphics');

            previewImg.src = e.target.result;
            previewImg.classList.remove('hidden');

            if (fallbackGraphic) {
                fallbackGraphic.style.display = 'none';
            }
        };
        fileReader.readAsDataURL(inputNode.files[0]);
    }
}
</script>
