<?php
$page_title = 'Kontrol Paneli';
require_once __DIR__ . '/includes/header.php';

// Kullanıcı giriş yapmamışsa, giriş sayfasına yönlendir
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$error_message = '';
$current_user_id = get_current_user_id();

// Yeni proje oluşturma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $project_name = trim($_POST['project_name'] ?? '');

    if (empty($project_name)) {
        $error_message = "Proje adı boş bırakılamaz.";
    } else {
        $projects = read_db(PROJECTS_FILE);

        $new_project = [
            'id' => count($projects) + 1,
            'name' => $project_name,
            'owner_id' => $current_user_id,
            'members' => [$current_user_id], // Kurucu otomatik olarak üyedir
            'lists' => [], // Başlangıçta listeler boş
            'cards' => []  // Başlangıçta kartlar boş
        ];

        $projects[] = $new_project;
        write_db(PROJECTS_FILE, $projects);

        // Sayfayı yenileyerek formu tekrar göndermeyi engelle
        header('Location: dashboard.php');
        exit;
    }
}

// Kullanıcının dahil olduğu projeleri getir
$all_projects = read_db(PROJECTS_FILE);
$user_projects = [];
foreach ($all_projects as $project) {
    if (in_array($current_user_id, $project['members'])) {
        $user_projects[] = $project;
    }
}

// Proje silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $project_id_to_delete = filter_input(INPUT_POST, 'project_id_to_delete', FILTER_VALIDATE_INT);

    if ($project_id_to_delete) {
        $projects_before_delete = read_db(PROJECTS_FILE);
        $project_to_delete = null;
        foreach($projects_before_delete as $p) {
            if ($p['id'] === $project_id_to_delete) {
                $project_to_delete = $p;
                break;
            }
        }

        // Sadece proje sahibi silebilir
        if ($project_to_delete && $project_to_delete['owner_id'] === $current_user_id) {
            $updated_projects = array_filter($projects_before_delete, fn($p) => $p['id'] !== $project_id_to_delete);
            write_db(PROJECTS_FILE, array_values($updated_projects));
            header('Location: ' . url('dashboard.php?deleted=true'));
            exit;
        }
    }
}
?>

<div class="dashboard-header">
    <h2>Projelerim</h2>
    <p>Mevcut projelerinizi görüntüleyin veya yenisini oluşturun.</p>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<!-- Yeni Proje Oluşturma Formu -->
<div class="card form-container">
    <h3>Yeni Proje Oluştur</h3>
    <form action="<?php echo url('dashboard.php'); ?>" method="post">
        <div class="form-group">
            <input type="text" name="project_name" placeholder="Projenizin adını girin..." required>
            <button type="submit" name="create_project" class="btn">Oluştur</button>
        </div>
    </form>
</div>

<!-- Proje Listesi -->
<div class="project-list">
    <h3>Mevcut Projeler</h3>
    <?php if (empty($user_projects)): ?>
        <p>Henüz bir projeniz bulunmuyor. Yukarıdaki formdan yeni bir proje oluşturabilirsiniz.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($user_projects as $project): ?>
                <li class="project-item">
                    <a href="<?php echo url('project.php?id=' . $project['id']); ?>">
                        <?php echo htmlspecialchars($project['name']); ?>
                    </a>
                    <?php if ($project['owner_id'] === $current_user_id): ?>
                        <form action="<?php echo url('dashboard.php'); ?>" method="post" onsubmit="return confirm('Bu projeyi ve içindeki tüm verileri kalıcı olarak silmek istediğinizden emin misiniz?');" class="delete-project-form">
                            <input type="hidden" name="project_id_to_delete" value="<?php echo $project['id']; ?>">
                            <button type="submit" name="delete_project" class="btn-delete" title="Projeyi Sil">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>