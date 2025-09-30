document.addEventListener('DOMContentLoaded', () => {

    const boardContainer = document.querySelector('.board-container');
    if (!boardContainer) return; // Sadece proje panosu sayfasında çalış

    const projectId = boardContainer.dataset.projectId;

    // --- Sayfa Yükleme Mantığı ---
    const urlParams = new URLSearchParams(window.location.search);
    const cardToOpen = urlParams.get('open_card');
    if (cardToOpen) {
        openEditCardModal(cardToOpen);
        window.history.replaceState({}, document.title, window.location.pathname + '?id=' + projectId);
    }

    // --- Olay Dinleyicileri ---
    initializeAllHandlers();

    // --- Modal Yönetimi ---
    const modal = document.getElementById('card-modal');
    const modalContentWrapper = document.getElementById('modal-card-content-wrapper');
    const closeModalBtn = document.querySelector('.modal-close-btn');

    closeModalBtn.addEventListener('click', () => modal.style.display = 'none');
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // --- Fonksiyonlar ---

    // Mevcut tüm kartlara ve butonlara olay dinleyicilerini atar
    function initializeAllHandlers() {
        initializeDragAndDrop();
        initializeCardClickHandlers();
        initializeAddCardClickHandlers();
        initializeAssignmentHandlers();
    }

    function initializeCardClickHandlers() {
        document.querySelectorAll('.task-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('.assign-user-select')) return;
                const cardId = card.dataset.cardId;
                openEditCardModal(cardId);
            });
        });
    }

    function initializeAddCardClickHandlers() {
        document.querySelectorAll('.btn-open-add-card-modal').forEach(button => {
            button.addEventListener('click', () => {
                const listId = button.dataset.listId;
                openAddCardModal(listId);
            });
        });
    }

    function initializeAssignmentHandlers() {
        document.querySelectorAll('.assign-user-select').forEach(select => {
            select.addEventListener('change', handleAssignmentChange);
        });
    }

    function handleAssignmentChange(e) {
        const select = e.target;
        const cardId = select.dataset.cardId;
        const memberId = select.value;
        assignCardToUser(cardId, memberId, select);
    }

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
                if(draggedCard) draggedCard.style.opacity = '1';
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
                if(!draggedCard) return;
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

    function openEditCardModal(cardId) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_card_details', projectId, cardId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderEditModalContent(data.card);
                modal.style.display = 'flex';
            } else {
                showToast(data.message, 'danger');
            }
        });
    }

    function openAddCardModal(listId) {
        renderAddModalContent(listId);
        modal.style.display = 'flex';
    }

    // --- API Çağrıları ---

    function updateCardPosition(projectId, cardId, newListId) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'move_card', projectId, cardId, newListId })
        }).then(res => res.json()).then(data => {
            if (!data.success) console.error(data.message);
        }).catch(err => console.error(err));
    }

    function assignCardToUser(cardId, memberId, selectElement) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'assign_card', projectId, cardId, memberId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const cardElement = document.querySelector(`.card[data-card-id="${cardId}"]`);
                const assigneeDiv = cardElement.querySelector('.card-assignee');
                if (memberId != '0') {
                    const memberName = selectElement.options[selectElement.selectedIndex].text;
                    assigneeDiv.innerHTML = `Atanan: <strong>${escapeHTML(memberName)}</strong>`;
                    showToast('Görev başarıyla atandı.', 'success');
                } else {
                    assigneeDiv.innerHTML = '';
                    showToast('Görev ataması kaldırıldı.', 'info');
                }
            } else {
                showToast(data.message, 'danger');
            }
        });
    }

    function updateCardDetails(cardId, title, description) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_card_details', projectId, cardId, title, description })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const cardOnBoard = document.querySelector(`.card[data-card-id="${cardId}"] .card-title`);
                if (cardOnBoard) cardOnBoard.textContent = title;
                modal.style.display = 'none';
                showToast('Kart başarıyla güncellendi.', 'success');
            } else {
                showToast('Güncelleme hatası: ' + data.message, 'danger');
            }
        });
    }

    function addNewCard(listId, title, description) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add_card', projectId, listId, title, description })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                createCardElement(data.card);
                modal.style.display = 'none';
                showToast('Kart başarıyla eklendi.', 'success');
            } else {
                showToast('Ekleme hatası: ' + data.message, 'danger');
            }
        });
    }

    // --- DOM Manipülasyonu ---

    function renderEditModalContent(card) {
        modalContentWrapper.innerHTML = `
            <form id="modal-card-form">
                <h3>Görevi Düzenle</h3>
                <div class="form-group">
                    <label for="modal-card-title">Başlık</label>
                    <input type="text" id="modal-card-title" name="title" value="${escapeHTML(card.title)}" required>
                </div>
                <div class="form-group">
                    <label for="modal-card-description">Açıklama</label>
                    <textarea id="modal-card-description" name="description" rows="6">${escapeHTML(card.description)}</textarea>
                </div>
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

    function renderAddModalContent(listId) {
        modalContentWrapper.innerHTML = `
            <form id="modal-add-card-form">
                <h3>Yeni Kart Ekle</h3>
                <div class="form-group">
                    <label for="modal-new-card-title">Başlık</label>
                    <input type="text" id="modal-new-card-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="modal-new-card-description">Açıklama</label>
                    <textarea id="modal-new-card-description" name="description" rows="6"></textarea>
                </div>
                <button type="submit" class="btn">Kartı Oluştur</button>
            </form>
        `;

        document.getElementById('modal-add-card-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const title = document.getElementById('modal-new-card-title').value;
            const description = document.getElementById('modal-new-card-description').value;
            addNewCard(listId, title, description);
        });
    }

    function createCardElement(cardData) {
        const listContainer = document.querySelector(`.list[data-list-id="${cardData.list_id}"] .cards`);
        if (!listContainer) return;

        const cardEl = document.createElement('div');
        cardEl.className = 'card task-card';
        cardEl.dataset.cardId = cardData.id;
        cardEl.draggable = true;

        const firstSelect = document.querySelector('.assign-user-select');
        let optionsHTML = '<option value="0">Ata...</option>';
        if (firstSelect) {
            const memberOptions = firstSelect.querySelectorAll('option[value]:not([value="0"])');
            memberOptions.forEach(opt => {
                if (opt.text !== "Atamayı Kaldır") {
                     optionsHTML += opt.outerHTML;
                }
            });
        }

        cardEl.innerHTML = `
            <div class="card-title">${escapeHTML(cardData.title)}</div>
            <div class="card-assignee"></div>
            <div class="card-actions">
                <select class="assign-user-select" data-card-id="${cardData.id}">
                    ${optionsHTML}
                </select>
            </div>
        `;

        listContainer.appendChild(cardEl);

        // Yeni karta da olay dinleyicileri ekle
        cardEl.addEventListener('dragstart', () => { /* ... */ }); // Basitleştirilmiş, tam sürükle-bırak yeniden başlatılmalı
        cardEl.addEventListener('click', (e) => {
            if (e.target.closest('.assign-user-select')) return;
            openEditCardModal(cardData.id);
        });
        cardEl.querySelector('.assign-user-select').addEventListener('change', handleAssignmentChange);
    }

    function escapeHTML(str) {
        const p = document.createElement("p");
        p.appendChild(document.createTextNode(str || ""));
        return p.innerHTML;
    }

    // --- Toast Bildirim Fonksiyonu ---
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;

        container.appendChild(toast);

        // Animasyon için kısa bir gecikme
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // 5 saniye sonra tostu kaldır
        setTimeout(() => {
            toast.classList.remove('show');
            // Animasyon bittikten sonra DOM'dan kaldır
            toast.addEventListener('transitionend', () => toast.remove());
        }, 5000);
    }
});