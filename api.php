<?php
// Hasello - API Uç Noktası

header('Content-Type: application/json');

// Gerekli dosyaları dahil et
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/database.php';

// --- API Güvenlik ve Yetkilendirme ---

// Kullanıcı giriş yapmamışsa işlemi reddet
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim. Lütfen giriş yapın.']);
    exit;
}

// Gelen isteği JSON formatından PHP dizisine çevir
$request_data = json_decode(file_get_contents('php://input'), true);

$action = $request_data['action'] ?? null;
$response = ['success' => false, 'message' => 'Geçersiz eylem.'];

if (!$action) {
    echo json_encode($response);
    exit;
}

// --- Eylemleri Yönet ---

switch ($action) {
    case 'move_card':
        $project_id = filter_var($request_data['projectId'] ?? 0, FILTER_VALIDATE_INT);
        $card_id = filter_var($request_data['cardId'] ?? 0, FILTER_VALIDATE_INT);
        $new_list_id = filter_var($request_data['newListId'] ?? 0, FILTER_VALIDATE_INT);
        $current_user_id = get_current_user_id();

        if (!$project_id || !$card_id || !$new_list_id) {
            $response['message'] = 'Eksik parametreler.';
            break;
        }

        $all_projects = read_db(PROJECTS_FILE);
        $project_key = null;
        foreach ($all_projects as $key => $p) {
            if ($p['id'] === $project_id) {
                $project_key = $key;
                break;
            }
        }

        // Proje bulunamazsa veya kullanıcı üye değilse
        if ($project_key === null || !in_array($current_user_id, $all_projects[$project_key]['members'])) {
            $response['message'] = 'Bu işlem için yetkiniz yok.';
            break;
        }

        // Kartı bul ve listesini güncelle
        $card_updated = false;
        foreach ($all_projects[$project_key]['cards'] as &$card) {
            if ($card['id'] === $card_id) {
                $card['list_id'] = $new_list_id;
                $card_updated = true;
                break;
            }
        }

        if ($card_updated && write_db(PROJECTS_FILE, $all_projects)) {
            $response = ['success' => true, 'message' => 'Kart başarıyla taşındı.'];
        } else {
            $response['message'] = 'Kart güncellenirken bir hata oluştu.';
        }
        break;

    case 'get_card_details':
        $project_id = filter_var($request_data['projectId'] ?? 0, FILTER_VALIDATE_INT);
        $card_id = filter_var($request_data['cardId'] ?? 0, FILTER_VALIDATE_INT);
        $current_user_id = get_current_user_id();

        if (!$project_id || !$card_id) {
            $response['message'] = 'Eksik parametreler.';
            break;
        }

        $all_projects = read_db(PROJECTS_FILE);
        $project = null;
        foreach ($all_projects as $p) {
            if ($p['id'] === $project_id) {
                $project = $p;
                break;
            }
        }

        if ($project === null || !in_array($current_user_id, $project['members'])) {
            $response['message'] = 'Bu işlem için yetkiniz yok.';
            break;
        }

        $card_details = null;
        foreach ($project['cards'] as $card) {
            if ($card['id'] === $card_id) {
                $card_details = $card;
                break;
            }
        }

        if ($card_details) {
            $response = ['success' => true, 'card' => $card_details];
        } else {
            $response['message'] = 'Kart bulunamadı.';
        }
        break;

    case 'update_card_details':
        $project_id = filter_var($request_data['projectId'] ?? 0, FILTER_VALIDATE_INT);
        $card_id = filter_var($request_data['cardId'] ?? 0, FILTER_VALIDATE_INT);
        $title = trim($request_data['title'] ?? '');
        $description = trim($request_data['description'] ?? '');
        $current_user_id = get_current_user_id();

        if (!$project_id || !$card_id || empty($title)) {
            $response['message'] = 'Eksik veya geçersiz parametreler.';
            break;
        }

        $all_projects = read_db(PROJECTS_FILE);
        $project_key = null;
        foreach ($all_projects as $key => $p) {
            if ($p['id'] === $project_id) {
                $project_key = $key;
                break;
            }
        }

        if ($project_key === null || !in_array($current_user_id, $all_projects[$project_key]['members'])) {
            $response['message'] = 'Bu işlem için yetkiniz yok.';
            break;
        }

        $card_updated = false;
        foreach ($all_projects[$project_key]['cards'] as &$card) {
            if ($card['id'] === $card_id) {
                $card['title'] = $title;
                $card['description'] = $description;
                $card_updated = true;
                break;
            }
        }

        if ($card_updated && write_db(PROJECTS_FILE, $all_projects)) {
            $response = ['success' => true, 'message' => 'Kart başarıyla güncellendi.'];
        } else {
            $response['message'] = 'Kart güncellenirken bir hata oluştu.';
        }
        break;

    case 'add_card':
        $project_id = filter_var($request_data['projectId'] ?? 0, FILTER_VALIDATE_INT);
        $list_id = filter_var($request_data['listId'] ?? 0, FILTER_VALIDATE_INT);
        $title = trim($request_data['title'] ?? '');
        $description = trim($request_data['description'] ?? '');
        $current_user_id = get_current_user_id();

        if (!$project_id || !$list_id || empty($title)) {
            $response['message'] = 'Eksik parametreler (proje, liste veya başlık).';
            break;
        }

        $all_projects = read_db(PROJECTS_FILE);
        $project_key = null;
        foreach ($all_projects as $key => $p) {
            if ($p['id'] === $project_id) {
                $project_key = $key;
                break;
            }
        }

        if ($project_key === null || !in_array($current_user_id, $all_projects[$project_key]['members'])) {
            $response['message'] = 'Bu işlem için yetkiniz yok.';
            break;
        }

        $new_card_id = empty($all_projects[$project_key]['cards']) ? 1 : max(array_column($all_projects[$project_key]['cards'], 'id')) + 1;
        $new_card = [
            'id' => $new_card_id,
            'list_id' => $list_id,
            'title' => $title,
            'description' => $description,
            'assigned_to' => null,
            'dueDate' => null,
            'labels' => [],
            'comments' => []
        ];

        $all_projects[$project_key]['cards'][] = $new_card;

        if (write_db(PROJECTS_FILE, $all_projects)) {
            $response = ['success' => true, 'card' => $new_card, 'message' => 'Kart başarıyla eklendi.'];
        } else {
            $response['message'] = 'Kart eklenirken bir hata oluştu.';
        }
        break;

    case 'assign_card':
        $project_id = filter_var($request_data['projectId'] ?? 0, FILTER_VALIDATE_INT);
        $card_id = filter_var($request_data['cardId'] ?? 0, FILTER_VALIDATE_INT);
        $member_id = filter_var($request_data['memberId'] ?? 0, FILTER_VALIDATE_INT); // 0 atamayı kaldırmak için
        $current_user_id = get_current_user_id();

        if (!$project_id || !$card_id) {
            $response['message'] = 'Eksik parametreler.';
            break;
        }

        $all_projects = read_db(PROJECTS_FILE);
        $project_key = null;
        $project_name = '';

        foreach ($all_projects as $key => $p) {
            if ($p['id'] === $project_id) {
                $project_key = $key;
                $project_name = $p['name'];
                break;
            }
        }

        if ($project_key === null || !in_array($current_user_id, $all_projects[$project_key]['members'])) {
            $response['message'] = 'Bu işlem için yetkiniz yok.';
            break;
        }

        $card_updated = false;
        $assigned_card_title = null;
        $previous_assignee = null;

        foreach ($all_projects[$project_key]['cards'] as &$card) {
            if ($card['id'] === $card_id) {
                $previous_assignee = $card['assigned_to'];
                $assigned_card_title = $card['title'];
                $card['assigned_to'] = ($member_id == 0) ? null : $member_id;
                $card_updated = true;
                break;
            }
        }

        if ($card_updated && write_db(PROJECTS_FILE, $all_projects)) {
            // Bildirim mantığı
            if ($member_id != 0 && $member_id != $previous_assignee && $member_id != $current_user_id) {
                require_once __DIR__ . '/php/notifications.php';
                $message = "Sana \"" . htmlspecialchars($project_name) . "\" projesinde yeni bir görev atandı: " . htmlspecialchars($assigned_card_title);
                $link = "project.php?id=" . $project_id;
                create_notification($member_id, $message, $link);
            }
            $response = ['success' => true, 'message' => 'Görevli atandı.'];
        } else {
            $response['message'] = 'Görevli atanırken bir hata oluştu.';
        }
        break;

    default:
        $response['message'] = 'Bilinmeyen eylem.';
        break;
}

echo json_encode($response);
?>