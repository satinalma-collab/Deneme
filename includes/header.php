<?php
// Gerekli ana dosyaları dahil et
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/notifications.php'; // Bildirim fonksiyonlarını dahil et

// Oturum başlatma auth.php içinde zaten yapılıyor.
$unread_count = 0;
if(is_logged_in()) {
    $unread_count = get_unread_notification_count(get_current_user_id());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasello - <?php echo $page_title ?? 'Proje Yönetim Aracı'; ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body <?php if (isset($body_class)) { echo 'class="' . htmlspecialchars($body_class) . '"'; } ?>>
    <header class="main-header">
        <div class="container">
            <a href="/" class="logo">Hasello</a>
            <nav class="main-nav">
                <ul>
                    <?php if (is_logged_in()): ?>
                        <li><a href="/dashboard.php">Kontrol Paneli</a></li>
                        <li>
                            <a href="/notifications.php">
                                Bildirimler
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><span class="user-greeting">Merhaba, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span></li>
                        <li><a href="/logout.php" class="btn-logout">Çıkış Yap</a></li>
                    <?php else: ?>
                        <li><a href="/login.php">Giriş Yap</a></li>
                        <li><a href="/register.php">Kayıt Ol</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">