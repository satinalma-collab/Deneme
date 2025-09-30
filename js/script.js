document.addEventListener('DOMContentLoaded', () => {

    const boardContainer = document.querySelector('.board-container');
    if (!boardContainer) return; // Sadece proje panosu sayfasında çalış

    const projectId = boardContainer.dataset.projectId;

    // --- Sayfa Yükleme Mantığı ---
    // URL'den gelen parametreye göre düzenleme modalını aç
    const urlParams = new URLSearchParams(window.location.search);
    const cardToOpen = urlParams.get('open_card');
    if (cardToOpen) {
        openEditCardModal(cardToOpen);
        // Tarayıcı geçmişini temizle
        window.history.replaceState({}, document.title, window.location.pathname + '?id=' + projectId);
    }

    // --- Olay Dinleyicileri ---
    initializeDragAndDrop();
    initializeModalOpeners();

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
    function initializeModalOpeners() {
        // Mevcut kartları düzenlemek için modal aç
        document.querySelectorAll('.task-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('.assign-form')) return;
                const cardId = card.dataset.cardId;
                openEditCardModal(cardId);
            });
        });

        // Yeni kart eklemek için modal aç
        document.querySelectorAll('.btn-open-add-card-modal').forEach(button => {
            button.addEventListener('click', () => {
                const listId = button.dataset.listId;
                openAddCardModal(listId);
            });
        });
    }

    // Sürükle-bırak işlevini (yeniden) başlatır
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

    // Kartı düzenlemek için modalı açar
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
                alert('Hata: ' + data.message);
            }
        });
    }

    // Yeni kart eklemek için modalı açar
    function openAddCardModal(listId) {
        renderAddModalContent(listId);
        modal.style.display = 'flex';
    }

    // API'ye kartın yeni pozisyonunu bildirir
    function updateCardPosition(projectId, cardId, newListId) {
        fetch(BASE_URL + 'api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'move_card', projectId, cardId, newListId })
        }).then(res => res.json()).then(data => {
            if (!data.success) console.error(data.message);
        }).catch(err => console.error(err));
    }

    // Modal içeriğini kart düzenleme formuyla doldurur
    function renderEditModalContent(card) {
        modalContentWrapper.innerHTML = `
            <form id="modal-card-form">
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

    // Modal içeriğini yeni kart ekleme formuyla doldurur
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

    // API'ye kart detaylarını güncelleme isteği gönderir
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
            } else {
                alert('Güncelleme hatası: ' . data.message);
            }
        });
    }

    // API'ye yeni kart ekleme isteği gönderir
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
            } else {
                alert('Ekleme hatası: ' + data.message);
            }
        });
    }

    // DOM'a yeni bir kart elementi oluşturur ve ekler
    function createCardElement(cardData) {
        const listContainer = document.querySelector(`.list[data-list-id="${cardData.list_id}"] .cards`);
        if (!listContainer) return;

        const cardEl = document.createElement('div');
        cardEl.className = 'card task-card';
        cardEl.dataset.cardId = cardData.id;
        cardEl.draggable = true;
        cardEl.innerHTML = `
            <div class="card-title">${escapeHTML(cardData.title)}</div>
            <div class="card-assignee"></div>
            <div class="card-actions">
                <form action="${BASE_URL}project.php?id=${projectId}" method="post" class="assign-form">
                    <input type="hidden" name="card_id" value="${cardData.id}">
                    <select name="member_id" onchange="this.form.submit()">
                        <option value="">Ata...</option>
                        ${document.querySelector('.assign-form select').innerHTML.match(/<option value="[1-9].*?<\/option>/g).join('')}
                    </select>
                    <input type="hidden" name="assign_card" value="1">
                </form>
            </div>
        `;

        listContainer.appendChild(cardEl);

        // Yeni karta da olay dinleyicileri ekle
        initializeDragAndDrop(); // Tüm sürükle-bırak olaylarını yeniden başlat
        cardEl.addEventListener('click', (e) => {
            if (e.target.closest('.assign-form')) return;
            openEditCardModal(cardData.id);
        });
    }

    function escapeHTML(str) {
        const p = document.createElement("p");
        p.appendChild(document.createTextNode(str || ""));
        return p.innerHTML;
    }
});