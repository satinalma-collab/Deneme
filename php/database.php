<?php
// Hasello - Veritabanı (JSON) İşlemleri

require_once 'security.php';

// Veri dosyalarının yolları
define('DATA_PATH', __DIR__ . '/../data/');
define('USERS_FILE', DATA_PATH . 'users.json');
define('PROJECTS_FILE', DATA_PATH . 'projects.json');
define('NOTIFICATIONS_FILE', DATA_PATH . 'notifications.json');

/**
 * Belirtilen JSON dosyasını okur, şifresini çözer ve PHP dizisi olarak döndürür.
 * @param string $filepath Dosya yolu.
 * @return array Veri dizisi. Dosya boş veya yoksa boş dizi döner.
 */
function read_db($filepath) {
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        return [];
    }

    $encrypted_data = file_get_contents($filepath);
    if (empty(trim($encrypted_data))) {
        return [];
    }

    $decrypted_data = decrypt_data($encrypted_data);

    if ($decrypted_data === false) {
        // Hata yönetimi eklenebilir, şimdilik boş dizi dönüyoruz.
        // Bu durum, anahtar değiştiğinde veya dosya bozulduğunda olabilir.
        error_log("Veri okunamadı veya şifre çözülemedi: " . $filepath);
        return [];
    }

    return json_decode($decrypted_data, true);
}

/**
 * Verilen bir PHP dizisini JSON'a çevirir, şifreler ve dosyaya yazar.
 * @param string $filepath Dosya yolu.
 * @param array $data Kaydedilecek veri dizisi.
 * @return bool Başarılı ise true, değilse false.
 */
function write_db($filepath, $data) {
    $json_data = json_encode($data, JSON_PRETTY_PRINT);
    $encrypted_data = encrypt_data($json_data);

    if (file_put_contents($filepath, $encrypted_data) === false) {
        error_log("Veri yazılamadı: " . $filepath);
        return false;
    }

    return true;
}