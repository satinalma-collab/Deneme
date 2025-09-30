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
                            <div class="card task-card" data-card-id="<?php echo $card['id']; ?>" draggable="true">
                                <div class="card-title"><?php echo htmlspecialchars($card['title']); ?></div>
                                <?php if ($card['assigned_to']):
                                    $assignee = get_user_by_id($card['assigned_to']); ?>
                                    <div class="card-assignee">Atanan: <strong><?php echo htmlspecialchars($assignee['username']); ?></strong></div>
                                <?php endif; ?>
                                <div class="card-actions">
                                    <select class="assign-user-select" data-card-id="<?php echo $card['id']; ?>">
                                        <option value="0">Ata...</option>
                                        <?php foreach ($project_members as $member): ?>
                                            <option value="<?php echo $member['id']; ?>" <?php if ($card['assigned_to'] == $member['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($member['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if ($card['assigned_to']): ?>
                                            <option value="0">Atamayı Kaldır</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="add-card-form-container">
                    <button class="btn-open-add-card-modal" data-list-id="<?php echo $list['id']; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="margin-right: 8px;">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        <span>Yeni kart ekle</span>
                    </button>
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