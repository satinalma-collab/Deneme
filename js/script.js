document.addEventListener('DOMContentLoaded', () => {
    // --- Add Card ---

    document.querySelectorAll('.add-card-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const formContainer = button.previousElementSibling;
            formContainer.style.display = 'block';
            button.style.display = 'none';
        });
    });

    document.querySelectorAll('.cancel-add-card').forEach(button => {
        button.addEventListener('click', () => {
            const form = button.closest('.add-card-form');
            const toggleButton = form.parentElement.nextElementSibling;
            form.style.display = 'none';
            toggleButton.style.display = 'block';
            form.querySelector('.add-card-textarea').value = '';
        });
    });

    document.querySelectorAll('.add-card-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const textarea = form.querySelector('.add-card-textarea');
            const content = textarea.value.trim();
            const listElement = form.closest('.list');
            const listId = listElement.dataset.listId;

            if (content) {
                const formData = new FormData();
                formData.append('action', 'add_card');
                formData.append('list_id', listId);
                formData.append('content', content);

                fetch('api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const newCard = document.createElement('div');
                        newCard.classList.add('card');
                        newCard.dataset.cardId = data.card.id;
                        newCard.draggable = true;
                        newCard.textContent = data.card.content;

                        listElement.querySelector('.cards').appendChild(newCard);
                        textarea.value = '';
                        form.style.display = 'none';
                        listElement.querySelector('.add-card-toggle').style.display = 'block';
                        addDragAndDrop(newCard); // Add drag listeners to new card
                    }
                });
            }
        });
    });

    // --- Drag and Drop ---
    let draggedCard = null;

    function addDragAndDrop(card) {
        card.addEventListener('dragstart', (e) => {
            draggedCard = e.target;
            setTimeout(() => {
                e.target.style.display = 'none';
            }, 0);
        });

        card.addEventListener('dragend', (e) => {
            setTimeout(() => {
                draggedCard.style.display = 'block';
                draggedCard = null;
            }, 0);
        });
    }

    document.querySelectorAll('.card').forEach(card => {
        addDragAndDrop(card);
    });

    document.querySelectorAll('.list').forEach(list => {
        list.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        list.addEventListener('drop', (e) => {
            e.preventDefault();
            if (draggedCard) {
                const listElement = e.currentTarget;
                listElement.querySelector('.cards').appendChild(draggedCard);
                const cardId = draggedCard.dataset.cardId;
                const newListId = listElement.dataset.listId;

                const formData = new FormData();
                formData.append('action', 'move_card');
                formData.append('card_id', cardId);
                formData.append('new_list_id', newListId);

                fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
            }
        });
    });
});