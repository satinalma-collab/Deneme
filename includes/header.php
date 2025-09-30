<?php
// --- Dinamik URL Yapılandırması ---
// Uygulamanın kök dizinini otomatik olarak algıla
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Eğer ana dizindeysek, script_name '/' olabilir, bunu temizle
$base_path = ($script_name == '/') ? '' : $script_name;
define('BASE_URL', $protocol . $host . $base_path . '/');

/**
 * Verilen bir yol için tam URL oluşturur.
 * @param string $path Uygulama içindeki göreceli yol (örn: 'css/style.css').
 * @return string Oluşturulan tam URL.
 */
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

// --- Gerekli Dosyaları Dahil Etme ---
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/notifications.php';

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
    <link rel="stylesheet" href="<?php echo url('css/style.css'); ?>">
    <script>
        const BASE_URL = '<?php echo url(); ?>';
    </script>
</head>
<body <?php if (isset($body_class)) { echo 'class="' . htmlspecialchars($body_class) . '"'; } ?>>
    <header class="main-header">
        <div class="container">
            <a href="<?php echo url(); ?>" class="logo">Hasello</a>
            <nav class="main-nav">
                <ul>
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?php echo url('dashboard.php'); ?>">Kontrol Paneli</a></li>
                        <li><a href="<?php echo url('friends.php'); ?>">Arkadaşlar</a></li>
                        <li>
                            <a href="<?php echo url('notifications.php'); ?>">
                                Bildirimler
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><span class="user-greeting">Merhaba, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span></li>
                        <li><a href="<?php echo url('logout.php'); ?>" class="btn-logout">Çıkış Yap</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo url('login.php'); ?>">Giriş Yap</a></li>
                        <li><a href="<?php echo url('register.php'); ?>">Kayıt Ol</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">