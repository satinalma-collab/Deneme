<?php
$page_title = 'Şifremi Unuttum';
require_once __DIR__ . '/includes/header.php';

$message = '';
$message_type = 'info'; // 'info', 'success', 'danger'

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reset_link'])) {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Lütfen geçerli bir e-posta adresi girin.';
        $message_type = 'danger';
    } else {
        $user = get_user_by_email($email);

        // E-posta bulunsa da bulunmasa da aynı mesajı göstererek
        // kullanıcı bilgilerinin sızdırılmasını (user enumeration) engelliyoruz.
        $message = 'Eğer bu e-posta adresi sistemimizde kayıtlıysa, şifre sıfırlama bağlantısı gönderilmiştir.';
        $message_type = 'success';

        if ($user) {
            $resets = read_db(DATA_PATH . 'password_resets.json');

            // Yeni token oluştur
            $token = bin2hex(random_bytes(32));
            $expires = time() + 3600; // 1 saat geçerli

            $new_reset_request = [
                'email' => $email,
                'token' => $token,
                'expires' => $expires
            ];

            // Eski talepleri temizle ve yenisini ekle
            $filtered_resets = array_filter($resets, function($r) use ($email) {
                return $r['email'] !== $email;
            });
            $filtered_resets[] = $new_reset_request;

            write_db(DATA_PATH . 'password_resets.json', $filtered_resets);

            // E-POSTA GÖNDERME SİMÜLASYONU
            // Gerçek bir uygulamada bu link e-posta ile gönderilir.
            // Burada test kolaylığı için ekranda gösteriyoruz.
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
            $message .= "<br><br><strong>Simülasyon:</strong> Şifrenizi sıfırlamak için aşağıdaki bağlantıyı kullanın:<br><a href='{$reset_link}'>{$reset_link}</a>";
        }
    }
}
?>

<div class="form-container">
    <h2>Şifremi Unuttum</h2>
    <p>Hesabınıza ait e-posta adresini girerek şifre sıfırlama bağlantısı talep edebilirsiniz.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; // HTML içerdiği için htmlspecialchars kullanılmadı ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo url('forgot-password.php'); ?>" method="post">
        <div class="form-group">
            <label for="email">E-posta Adresiniz</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <button type="submit" name="send_reset_link" class="btn">Sıfırlama Bağlantısı Gönder</button>
        </div>
    </form>
    <p class="form-footer"><a href="<?php echo url('login.php'); ?>">Giriş ekranına dön</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>