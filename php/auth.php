<?php
// Hasello - Kimlik Doğrulama Fonksiyonları

require_once 'database.php';

// Oturumu her zaman başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Yeni bir kullanıcı kaydeder.
 * @param string $username Kullanıcı adı.
 * @param string $email E-posta adresi.
 * @param string $password Parola.
 * @return bool|string Başarılı ise true, hata varsa hata mesajı döner.
 */
function register_user($username, $email, $password) {
    $users = read_db(USERS_FILE);

    // E-postanın zaten kayıtlı olup olmadığını kontrol et
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return "Bu e-posta adresi zaten kullanılıyor.";
        }
    }

    // Parolayı güvenli bir şekilde hash'le
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Yeni kullanıcı oluştur
    $new_user = [
        'id' => empty($users) ? 1 : max(array_column($users, 'id')) + 1,
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'friends' => [],
        'friend_requests_sent' => [],
        'friend_requests_received' => []
    ];

    $users[] = $new_user;

    if (write_db(USERS_FILE, $users)) {
        return true;
    }

    return "Kayıt sırasında bir hata oluştu.";
}

/**
 * Kullanıcı girişi yapar ve oturum başlatır.
 * @param string $email E-posta adresi.
 * @param string $password Parola.
 * @return bool Giriş başarılı ise true, değilse false.
 */
function login_user($email, $password) {
    $users = read_db(USERS_FILE);

    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password'])) {
            // Giriş başarılı, oturum bilgilerini ayarla
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
    }

    return false;
}

/**
 * Kullanıcı çıkışı yapar ve oturumu sonlandırır.
 */
function logout_user() {
    session_unset();
    session_destroy();
}

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol eder.
 * @return bool Giriş yapmışsa true, yapmamışsa false.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Oturumu açık olan kullanıcının ID'sini döndürür.
 * @return int|null Kullanıcı ID'si veya giriş yapılmamışsa null.
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Belirtilen ID'ye sahip kullanıcıyı bulur.
 * @param int $user_id Aranacak kullanıcı ID'si.
 * @return array|null Kullanıcı dizisi veya bulunamazsa null.
 */
function get_user_by_id($user_id) {
    $users = read_db(USERS_FILE);
    foreach ($users as $user) {
        if ($user['id'] == $user_id) {
            // Güvenlik için parolayı döndürme
            unset($user['password']);
            return $user;
        }
    }
    return null;
}

/**
 * Belirtilen e-postaya sahip kullanıcıyı bulur.
 * @param string $email Aranacak e-posta.
 * @return array|null Kullanıcı dizisi veya bulunamazsa null.
 */
function get_user_by_email($email) {
    $users = read_db(USERS_FILE);
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            unset($user['password']);
            return $user;
        }
    }
    return null;
}