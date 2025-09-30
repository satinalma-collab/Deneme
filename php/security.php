<?php
// Hasello - Güvenlik Fonksiyonları

// UYARI: Gerçek bir üretim ortamında bu anahtarı doğrudan koda yazmak yerine,
// sunucu ortam değişkenleri gibi daha güvenli bir yerde saklayın.
define('ENCRYPTION_KEY', 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6');
define('ENCRYPTION_CIPHER', 'AES-256-CBC');

/**
 * Verilen bir metni şifreler.
 * @param string $plaintext Şifrelenecek metin.
 * @return string Şifrelenmiş metin (base64 formatında).
 */
function encrypt_data($plaintext) {
    $ivlen = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($plaintext, ENCRYPTION_CIPHER, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, true);
    return base64_encode($iv . $hmac . $ciphertext_raw);
}

/**
 * Şifrelenmiş bir metnin şifresini çözer.
 * @param string $ciphertext Şifresi çözülecek, base64 formatındaki metin.
 * @return string|false Orijinal metin veya hata durumunda false.
 */
function decrypt_data($ciphertext) {
    $c = base64_decode($ciphertext);
    $ivlen = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    $original_plaintext = openssl_decrypt($ciphertext_raw, ENCRYPTION_CIPHER, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, true);

    if (hash_equals($hmac, $calcmac)) {
        return $original_plaintext;
    }

    return false;
}