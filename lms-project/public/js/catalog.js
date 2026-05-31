document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const searchButton = document.getElementById("searchBtn");
    const catalogContainer = document.querySelector(".main-content-wrapper");

    const renderCatalog = async (searchTerm = '') => {
        try {
            // Contextual parameters routing safely to our controller stack endpoints
            const response = await fetch(`/api/books.php?search=${encodeURIComponent(searchTerm)}`);
            if (!response.ok) throw new Error("Network operations failure state identified.");

            const books = await response.json();

            // Wipe standard template components and replace with reactive card templates
            document.querySelectorAll(".catalog-item-card").forEach(el => el.remove());

            books.forEach(book => {
                const card = document.createElement("article");
                card.className = "catalog-item-card";
                card.innerHTML = `
                    <div class="catalog-cover-space">
                        <div class="book-mock-icon"><img src="/public/uploads/${book.cover_image}" alt="cover" style="width:100%;height:100%;"></div>
                    </div>
                    <div class="catalog-details-space">
                        <ul class="book-metadata-list">
                            <li><strong>Title:</strong> ${book.title}</li>
                            <li><strong>Author:</strong> ${book.author}</li>
                            <li><strong>ISBN:</strong> ${book.isbn}</li>
                            <li><strong>Category:</strong> ${book.category_name || 'Unassigned'}</li>
                            <li><strong>Copies Available:</strong> ${book.copies}</li>
                        </ul>
                        <div class="catalog-action-row">
                            <button class="action-card-btn" ${book.copies <= 0 ? 'disabled style="background:#444;"' : ''} onclick="requestBorrow(${book.id})">
                                ${book.copies > 0 ? 'Borrow' : 'On Loan'}
                            </button>
                        </div>
                    </div>
                `;
                catalogContainer.appendChild(card);
            });
        } catch (err) {
            console.error("Catalog mapping operational failure:", err);
        }
    };

    if (searchButton) {
        searchButton.addEventListener("click", () => renderCatalog(searchInput.value));
    }
});
