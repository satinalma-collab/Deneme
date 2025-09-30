<?php

// --- Data Functions ---

function get_data($file) {
    $json = file_get_contents("data/{$file}.json");
    return json_decode($json, true);
}

function get_board($id = 1) {
    $boards = get_data('boards');
    foreach ($boards as $board) {
        if ($board['id'] == $id) {
            return $board;
        }
    }
    return null;
}

function get_lists_for_board($board_id) {
    $lists = get_data('lists');
    $board_lists = [];
    foreach ($lists as $list) {
        if ($list['board_id'] == $board_id) {
            $board_lists[] = $list;
        }
    }
    return $board_lists;
}

function get_cards_for_list($list_id) {
    $cards = get_data('cards');
    $list_cards = [];
    foreach ($cards as $card) {
        if ($card['list_id'] == $list_id) {
            $list_cards[] = $card;
        }
    }
    return $list_cards;
}

// --- Current Board Data ---

$board = get_board(1); // Default to board 1
$lists = get_lists_for_board($board['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trello Clone</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="board-container">
        <h1 class="board-title"><?php echo htmlspecialchars($board['name']); ?></h1>
        <div class="lists-container">
            <?php foreach ($lists as $list): ?>
                <div class="list" data-list-id="<?php echo $list['id']; ?>">
                    <h2 class="list-title"><?php echo htmlspecialchars($list['name']); ?></h2>
                    <div class="cards">
                        <?php $cards = get_cards_for_list($list['id']); ?>
                        <?php foreach ($cards as $card): ?>
                            <div class="card" data-card-id="<?php echo $card['id']; ?>" draggable="true">
                                <?php echo htmlspecialchars($card['content']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="add-card-form-container">
                        <form class="add-card-form" style="display: none;">
                            <textarea class="add-card-textarea" placeholder="Enter a title for this card..."></textarea>
                            <button type="submit" class="add-card-button">Add Card</button>
                            <button type="button" class="cancel-add-card">X</button>
                        </form>
                        <button class="add-card-toggle">+ Add a card</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>