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
                <li>
                    <a href="<?php echo url('project.php?id=' . $project['id']); ?>">
                        <?php echo htmlspecialchars($project['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>