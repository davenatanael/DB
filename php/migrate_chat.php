<?php

/**
 * migrate_chat.php
 *
 * Migrasi data chat (chats, chat_users, chat_messages)
 * dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * TAHAP 1: chats (Master Room Chat)
 *   Sumber : outclassco_marketing.chats (6 baris)
 *   Target : db_ybaik_new.chats
 *   Perbandingan Schema:
 *     - Atribut Sama : id, title, created_at, updated_at
 *     - Atribut Baru : deleted_at (diisi NULL)
 *
 * TAHAP 2: chat_users (Peserta Chat)
 *   Sumber : outclassco_marketing.chat_users (11 baris)
 *   Target : db_ybaik_new.chat_users
 *   Perbandingan Schema:
 *     - Atribut Sama : user_id, chat_id, created_at, updated_at
 *     - Atribut Baru : deleted_at (diisi NULL)
 *   Integritas FK: user_id -> users.id, chat_id -> chats.id
 *
 * TAHAP 3: chat_messages (Pesan Chat)
 *   Sumber : outclassco_marketing.chat_messages (11 baris)
 *   Target : db_ybaik_new.chat_messages
 *   Perbandingan Schema:
 *     - Atribut Sama : id, chat_id, user_id, content, attachment_path,
 *                      is_read, read_at, created_at, updated_at, deleted_at
 *   Integritas FK: user_id -> users.id, chat_id -> chats.id
 *
 * PRASYARAT: users HARUS sudah dimigrasikan duluan.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "====================================================================\n";
    echo "    MEMULAI MIGRASI DATA CHAT (CHATS, CHAT_USERS, CHAT_MESSAGES)    \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target sesuai urutan relasi
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`chat_messages`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`chat_users`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`chats`");
    echo "-> Tabel `chat_messages`, `chat_users`, dan `chats` berhasil dikosongkan.\n\n";

    // =========================================================================
    // TAHAP 1: MIGRASI CHATS (Master Room Chat)
    // =========================================================================
    echo "1. Memigrasi data chats (Master Chat)...\n";
    $chatsStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`chats` (
            `id`,
            `title`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        ) VALUES (
            :id,
            :title,
            :created_at,
            :updated_at,
            :deleted_at
        )
    ");

    $sourceChats = $pdo->query("SELECT * FROM `$sourceDb`.`chats` ORDER BY `id` ASC")->fetchAll();
    $insertedChats = 0;

    foreach ($sourceChats as $row) {
        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
        $updatedAt = !empty($row['updated_at']) ? $row['updated_at'] : date('Y-m-d H:i:s');
        $deletedAt = !empty($row['deleted_at']) ? $row['deleted_at'] : null;

        $chatsStmt->execute([
            ':id'         => $row['id'],
            ':title'      => $row['title'],
            ':created_at' => $createdAt,
            ':updated_at' => $updatedAt,
            ':deleted_at' => $deletedAt,
        ]);
        $insertedChats++;
    }

    $totalChats = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`chats`")->fetch()['c'];
    echo "   -> Sumber: " . count($sourceChats) . " baris | Berhasil dimasukkan: $insertedChats | Total target: $totalChats\n\n";

    // =========================================================================
    // TAHAP 2: MIGRASI CHAT_USERS (Peserta Chat)
    // =========================================================================
    echo "2. Memigrasi data chat_users (Peserta Chat)...\n";
    $chatUsersStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`chat_users` (
            `user_id`,
            `chat_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        ) VALUES (
            :user_id,
            :chat_id,
            :created_at,
            :updated_at,
            :deleted_at
        )
    ");

    $sourceChatUsers = $pdo->query("SELECT * FROM `$sourceDb`.`chat_users` ORDER BY `chat_id` ASC, `user_id` ASC")->fetchAll();
    $insertedChatUsers = 0;

    foreach ($sourceChatUsers as $row) {
        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
        $updatedAt = !empty($row['updated_at']) ? $row['updated_at'] : date('Y-m-d H:i:s');
        $deletedAt = !empty($row['deleted_at']) ? $row['deleted_at'] : null;

        $chatUsersStmt->execute([
            ':user_id'    => $row['user_id'],
            ':chat_id'    => $row['chat_id'],
            ':created_at' => $createdAt,
            ':updated_at' => $updatedAt,
            ':deleted_at' => $deletedAt,
        ]);
        $insertedChatUsers++;
    }

    $totalChatUsers = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`chat_users`")->fetch()['c'];
    echo "   -> Sumber: " . count($sourceChatUsers) . " baris | Berhasil dimasukkan: $insertedChatUsers | Total target: $totalChatUsers\n\n";

    // =========================================================================
    // TAHAP 3: MIGRASI CHAT_MESSAGES (Pesan Percakapan)
    // =========================================================================
    echo "3. Memigrasi data chat_messages (Pesan Percakapan)...\n";
    $chatMessagesStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`chat_messages` (
            `id`,
            `chat_id`,
            `user_id`,
            `content`,
            `attachment_path`,
            `is_read`,
            `read_at`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        ) VALUES (
            :id,
            :chat_id,
            :user_id,
            :content,
            :attachment_path,
            :is_read,
            :read_at,
            :created_at,
            :updated_at,
            :deleted_at
        )
    ");

    $sourceMessages = $pdo->query("SELECT * FROM `$sourceDb`.`chat_messages` ORDER BY `id` ASC")->fetchAll();
    $insertedMessages = 0;

    foreach ($sourceMessages as $row) {
        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
        $updatedAt = !empty($row['updated_at']) ? $row['updated_at'] : date('Y-m-d H:i:s');
        $deletedAt = !empty($row['deleted_at']) ? $row['deleted_at'] : null;
        $attachmentPath = !empty($row['attachment_path']) ? $row['attachment_path'] : null;
        $readAt = !empty($row['read_at']) ? $row['read_at'] : null;
        $isRead = isset($row['is_read']) ? (int)$row['is_read'] : 0;

        $chatMessagesStmt->execute([
            ':id'              => $row['id'],
            ':chat_id'         => $row['chat_id'],
            ':user_id'         => $row['user_id'],
            ':content'         => $row['content'],
            ':attachment_path' => $attachmentPath,
            ':is_read'         => $isRead,
            ':read_at'         => $readAt,
            ':created_at'      => $createdAt,
            ':updated_at'      => $updatedAt,
            ':deleted_at'      => $deletedAt,
        ]);
        $insertedMessages++;
    }

    $totalMessages = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`chat_messages`")->fetch()['c'];
    echo "   -> Sumber: " . count($sourceMessages) . " baris | Berhasil dimasukkan: $insertedMessages | Total target: $totalMessages\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI SELURUH DATA MODUL CHAT SELESAI DENGAN SUKSES!          \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
