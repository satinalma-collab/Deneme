<?php
// Hasello - Çıkış İşlemi

require_once __DIR__ . '/php/auth.php';

// Kullanıcı oturumunu sonlandır
logout_user();

// Kullanıcıyı giriş sayfasına yönlendir
header('Location: login.php?logged_out=true');
exit;
?>