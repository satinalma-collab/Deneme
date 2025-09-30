<?php
$page_title = 'Bildirimler';
require_once __DIR__ . '/includes/header.php';

// Kullanıcı giriş yapmamışsa, giriş sayfasına yönlendir
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$current_user_id = get_current_user_id();

// Kullanıcının bildirimlerini getir
$notifications = get_notifications_for_user($current_user_id);

// Sayfa görüntülendiğinde okunmamış bildirimleri okundu olarak işaretle
// Bu işlem, header'daki sayacın bir sonraki sayfa yüklemesinde güncellenmesini sağlar.
mark_notifications_as_read($current_user_id);
?>

<div class="notifications-page">
    <h2>Bildirimleriniz</h2>

    <?php if (empty($notifications)): ?>
        <div class="card no-notifications">
            <p>Henüz bir bildiriminiz yok.</p>
        </div>
    <?php else: ?>
        <ul class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <li class="notification-item <?php echo !$notification['is_read'] ? 'unread' : 'read'; ?>">
                    <a href="<?php echo htmlspecialchars($notification['link']); ?>">
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        <div class="notification-timestamp">
                            <?php echo htmlspecialchars($notification['timestamp']); ?>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>