document.addEventListener('DOMContentLoaded', () => {

    const boardContainer = document.querySelector('.board-container');
    if (!boardContainer) return;

    const projectId = boardContainer.dataset.projectId;

    // --- Sayfa Yükleme Mantığı ---
    const urlParams = new URLSearchParams(window.location.search);
    const cardToOpen = urlParams.get('open_card');
    if (cardToOpen) {
        openEditCardModal(cardToOpen);
        window.history.replaceState({}, document.title, `${window.location.pathname}?id=${projectId}`);
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

    function initializeAllHandlers() {
        initializeDragAndDrop();
        initializeCardClickHandlers();
        initializeAddCardClickHandlers();
        initializeAssignmentPopoverHandlers();
    }

    // Olay delegasyonu ile sadece ana konteynere olay dinleyicileri ekle
    function initializeCardClickHandlers() {
        const listsContainer = document.querySelector('.lists-container');
        if(listsContainer) {
            listsContainer.addEventListener('click', e => {
                const card = e.target.closest('.task-card');
                // Sadece kartın kendisine tıklandığında ve atama butonu dışında bir yere tıklandığında modalı aç
                if (card && !e.target.closest('.btn-assign-user')) {
                    const cardId = card.dataset.cardId;
                    openEditCardModal(cardId);
                }
            });
        }
    }

    function initializeAddCardClickHandlers() {
        document.querySelectorAll('.btn-open-add-card-modal').forEach(button => {
            button.addEventListener('click', () => {
                const listId = button.dataset.listId;
                openAddCardModal(listId);
            });
        });
    }

    function initializeAssignmentPopoverHandlers() {
        const listsContainer = document.querySelector('.lists-container');
        if(listsContainer) {
            listsContainer.addEventListener('click', e => {
                const assignButton = e.target.closest('.btn-assign-user');
                if(assignButton) {
                    e.stopPropagation(); // Kartın tıklama olayını tetiklemesini engelle
                    const cardId = assignButton.dataset.cardId;
                    openAssigneePopover(assignButton, cardId);
                }
            });
        }
    }

    function openAssigneePopover(button, cardId) {
        closeExistingPopovers(); // Önceki popoverları kapat
        const popover = document.createElement('div');
        popover.className = 'assignee-popover';

        let listItems = window.projectMembers.map(member => `
            <li class="assignee-popover-item" data-member-id="${member.id}">
                <div class="assignee-popover-avatar">${escapeHTML(member.username.charAt(0).toUpperCase())}</div>
                <span>${escapeHTML(member.username)}</span>
            </li>
        `).join('');

        // Atamayı kaldırma seçeneği
        listItems += `<li class="assignee-popover-item" data-member-id="0" style="color: var(--accent-danger);">Atamayı Kaldır</li>`;

        popover.innerHTML = `
            <div class="assignee-popover-header">Üye Ata</div>
            <ul class="assignee-popover-list">${listItems}</ul>
        `;

        document.body.appendChild(popover);
        positionPopover(button, popover);

        // Popover içindeki bir üyeye tıklandığında
        popover.addEventListener('click', e => {
            const item = e.target.closest('.assignee-popover-item');
            if(item) {
                const memberId = item.dataset.memberId;
                assignCardToUser(cardId, memberId);
                closeExistingPopovers();
            }
        });

        // Dışarı tıklandığında popover'ı kapat
        setTimeout(() => {
            document.addEventListener('click', closePopoverOnClickOutside, { once: true });
        }, 0);
    }

    function positionPopover(button, popover) {
        const rect = button.getBoundingClientRect();
        popover.style.left = `${rect.left}px`;
        popover.style.top = `${rect.bottom + 8}px`;
    }

    function closeExistingPopovers() {
        document.querySelectorAll('.assignee-popover').forEach(p => p.remove());
    }

    function closePopoverOnClickOutside(e) {
        if (!e.target.closest('.assignee-popover')) {
            closeExistingPopovers();
        }
    }

    function initializeDragAndDrop() {
        // Bu fonksiyonun içeriği değişmedi, önceki haliyle aynı kalıyor.
        const listsContainer = document.querySelector('.lists-container');
        if (!listsContainer) return;
        let draggedCard = null;
        listsContainer.addEventListener('dragstart', e => { if (e.target.classList.contains('task-card')) { draggedCard = e.target; setTimeout(() => { if(draggedCard) draggedCard.style.opacity = '0.5'; }, 0); } });
        listsContainer.addEventListener('dragend', () => { if (draggedCard) { draggedCard.style.opacity = '1'; draggedCard = null; } });
        listsContainer.addEventListener('dragover', e => { e.preventDefault(); const list = e.target.closest('.list'); if (!list || !draggedCard) return; const cardsContainer = list.querySelector('.cards'); const afterElement = getDragAfterElement(cardsContainer, e.clientY); if (afterElement == null) { cardsContainer.appendChild(draggedCard); } else { cardsContainer.insertBefore(draggedCard, afterElement); } });
        listsContainer.addEventListener('drop', e => { e.preventDefault(); const list = e.target.closest('.list'); if (!list || !draggedCard) return; const newListId = list.dataset.listId; const cardId = draggedCard.dataset.cardId; updateCardPosition(projectId, cardId, newListId); });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.task-card:not([style*="opacity: 0.5"])')];
        return draggableElements.reduce((closest, child) => { const box = child.getBoundingClientRect(); const offset = y - box.top - box.height / 2; if (offset < 0 && offset > closest.offset) { return { offset: offset, element: child }; } else { return closest; } }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function openEditCardModal(cardId) {
        fetch(`${BASE_URL}api.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'get_card_details', projectId, cardId }) })
        .then(res => res.json()).then(data => { if (data.success) { renderEditModalContent(data.card); modal.style.display = 'flex'; } else { showToast(data.message, 'danger'); } });
    }

    function openAddCardModal(listId) {
        renderAddModalContent(listId);
        modal.style.display = 'flex';
    }

    // --- API Çağrıları ---

    function updateCardPosition(projectId, cardId, newListId) {
        fetch(`${BASE_URL}api.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'move_card', projectId, cardId, newListId }) })
        .then(res => res.json()).then(data => { if (!data.success) showToast(data.message, 'danger'); }).catch(err => console.error(err));
    }

    function assignCardToUser(cardId, memberId) {
        fetch(`${BASE_URL}api.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'assign_card', projectId, cardId, memberId }) })
        .then(res => res.json()).then(data => {
            if (data.success) {
                updateCardAssigneeUI(cardId, memberId);
                showToast(memberId != '0' ? 'Görev başarıyla atandı.' : 'Görev ataması kaldırıldı.', 'success');
            } else {
                showToast(data.message, 'danger');
            }
        });
    }

    function updateCardDetails(cardId, title, description) {
        fetch(`${BASE_URL}api.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'update_card_details', projectId, cardId, title, description }) })
        .then(res => res.json()).then(data => { if (data.success) { const cardOnBoard = document.querySelector(`.card[data-card-id="${cardId}"] .card-title`); if (cardOnBoard) cardOnBoard.textContent = title; modal.style.display = 'none'; showToast('Kart başarıyla güncellendi.', 'success'); } else { showToast('Güncelleme hatası: ' + data.message, 'danger'); } });
    }

    function addNewCard(listId, title, description) {
        fetch(`${BASE_URL}api.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'add_card', projectId, listId, title, description }) })
        .then(res => res.json()).then(data => { if (data.success) { createCardElement(data.card); modal.style.display = 'none'; showToast('Kart başarıyla eklendi.', 'success'); } else { showToast('Ekleme hatası: ' + data.message, 'danger'); } });
    }

    // --- DOM Manipülasyonu ---

    function renderEditModalContent(card) {
        modalContentWrapper.innerHTML = `<form id="modal-card-form"><h3>Görevi Düzenle</h3><div class="form-group"><label for="modal-card-title">Başlık</label><input type="text" id="modal-card-title" name="title" value="${escapeHTML(card.title)}" required></div><div class="form-group"><label for="modal-card-description">Açıklama</label><textarea id="modal-card-description" name="description" rows="6">${escapeHTML(card.description)}</textarea></div><button type="submit" class="btn">Kaydet</button></form>`;
        document.getElementById('modal-card-form').addEventListener('submit', (e) => { e.preventDefault(); updateCardDetails(card.id, document.getElementById('modal-card-title').value, document.getElementById('modal-card-description').value); });
    }

    function renderAddModalContent(listId) {
        modalContentWrapper.innerHTML = `<form id="modal-add-card-form"><h3>Yeni Kart Ekle</h3><div class="form-group"><label for="modal-new-card-title">Başlık</label><input type="text" id="modal-new-card-title" name="title" required></div><div class="form-group"><label for="modal-new-card-description">Açıklama</label><textarea id="modal-new-card-description" name="description" rows="6"></textarea></div><button type="submit" class="btn">Kartı Oluştur</button></form>`;
        document.getElementById('modal-add-card-form').addEventListener('submit', (e) => { e.preventDefault(); addNewCard(listId, document.getElementById('modal-new-card-title').value, document.getElementById('modal-new-card-description').value); });
    }

    function updateCardAssigneeUI(cardId, memberId) {
        const cardElement = document.querySelector(`.card[data-card-id="${cardId}"]`);
        if (!cardElement) return;
        const assigneesContainer = cardElement.querySelector('.card-assignees');
        assigneesContainer.innerHTML = ''; // Mevcut avatarı temizle
        if (memberId && memberId != '0') {
            const member = window.projectMembers.find(m => m.id == memberId);
            if (member) {
                const avatar = document.createElement('div');
                avatar.className = 'assignee-avatar';
                avatar.title = `Atanan: ${escapeHTML(member.username)}`;
                avatar.textContent = member.username.charAt(0).toUpperCase();
                assigneesContainer.appendChild(avatar);
            }
        }
    }

    function createCardElement(cardData) {
        const listContainer = document.querySelector(`.list[data-list-id="${cardData.list_id}"] .cards`);
        if (!listContainer) return;
        const cardEl = document.createElement('div');
        cardEl.className = 'card task-card';
        cardEl.dataset.cardId = cardData.id;
        cardEl.draggable = true;
        cardEl.innerHTML = `<div class="card-title">${escapeHTML(cardData.title)}</div><div class="card-footer"><div class="card-assignees"></div><button class="btn-assign-user" data-card-id="${cardData.id}" title="Görevli Ata"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg></button></div>`;
        listContainer.appendChild(cardEl);
    }

    function escapeHTML(str) {
        const p = document.createElement("p");
        p.appendChild(document.createTextNode(str || ""));
        return p.innerHTML;
    }

    // --- Toast Bildirim Fonksiyonu ---
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if(!container) return;
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 5000);
    }
});