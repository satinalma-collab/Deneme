<?php

header('Content-Type: application/json');

// --- Utility Functions ---

function get_data($file) {
    $json = file_get_contents("data/{$file}.json");
    return json_decode($json, true);
}

function save_data($file, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents("data/{$file}.json", $json);
}

function get_next_id($items) {
    $max_id = 0;
    foreach ($items as $item) {
        if ($item['id'] > $max_id) {
            $max_id = $item['id'];
        }
    }
    return $max_id + 1;
}

// --- API Logic ---

$action = $_POST['action'] ?? null;
$response = ['success' => false, 'message' => 'Invalid action.'];

switch ($action) {
    case 'add_card':
        $list_id = (int)($_POST['list_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($list_id && !empty($content)) {
            $cards = get_data('cards');
            $new_card = [
                'id' => get_next_id($cards),
                'list_id' => $list_id,
                'content' => $content
            ];
            $cards[] = $new_card;
            save_data('cards', $cards);
            $response = ['success' => true, 'card' => $new_card];
        } else {
            $response['message'] = 'Missing list_id or content.';
        }
        break;

    case 'move_card':
        $card_id = (int)($_POST['card_id'] ?? 0);
        $new_list_id = (int)($_POST['new_list_id'] ?? 0);

        if ($card_id && $new_list_id) {
            $cards = get_data('cards');
            $card_found = false;
            foreach ($cards as &$card) {
                if ($card['id'] == $card_id) {
                    $card['list_id'] = $new_list_id;
                    $card_found = true;
                    break;
                }
            }
            if ($card_found) {
                save_data('cards', $cards);
                $response = ['success' => true];
            } else {
                $response['message'] = 'Card not found.';
            }
        } else {
            $response['message'] = 'Missing card_id or new_list_id.';
        }
        break;
}

echo json_encode($response);