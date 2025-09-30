<?php
// Hasello - Bildirim Fonksiyonları

// database.php'yi dahil etmeye gerek yok, çünkü bu dosyayı çağıran scriptler
// (project.php, header.php) zaten onu dahil etmiş olacak.

/**
 * Belirli bir kullanıcı için yeni bir bildirim oluşturur.
 *
 * @param int $user_id Bildirimi alacak kullanıcının ID'si.
 * @param string $message Bildirim mesajı.
 * @param string $link Tıklandığında yönlendirilecek URL.
 * @return bool Başarılı olursa true, olmazsa false.
 */
function create_notification($user_id, $message, $link) {
    $notifications = read_db(NOTIFICATIONS_FILE);

    $new_notification = [
        'id' => count($notifications) + 1,
        'user_id' => $user_id,
        'message' => $message,
        'link' => $link,
        'is_read' => false,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $notifications[] = $new_notification;
    return write_db(NOTIFICATIONS_FILE, $notifications);
}

/**
 * Belirli bir kullanıcının tüm bildirimlerini getirir (en yeniden en eskiye).
 *
 * @param int $user_id Kullanıcı ID'si.
 * @return array Kullanıcının bildirimleri.
 */
function get_notifications_for_user($user_id) {
    $all_notifications = read_db(NOTIFICATIONS_FILE);
    $user_notifications = [];

    foreach ($all_notifications as $notification) {
        if ($notification['user_id'] == $user_id) {
            $user_notifications[] = $notification;
        }
    }

    // Bildirimleri en yeniden en eskiye doğru sırala
    usort($user_notifications, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    return $user_notifications;
}

/**
 * Bir kullanıcının okunmamış bildirimlerinin sayısını döndürür.
 *
 * @param int $user_id Kullanıcı ID'si.
 * @return int Okunmamış bildirim sayısı.
 */
function get_unread_notification_count($user_id) {
    $all_notifications = read_db(NOTIFICATIONS_FILE);
    $count = 0;

    foreach ($all_notifications as $notification) {
        if ($notification['user_id'] == $user_id && !$notification['is_read']) {
            $count++;
        }
    }
    return $count;
}

/**
 * Bir kullanıcının tüm okunmamış bildirimlerini okundu olarak işaretler.
 *
 * @param int $user_id Kullanıcı ID'si.
 * @return bool Değişiklik yapıldıysa true.
 */
function mark_notifications_as_read($user_id) {
    $all_notifications = read_db(NOTIFICATIONS_FILE);
    $updated = false;

    foreach ($all_notifications as &$notification) {
        if ($notification['user_id'] == $user_id && !$notification['is_read']) {
            $notification['is_read'] = true;
            $updated = true;
        }
    }

    if ($updated) {
        return write_db(NOTIFICATIONS_FILE, $all_notifications);
    }
    return false;
}