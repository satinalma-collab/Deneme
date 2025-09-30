<?php
$page_title = 'Proje Panosu';
$body_class = 'board-active'; // Proje panosu için özel body sınıfı
require_once __DIR__ . '/includes/header.php'; // auth.php ve database.php'yi zaten içeriyor

// --- GÜVENLİK VE VERİ YÜKLEME ---

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$project_id) {
    header('Location: dashboard.php?error=invalid_project');
    exit;
}

$all_projects = read_db(PROJECTS_FILE);
$project = null;
$project_key = null;
foreach ($all_projects as $key => $p) {
    if ($p['id'] === $project_id) {
        $project = $p;
        $project_key = $key;
        break;
    }
}

if ($project === null) {
    header('Location: dashboard.php?error=project_not_found');
    exit;
}

$current_user_id = get_current_user_id();
if (!in_array($current_user_id, $project['members'])) {
    echo "<div class='alert alert-danger'>Bu projeyi görüntüleme yetkiniz yok.</div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

$is_owner = ($project['owner_id'] === $current_user_id);

// --- POST İŞLEMLERİ ---

$error_message = '';
$success_message = '';

require_once __DIR__ . '/php/notifications.php';

// Liste Ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_list'])) {
    $list_name = trim($_POST['list_name'] ?? '');
    if (!empty($list_name)) {
        $new_list = ['id' => empty($project['lists']) ? 1 : max(array_column($project['lists'], 'id')) + 1, 'name' => $list_name];
        $all_projects[$project_key]['lists'][] = $new_list;
        if (write_db(PROJECTS_FILE, $all_projects)) {
            header('Location: project.php?id=' . $project_id);
            exit;
        }
    }
}

// Kart Ekleme (Gelişmiş veri yapısıyla)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_card'])) {
    $card_title = trim($_POST['card_title'] ?? '');
    $list_id = filter_input(INPUT_POST, 'list_id', FILTER_VALIDATE_INT);
    if (!empty($card_title) && $list_id) {
        $new_card_id = empty($project['cards']) ? 1 : max(array_column($project['cards'], 'id')) + 1;
        $new_card = [
            'id' => $new_card_id,
            'list_id' => $list_id,
            'title' => $card_title,
            'description' => '',
            'assigned_to' => null,
            'dueDate' => null,
            'labels' => [],
            'comments' => []
        ];
        $all_projects[$project_key]['cards'][] = $new_card;
        if (write_db(PROJECTS_FILE, $all_projects)) {
            // Modal'ı otomatik açmak için parametre ile yönlendir
            header('Location: ' . url('project.php?id=' . $project_id . '&open_card=' . $new_card_id));
            exit;
        }
    }
}

// Üye Ekleme
if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $member_email = trim($_POST['member_email'] ?? '');
    $user_to_add = get_user_by_email($member_email);

    if (!$user_to_add) {
        $error_message = "Bu e-posta adresine sahip bir kullanıcı bulunamadı.";
    } elseif (in_array($user_to_add['id'], $project['members'])) {
        $error_message = "Bu kullanıcı zaten projeye üye.";
    } else {
        $all_projects[$project_key]['members'][] = $user_to_add['id'];
        if (write_db(PROJECTS_FILE, $all_projects)) {
            $success_message = htmlspecialchars($user_to_add['username']) . " projeye eklendi.";
            $project['members'][] = $user_to_add['id'];
        } else {
            $error_message = "Kullanıcı eklenirken bir hata oluştu.";
        }
    }
}

// Görev Atama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_card'])) {
    $card_id_to_assign = filter_input(INPUT_POST, 'card_id', FILTER_VALIDATE_INT);
    $member_id_to_assign = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    $assigned_card_title = null;
    $previous_assignee = null;

    foreach ($all_projects[$project_key]['cards'] as &$card) {
        if ($card['id'] === $card_id_to_assign) {
            $previous_assignee = $card['assigned_to'];
            $assigned_card_title = $card['title'];
            $card['assigned_to'] = ($member_id_to_assign == 0) ? null : $member_id_to_assign;
            break;
        }
    }

    if ($member_id_to_assign != 0 && $member_id_to_assign != $previous_assignee) {
        $project_name = $project['name'];
        $message = "Sana \"" . htmlspecialchars($project_name) . "\" projesinde yeni bir görev atandı: " . htmlspecialchars($assigned_card_title);
        $link = "project.php?id=" . $project_id;

        if ($member_id_to_assign != $current_user_id) {
            create_notification($member_id_to_assign, $message, $link);
        }
    }

    if (write_db(PROJECTS_FILE, $all_projects)) {
        header('Location: project.php?id=' . $project_id);
        exit;
    }
}


// --- VERİ HAZIRLAMA ---

$page_title = htmlspecialchars($project['name']);
$project_members = [];
foreach ($project['members'] as $member_id) {
    $user = get_user_by_id($member_id);
    if ($user) $project_members[] = $user;
}

// --- ARAYÜZ ---
?>

<div class="project-header">
    <h2><?php echo $page_title; ?></h2>
    <div class="project-members">
        <strong>Üyeler:</strong>
        <?php foreach ($project_members as $member): ?>
            <span class="member-tag"><?php echo htmlspecialchars($member['username']); ?></span>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($is_owner): ?>
<div class="card form-container member-management">
    <h3>Üye Ekle</h3>
    <form action="<?php echo url('project.php?id=' . $project_id); ?>" method="post">
        <div class="form-group">
            <input type="email" name="member_email" placeholder="Kullanıcının e-posta adresini girin..." required>
            <button type="submit" name="add_member" class="btn">Ekle</button>
        </div>
    </form>
    <?php if(!empty($error_message)) echo "<div class='alert alert-danger mt-2'>$error_message</div>"; ?>
    <?php if(!empty($success_message)) echo "<div class='alert alert-success mt-2'>$success_message</div>"; ?>
</div>
<?php endif; ?>

<div class="board-container" data-project-id="<?php echo $project_id; ?>">
    <div class="lists-container">
        <?php foreach ($project['lists'] as $list): ?>
            <div class="list" data-list-id="<?php echo $list['id']; ?>">
                <h3 class="list-title"><?php echo htmlspecialchars($list['name']); ?></h3>
                <div class="cards">
                    <?php foreach ($project['cards'] as $card): ?>
                        <?php if ($card['list_id'] === $list['id']): ?>
                            <div class="card" data-card-id="<?php echo $card['id']; ?>" draggable="true">
                                <div class="card-title"><?php echo htmlspecialchars($card['title']); ?></div>
                                <?php if ($card['assigned_to']):
                                    $assignee = get_user_by_id($card['assigned_to']); ?>
                                    <div class="card-assignee">Atanan: <strong><?php echo htmlspecialchars($assignee['username']); ?></strong></div>
                                <?php endif; ?>
                                <div class="card-actions">
                                    <form action="<?php echo url('project.php?id=' . $project_id); ?>" method="post" class="assign-form">
                                        <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                        <select name="member_id" onchange="this.form.submit()">
                                            <option value="">Ata...</option>
                                            <?php foreach ($project_members as $member): ?>
                                                <option value="<?php echo $member['id']; ?>" <?php if ($card['assigned_to'] == $member['id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($member['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if ($card['assigned_to']): ?>
                                                <option value="0">Atamayı Kaldır</option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="hidden" name="assign_card" value="1">
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="add-card-form-container">
                    <form action="<?php echo url('project.php?id=' . $project_id); ?>" method="post">
                        <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                        <textarea name="card_title" placeholder="+ Yeni bir kart ekle..." required></textarea>
                        <button type="submit" name="add_card" class="btn-add-card">Ekle</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="list add-new-list">
            <form action="<?php echo url('project.php?id=' . $project_id); ?>" method="post">
                <input type="text" name="list_name" placeholder="+ Yeni bir liste ekle" required>
                <button type="submit" name="add_list" class="btn-add-list">Ekle</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>