<style>
.manage-canvas {
    background-color: #ebdcd5 !important;
    padding: 50px 0 !important;
    min-height: 80vh;
}
.manage-inner-wrapper {
    max-width: 820px;
    margin: 0 auto;
    padding: 0 20px;
}
.manage-main-title {
    color: #000000;
    margin: 0 0 45px 0;
    letter-spacing: 0.5px;
}
.manage-section-group {
    margin-bottom: 45px;
}
.manage-section-subtitle {
    color: #000000;
    margin: 0 0 15px 0;
    letter-spacing: 0.5px;
}
.manage-cards-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    align-items: flex-start;
    gap: 15px !important;
}
.manage-dashboard-card {
    display: block;
    position: relative;
    width: 250px;
    height: 250px;
    text-decoration: none !important;
    box-sizing: border-box;
    overflow: hidden;
}
.card-terracotta { background-color: #d5967b !important; }
.card-indigo     { background-color: #6f7aa6 !important; }
.card-crimson    { background-color: #f8392b !important; }
.card-gold       { background-color: #ebd589 !important; }
.card-green      { background-color: #7eb68d !important; }

.manage-card-label {
    position: absolute !important;
    bottom: 15px !important;
    left: 15px !important;
    right: 15px !important;
    margin: 0 !important;
    padding: 0 !important;
    color: #000000 !important;
    text-align: left !important;
    letter-spacing: -0.5px;
    line-height: 1.1;
    z-index: 10;
}
.card-vector-space {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 80%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}
.icon-transactions-stack {
    position: relative;
    width: 110px;
    height: 110px;
}
.paper-layer {
    position: absolute;
    border: 4px solid #ffffff;
    background-color: transparent;
    width: 70px;
    height: 95px;
    box-sizing: border-box;
}
.sheet-back {
    top: 0;
    right: 0;
}
.sheet-front {
    top: 15px;
    left: 0;
    padding: 14px 8px;
    display: flex;
    flex-direction: column;
    gap: 9px;
    background-color: #d5967b;
}
.paper-line {
    height: 4px;
    background-color: #ffffff;
    width: 100%;
}
.icon-fines-coins {
    position: relative;
    width: 150px;
    height: 130px;
}
.coin-layer {
    position: absolute;
    border: 4px solid #ffffff;
    border-radius: 50%;
    width: 90px;
    height: 90px;
    box-sizing: border-box;
    background-color: transparent;
}
.coin-back {
    top: 5px;
    left: 10px;
}
.coin-front {
    top: 40px;
    left: 55px;
}
.icon-reservations-ticket {
    width: 155px;
    height: 90px;
    border: 4px solid #ffffff;
    box-sizing: border-box;
    padding: 24px 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ticket-line {
    height: 4px;
    background-color: #ffffff;
}
.ticket-line.short { width: 45%; }
.ticket-line.mid   { width: 75%; }

.inner-book-vector {
    width: 95px;
    height: 115px;
    border: 4px solid #ffffff;
    box-sizing: border-box;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.vector-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: #bcada4;
    margin-bottom: 14px;
}
.vector-divider {
    width: 45px;
    height: 4px;
    background-color: #ffffff;
}
.manage-canvas .profile-avatar-fallback {
    width: 120px;
    height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
}
.manage-canvas .fallback-vector-head {
    width: 56px;
    height: 56px;
    border: 4px solid #ffffff;
    border-radius: 50%;
    box-sizing: border-box;
    margin-bottom: 2px;
}
.manage-canvas .fallback-vector-torso {
    width: 96px;
    height: 44px;
    border: 4px solid #ffffff;
    border-bottom: none;
    border-radius: 48px 48px 0 0;
    box-sizing: border-box;
}
</style>

<main class="content-container manage-canvas">
    <div class="manage-inner-wrapper">

        <h1 class="manage-main-title">MANAGE:</h1>

        <section class="manage-section-group">
            <h2 class="manage-section-subtitle">STUDENT MANAGEMENT</h2>
            <div class="manage-cards-row">

                <a href="index.php?page=transactions" class="manage-dashboard-card card-terracotta">
                    <div class="card-vector-space">
                        <div class="icon-transactions-stack">
                            <div class="paper-layer sheet-back"></div>
                            <div class="paper-layer sheet-front">
                                <div class="paper-line"></div>
                                <div class="paper-line"></div>
                                <div class="paper-line"></div>
                                <div class="paper-line"></div>
                            </div>
                        </div>
                    </div>
                    <span class="manage-card-label">TRANSACTIONS</span>
                </a>

                <a href="index.php?page=fines" class="manage-dashboard-card card-indigo">
                    <div class="card-vector-space">
                        <div class="icon-fines-coins">
                            <div class="coin-layer coin-back"></div>
                            <div class="coin-layer coin-front"></div>
                        </div>
                    </div>
                    <span class="manage-card-label">FINES</span>
                </a>

                <a href="index.php?page=reservations" class="manage-dashboard-card card-crimson">
                    <div class="card-vector-space">
                        <div class="icon-reservations-ticket">
                            <div class="ticket-line short"></div>
                            <div class="ticket-line mid"></div>
                        </div>
                    </div>
                    <span class="manage-card-label">RESERVATIONS</span>
                </a>

            </div>
        </section>

        <?php if (in_array($user_role, ['admin', 'librarian'])): ?>
            <section class="manage-section-group">
                <h2 class="manage-section-subtitle">LIBRARIAN MANAGEMENT</h2>
                <div class="manage-cards-row">

                    <a href="index.php?page=books" class="manage-dashboard-card card-gold">
                        <div class="card-vector-space">
                            <div class="inner-book-vector">
                                <div class="vector-circle"></div>
                                <div class="vector-divider"></div>
                            </div>
                        </div>
                        <span class="manage-card-label">BOOKS AND MATERIALS</span>
                    </a>

                </div>
            </section>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
            <section class="manage-section-group">
                <h2 class="manage-section-subtitle">ADMINISTRATIVE MANAGEMENT</h2>
                <div class="manage-cards-row">

                    <a href="index.php?page=users" class="manage-dashboard-card card-green">
                        <div class="card-vector-space">
                            <div class="profile-avatar-fallback">
                                <div class="fallback-vector-head"></div>
                                <div class="fallback-vector-torso"></div>
                            </div>
                        </div>
                        <span class="manage-card-label">USERS</span>
                    </a>

                </div>
            </section>
        <?php endif; ?>

    </div>
</main>
