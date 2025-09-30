<?php
// Hasello - Arkadaşlık İşlemleri Mantığı

// auth.php ve database.php bu dosyayı çağıran script tarafından zaten dahil ediliyor.

/**
 * Bir kullanıcıya arkadaşlık isteği gönderir.
 * @param int $sender_id İstek gönderen kullanıcının ID'si.
 * @param string $receiver_email İstek alacak kullanıcının e-postası.
 * @return bool|string Başarılı ise true, hata varsa hata mesajı döner.
 */
function send_friend_request($sender_id, $receiver_email) {
    $users = read_db(USERS_FILE);

    $receiver = null;
    $sender_key = null;
    $receiver_key = null;

    foreach ($users as $key => $user) {
        if ($user['email'] === $receiver_email) {
            $receiver = $user;
            $receiver_key = $key;
        }
        if ($user['id'] === $sender_id) {
            $sender_key = $key;
        }
    }

    if ($receiver === null) {
        return "Bu e-posta adresine sahip bir kullanıcı bulunamadı.";
    }

    if ($sender_id === $receiver['id']) {
        return "Kendinize arkadaşlık isteği gönderemezsiniz.";
    }

    if (in_array($receiver['id'], $users[$sender_key]['friends'])) {
        return "Bu kullanıcı zaten arkadaşınız.";
    }

    if (in_array($receiver['id'], $users[$sender_key]['friend_requests_sent'])) {
        return "Bu kullanıcıya zaten bir istek göndermişsiniz.";
    }

    // İsteği gönderen ve alan taraflara kaydet
    $users[$sender_key]['friend_requests_sent'][] = $receiver['id'];
    $users[$receiver_key]['friend_requests_received'][] = $sender_id;

    return write_db(USERS_FILE, $users);
}

/**
 * Bir arkadaşlık isteğini kabul eder.
 * @param int $user_id İsteği kabul eden kullanıcı.
 * @param int $requester_id İsteği gönderen kullanıcı.
 */
function accept_friend_request($user_id, $requester_id) {
    $users = read_db(USERS_FILE);

    $user_key = null;
    $requester_key = null;

    foreach ($users as $key => $user) {
        if ($user['id'] === $user_id) $user_key = $key;
        if ($user['id'] === $requester_id) $requester_key = $key;
    }

    if ($user_key === null || $requester_key === null) return;

    // Her iki kullanıcıyı da birbirinin arkadaş listesine ekle
    $users[$user_key]['friends'][] = $requester_id;
    $users[$requester_key]['friends'][] = $user_id;

    // İstekleri temizle ve diziyi yeniden indeksle
    $users[$user_key]['friend_requests_received'] = array_values(array_diff($users[$user_key]['friend_requests_received'], [$requester_id]));
    $users[$requester_key]['friend_requests_sent'] = array_values(array_diff($users[$requester_key]['friend_requests_sent'], [$user_id]));

    write_db(USERS_FILE, $users);
}

/**
 * Bir arkadaşlık isteğini reddeder.
 * @param int $user_id İsteği reddeden kullanıcı.
 * @param int $requester_id İsteği gönderen kullanıcı.
 */
function decline_friend_request($user_id, $requester_id) {
    $users = read_db(USERS_FILE);

    $user_key = null;
    $requester_key = null;

    foreach ($users as $key => $user) {
        if ($user['id'] === $user_id) $user_key = $key;
        if ($user['id'] === $requester_id) $requester_key = $key;
    }

    if ($user_key === null || $requester_key === null) return;

    // İstekleri temizle ve diziyi yeniden indeksle
    $users[$user_key]['friend_requests_received'] = array_values(array_diff($users[$user_key]['friend_requests_received'], [$requester_id]));
    $users[$requester_key]['friend_requests_sent'] = array_values(array_diff($users[$requester_key]['friend_requests_sent'], [$user_id]));

    write_db(USERS_FILE, $users);
}
?>