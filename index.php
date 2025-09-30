<?php
// Hasello - Ana Yönlendirici

// Kimlik doğrulama fonksiyonlarını dahil et
require_once __DIR__ . '/php/auth.php';

// Oturum auth.php içinde zaten başlatılıyor.

// Kullanıcının giriş yapıp yapmadığını kontrol et
if (is_logged_in()) {
    // Giriş yapmışsa, kontrol paneline yönlendir
    header('Location: dashboard.php');
} else {
    // Giriş yapmamışsa, giriş sayfasına yönlendir
    header('Location: login.php');
}

// Yönlendirmeden sonra betiğin çalışmasını durdur
exit;
?>