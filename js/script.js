document.addEventListener('DOMContentLoaded', () => {

    const boardContainer = document.querySelector('.board-container');
    if (!boardContainer) return; // Sadece proje panosu sayfasında çalış

    const projectId = boardContainer.dataset.projectId;

    // Sayfa yüklendiğinde URL'yi kontrol et ve gerekirse modal'ı aç
    const urlParams = new URLSearchParams(window.location.search);
    const cardToOpen = urlParams.get('open_card');
    if (cardToOpen) {
        openCardModal(cardToOpen);
        // Tarayıcı geçmişini temizle, böylece yenileme modal'ı tekrar açmaz
        window.history.replaceState({}, document.title, window.location.pathname + '?id=' + projectId);
    }

    // --- Sürükle ve Bırak (Drag and Drop) Mantığı ---
    initializeDragAndDrop();

    // --- Kart Detayları Modal Mantığı ---
    const modal = document.getElementById('card-modal');
    const modalContentWrapper = document.getElementById('modal-card-content-wrapper');
    const closeModalBtn = document.querySelector('.modal-close-btn');

    // Sadece gerçek görev kartları için modal açma olayını ekle
    document.querySelectorAll('.cards .card').forEach(card => {
        card.addEventListener('click', (e) => {
            // Atama formuna tıklandığında modalın açılmasını engelle
            if (e.target.closest('.assign-form')) return;

            const cardId = card.dataset.cardId;
            openCardModal(cardId);
        });
    });

    // Modal kapatma olayları
    closeModalBtn.addEventListener('click', () => modal.style.display = 'none');
    modal.addEventListener('click', (e) => {
        if (e.target === modal) { // Sadece overlay'e tıklanırsa kapat
            modal.style.display = 'none';
        }
    });

    function initializeDragAndDrop() {
        const cards = document.querySelectorAll('.card[draggable="true"]');
        const lists = document.querySelectorAll('.list');
        let draggedCard = null;

        cards.forEach(card => {
            card.addEventListener('dragstart', () => {
                draggedCard = card;
                setTimeout(() => card.style.opacity = '0.5', 0);
            });
            card.addEventListener('dragend', () => {
                draggedCard.style.opacity = '1';
            });
        });

        lists.forEach(list => {
            list.addEventListener('dragover', e => {
                e.preventDefault();
                const afterElement = getDragAfterElement(list, e.clientY);
                const cardsContainer = list.querySelector('.cards');
                if (afterElement == null) {
                    cardsContainer.appendChild(draggedCard);
                } else {
                    cardsContainer.insertBefore(draggedCard, afterElement);
                }
            });
            list.addEventListener('drop', e => {
                e.preventDefault();
                const newListId = list.dataset.listId;
                const cardId = draggedCard.dataset.cardId;
                updateCardPosition(projectId, cardId, newListId);
            });
        });
    }

    function getDragAfterElement(list, y) {
        const draggableElements = [...list.querySelectorAll('.card:not([style*="opacity: 0.5"])')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateCardPosition(projectId, cardId, newListId) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'move_card', projectId, cardId, newListId })
        }).then(res => res.json()).then(data => {
            if (!data.success) console.error(data.message);
        }).catch(err => console.error(err));
    }

    function openCardModal(cardId) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_card_details', projectId, cardId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderModalContent(data.card);
                modal.style.display = 'flex';
            } else {
                alert('Hata: ' + data.message);
            }
        });
    }

    function renderModalContent(card) {
        modalContentWrapper.innerHTML = `
            <form id="modal-card-form">
                <div class="form-group">
                    <label for="modal-card-title">Başlık</label>
                    <input type="text" id="modal-card-title" name="title" value="${escapeHTML(card.title)}" required>
                </div>
                <div class="form-group">
                    <label for="modal-card-description">Açıklama</label>
                    <textarea id="modal-card-description" name="description" rows="4">${escapeHTML(card.description)}</textarea>
                </div>
                <!-- Diğer alanlar (son tarih, etiketler) buraya eklenebilir -->
                <button type="submit" class="btn">Kaydet</button>
            </form>
        `;

        document.getElementById('modal-card-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const newTitle = document.getElementById('modal-card-title').value;
            const newDescription = document.getElementById('modal-card-description').value;

            updateCardDetails(card.id, newTitle, newDescription);
        });
    }

    function updateCardDetails(cardId, title, description) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_card_details',
                projectId: projectId,
                cardId: cardId,
                title: title,
                description: description
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Panodaki kartın başlığını güncelle
                const cardOnBoard = document.querySelector(`.card[data-card-id="${cardId}"] .card-title`);
                if (cardOnBoard) {
                    cardOnBoard.textContent = title;
                }
                modal.style.display = 'none';
            } else {
                alert('Güncelleme hatası: ' + data.message);
            }
        });
    }

    function escapeHTML(str) {
        const p = document.createElement("p");
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }
});