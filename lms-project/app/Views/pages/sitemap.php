<style>
.sitemap-canvas {
    background-color: #ebdcd5 !important;
    padding: 60px 0 !important;
    min-height: 75vh;
}
.sitemap-inner-wrapper {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
}
.sitemap-main-title {
    color: #000000;
    margin: 0 0 40px 0;
    font-size: 2.5rem;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.sitemap-grid-container {
    display: flex;
    justify-content: space-between;
    gap: 30px;
}
.sitemap-column {
    flex: 1;
    background-color: #bcada4 !important;
    border: 1px solid #000000;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
.sitemap-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.sitemap-link {
    display: block;
    color: #000000 !important;
    text-decoration: none !important;
    font-size: 1.1rem;
    padding: 6px 12px;
    border-radius: 4px;
    transition: background-color 0.2s ease, transform 0.15s ease;
}
.sitemap-link:hover {
    background-color: #7eb68d !important;
    transform: translateX(3px);
}
.sitemap-link.active-page {
    background-color: #7eb68d !important;
    font-weight: bold;
    border-left: 4px solid #000000;
    padding-left: 8px;
}
.text-center {
    text-align: center;
}
@media (max-width: 768px) {
    .sitemap-grid-container {
        flex-direction: column;
        gap: 20px;
    }
}
</style>

<main class="content-container sitemap-canvas">
    <div class="sitemap-inner-wrapper">

        <h1 class="sitemap-main-title text-center">SITE MAP</h1>

        <div class="sitemap-grid-container">

            <?php foreach ($sitemap_columns as $column_links): ?>
                <div class="sitemap-column">
                    <ul class="sitemap-list">
                        <?php foreach ($column_links as $link): ?>
                            <?php
                                // Match router keys with the controller configuration state
                                $is_active = ($current_page === ($link['key'] ?? ''));
                                $class_assignment = $is_active ? 'sitemap-link active-page' : 'sitemap-link';
                            ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>" class="<?php echo $class_assignment; ?>">
                                    <?php echo htmlspecialchars($link['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

        </div>

    </div>
</main>
