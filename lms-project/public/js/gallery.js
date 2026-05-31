/**
 * Marinduque Midwest College LMS - Gallery Engine Interaction Matrix
 * Architecture: Event delegation bound to structured section limits.
 */
document.addEventListener("DOMContentLoaded", function () {

    // Find all mini book cards inside the application template layout
    const miniCards = document.querySelectorAll(".book-card-mini");

    miniCards.forEach(function (card) {
        card.addEventListener("click", function () {

            // 1. Isolate target structural bounds to keep row behavior independent
            const sectionContainer = card.closest(".gallery-section-wrapper");
            if (!sectionContainer) return;

            // 2. Extract dataset nodes injected into the clicked card element
            const bookTitle  = card.getAttribute("data-title");
            const bookAuthor = card.getAttribute("data-author");
            const bookIsbn   = card.getAttribute("data-isbn");
            const bookCover  = card.getAttribute("data-cover");

            // 3. Pinpoint structural targets inside the corresponding main display
            const largeImage  = sectionContainer.querySelector(".target-large-img");
            const largeTitle  = sectionContainer.querySelector(".target-large-title");
            const largeAuthor = sectionContainer.querySelector(".target-large-author");
            const largeIsbn   = sectionContainer.querySelector(".target-large-isbn");

            // 4. Fire DOM modifications with an optional fluid transition effect
            if (largeImage) {
                largeImage.style.opacity = "0.3";

                // Set source swap after a tiny performance delay to mimic fluid execution
                setTimeout(() => {
                    largeImage.src = bookCover;
                    largeImage.style.opacity = "1";
                }, 120);
            }

            if (largeTitle)  largeTitle.textContent = bookTitle;
            if (largeAuthor) largeAuthor.textContent = bookAuthor;
            if (largeIsbn)   largeIsbn.textContent = "ISBN: " + bookIsbn;
        });
    });
});
