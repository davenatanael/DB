<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE `roles`;");

    $sql = "
        INSERT INTO `roles` (`id`, `name`) VALUES
        (1, 'Superadmin'),
        (2, 'Admin'),
        (3, 'Korwil'),
        (4, 'Koordinator'),
        (5, 'Consultant'),
        (6, 'Finance'),
        (7, 'School'),
        (8, 'Parent'),
        (9, 'Student');
    ";
    $pdo->exec($sql);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Tabel roles berhasil direset dan diisi data baru.";
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
    echo "Error: " . $e->getMessage();
}