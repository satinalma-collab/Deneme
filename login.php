<?php
$page_title = 'Giriş Yap';
require_once __DIR__ . '/php/auth.php';

// Eğer kullanıcı zaten giriş yapmışsa, kontrol paneline yönlendir
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// Kayıt sayfasından yönlendirme varsa başarı mesajı göster
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success_message = "Kaydınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = "Lütfen e-posta ve parolanızı girin.";
    } else {
        if (login_user($email, $password)) {
            // Giriş başarılı, kontrol paneline yönlendir
            header('Location: dashboard.php');
            exit;
        } else {
            $error_message = "E-posta veya parola hatalı.";
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <h2>Giriş Yap</h2>
    <p>Hesabınıza erişim sağlayın.</p>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="post">
        <div class="form-group">
            <label for="email">E-posta</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Parola</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Giriş Yap</button>
        </div>
    </form>
    <p class="form-footer">Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>