<?php
$page_title = 'Arkadaşlar';
require_once __DIR__ . '/includes/header.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/php/friends.php';

$current_user_id = get_current_user_id();
$message = '';
$message_type = 'info';

// --- POST İŞLEMLERİ ---
// Arkadaşlık İsteği Gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $email = trim($_POST['email'] ?? '');
    $result = send_friend_request($current_user_id, $email);
    if ($result !== true) {
        $message = $result;
        $message_type = 'danger';
    } else {
        $message = 'Arkadaşlık isteği başarıyla gönderildi.';
        $message_type = 'success';
    }
}

// Arkadaşlık İsteği Yanıtlama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['handle_request'])) {
    $requester_id = filter_input(INPUT_POST, 'requester_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? ''; // 'accept' or 'decline'

    if ($action === 'accept') {
        accept_friend_request($current_user_id, $requester_id);
        $message = "Arkadaşlık isteği kabul edildi.";
        $message_type = 'success';
    } elseif ($action === 'decline') {
        decline_friend_request($current_user_id, $requester_id);
        $message = "Arkadaşlık isteği reddedildi.";
        $message_type = 'info';
    }
}


// --- VERİLERİ HAZIRLAMA ---
$current_user_data = get_user_by_id($current_user_id);
$friend_requests = [];
foreach ($current_user_data['friend_requests_received'] as $requester_id) {
    $requester = get_user_by_id($requester_id);
    if ($requester) $friend_requests[] = $requester;
}
$friends = [];
foreach ($current_user_data['friends'] as $friend_id) {
    $friend = get_user_by_id($friend_id);
    if ($friend) $friends[] = $friend;
}

?>

<div class="friends-page">
    <h2>Arkadaş Yönetimi</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- 1. Arkadaş Ekle -->
    <div class="card form-container">
        <h3>Yeni Arkadaş Ekle</h3>
        <form action="<?php echo url('friends.php'); ?>" method="post">
            <div class="form-group">
                <input type="email" name="email" placeholder="Arkadaşının e-posta adresini gir..." required>
                <button type="submit" name="send_request" class="btn">İstek Gönder</button>
            </div>
        </form>
    </div>

    <!-- 2. Gelen İstekler -->
    <div class="friend-requests">
        <h3>Gelen Arkadaşlık İstekleri</h3>
        <?php if (empty($friend_requests)): ?>
            <p>Yeni arkadaşlık isteğiniz bulunmuyor.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($friend_requests as $requester): ?>
                    <li>
                        <span><?php echo htmlspecialchars($requester['username']); ?></span>
                        <form action="<?php echo url('friends.php'); ?>" method="post" class="inline-form">
                            <input type="hidden" name="requester_id" value="<?php echo $requester['id']; ?>">
                            <button type="submit" name="handle_request" value="accept" class="btn btn-success">Kabul Et</button>
                            <button type="submit" name="handle_request" value="decline" class="btn btn-danger">Reddet</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- 3. Arkadaşlarım -->
    <div class="friend-list">
        <h3>Arkadaşlarım</h3>
        <?php if (empty($friends)): ?>
            <p>Henüz hiç arkadaşınız yok.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($friends as $friend): ?>
                    <li><?php echo htmlspecialchars($friend['username']); ?> (<?php echo htmlspecialchars($friend['email']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
/* Sayfaya özel stiller */
.inline-form { display: inline; margin-left: 10px; }
.inline-form button { display: inline-block; width: auto; padding: 5px 10px; }
.btn-success { background-color: var(--success-color); }
.btn-danger { background-color: var(--danger-color); }
.friend-requests ul, .friend-list ul { list-style: none; padding: 0; }
.friend-requests li, .friend-list li { background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>