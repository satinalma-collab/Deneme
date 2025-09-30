<?php
$page_title = 'Kayıt Ol';
require_once __DIR__ . '/php/auth.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basit doğrulama
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = "Lütfen tüm alanları doldurun.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Lütfen geçerli bir e-posta adresi girin.";
    } else {
        $result = register_user($username, $email, $password);
        if ($result === true) {
            // Kayıt başarılı, giriş sayfasına yönlendir
            header('Location: login.php?registered=true');
            exit;
        } else {
            // Hata mesajını göster
            $error_message = $result;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <h2>Kayıt Ol</h2>
    <p>Hasello'ya katılın ve projelerinizi yönetmeye başlayın.</p>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo url('register.php'); ?>" method="post">
        <div class="form-group">
            <label for="username">Kullanıcı Adı</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="email">E-posta</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Parola</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Kayıt Ol</button>
        </div>
    </form>
    <p class="form-footer">Zaten bir hesabınız var mı? <a href="<?php echo url('login.php'); ?>">Giriş Yapın</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>