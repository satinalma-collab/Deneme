<?php
$page_title = 'Şifre Sıfırla';
require_once __DIR__ . '/includes/header.php';

$token = trim($_GET['token'] ?? '');
$error_message = '';
$success_message = '';
$token_is_valid = false;
$reset_request = null;

if (empty($token)) {
    $error_message = "Geçersiz veya eksik sıfırlama anahtarı.";
} else {
    $resets = read_db(DATA_PATH . 'password_resets.json');
    foreach ($resets as $req) {
        if ($req['token'] === $token) {
            $reset_request = $req;
            break;
        }
    }

    if ($reset_request === null || time() > $reset_request['expires']) {
        $error_message = "Bu şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.";
    } else {
        $token_is_valid = true;
    }
}

// Yeni şifreyi ayarlama işlemi
if ($token_is_valid && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || $password !== $password_confirm) {
        $error_message = "Parolalar eşleşmiyor veya boş bırakılamaz.";
    } elseif (strlen($password) < 6) {
        $error_message = "Parola en az 6 karakter olmalıdır.";
    } else {
        $users = read_db(USERS_FILE);
        $user_found_and_updated = false;

        foreach ($users as &$user) {
            if ($user['email'] === $reset_request['email']) {
                $user['password'] = password_hash($password, PASSWORD_DEFAULT);
                $user_found_and_updated = true;
                break;
            }
        }

        if ($user_found_and_updated) {
            write_db(USERS_FILE, $users);

            // Kullanılmış token'ı sil
            $new_resets = array_filter(read_db(DATA_PATH . 'password_resets.json'), function($r) use ($token) {
                return $r['token'] !== $token;
            });
            write_db(DATA_PATH . 'password_resets.json', array_values($new_resets));

            // Başarı mesajı ile giriş sayfasına yönlendir
            header('Location: login.php?password_reset_success=true');
            exit;
        } else {
            $error_message = "Kullanıcı bulunamadığı için şifre güncellenemedi.";
        }
    }
}
?>

<div class="form-container">
    <h2>Yeni Şifre Belirle</h2>

    <?php if (!$token_is_valid): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <p class="form-footer"><a href="forgot-password.php">Yeni bir sıfırlama bağlantısı talep et</a></p>
    <?php else: ?>
        <p>Lütfen yeni parolanızı girin.</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
            <div class="form-group">
                <label for="password">Yeni Parola</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Yeni Parola (Tekrar)</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <div class="form-group">
                <button type="submit" name="reset_password" class="btn">Şifreyi Güncelle</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>