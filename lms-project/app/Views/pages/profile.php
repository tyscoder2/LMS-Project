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
.profile-action-btn {
    display: inline-block;
    background-color: #bcada4 !important;
    border: 1px solid #000000 !important;
    border-radius: 12px !important;
    padding: 6px 22px !important;
    color: #000000 !important;
    text-decoration: none !important;
    cursor: pointer;
}
.bio-grid-matrix {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.matrix-row {
    display: flex;
    justify-content: space-between;
    width: 100%;
}
.matrix-val {
    text-align: right;
}
.profile-section-divider {
    border: none !important;
    height: 2px !important;
    background-color: #7b629c !important;
    margin: 30px 0 !important;
}
.metrics-stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 30px;
}
.text-wide .matrix-val {
    text-align: right;
}
.profile-management-trigger-container {
    display: flex;
    justify-content: flex-start;
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

        <h1 class="profile-main-title">PROFILE</h1>

        <div class="profile-upper-deck">

            <div class="profile-media-box">
                <?php if (!empty($user_profile['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user_profile['profile_picture']); ?>"
                         alt="Profile picture" class="profile-avatar-fluid">
                <?php else: ?>
                    <div class="profile-avatar-fallback">
                        <div class="fallback-vector-head"></div>
                        <div class="fallback-vector-torso"></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-bio-data-block">
                <div class="bio-header-row">
                    <h2 class="profile-user-headline">
                        <?php echo htmlspecialchars($user_profile['name']); ?><br>
                        <span class="profile-role-subtext">(<?php echo htmlspecialchars($user_profile['role']); ?>)</span>
                    </h2>
                    <a href="index.php?page=settings" class="profile-action-btn">Settings</a>
                </div>

                <div class="bio-grid-matrix">
                    <div class="matrix-row">
                        <span class="matrix-key">Joined:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['joined_date']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Username:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['username']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">ID Number:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['id_number']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Student ID:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['student_id']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Email:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['email']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Course:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['course']); ?></span>
                    </div>
                    <div class="matrix-row">
                        <span class="matrix-key">Contact:</span>
                        <span class="matrix-val"><?php echo htmlspecialchars($user_profile['contact']); ?></span>
                    </div>
                </div>
            </div>

        </div>

        <hr class="profile-section-divider">

        <div class="profile-lower-deck">
            <div class="metrics-stack">
                <div class="matrix-row text-wide">
                    <span class="matrix-key">Transactions:</span>
                    <span class="matrix-val"><?php echo (int)$user_profile['total_txns']; ?></span>
                </div>
                <div class="matrix-row text-wide">
                    <span class="matrix-key">Fined:</span>
                    <span class="matrix-val"><?php echo (int)$user_profile['times_fined']; ?></span>
                </div>
                <div class="matrix-row text-wide">
                    <span class="matrix-key">Total fines paid:</span>
                    <span class="matrix-val"><?php echo htmlspecialchars($user_profile['total_fines_paid']); ?></span>
                </div>
            </div>

            <?php if (in_array(strtolower($user_profile['role']), ['admin', 'librarian'])): ?>
                <div class="profile-management-trigger-container">
                    <a href="index.php?page=management" class="profile-action-btn">Management</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>
