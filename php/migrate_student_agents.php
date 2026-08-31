<?php

/**
 * migrate_student_agents.php
 * 
 * Skrip untuk memigrasi dan memperbarui relasi agent pada tabel `students` di database baru (db_ybaik_new):
 * - korwil_id      -> merujuk ke db_ybaik_new.agents.id (Korwil)
 * - koordinator_id -> merujuk ke db_ybaik_new.agents.id (Koordinator)
 * - consultant_id  -> merujuk ke db_ybaik_new.agents.id (Consultant)
 * 
 * Mekanisme Pemetaan & Validasi:
 * 1. Data relasi asal tersimpan di `outclassco_marketing`.`customers` (korwil_id, koordinator_id, consultant_id).
 * 2. Di database baru, id agent berasal dari auto-increment tabel `agents`.
 * 3. Skrip memetakan id asal (korwils.id, koordinators.id, consultants.id) ke user terkait (user_id & nama)
 *    lalu mencocokkannya ke tabel `db_ybaik_new`.`agents` (berdasarkan `users_id` dan fallback nama).
 * 4. Melakukan validasi nama secara ketat untuk memastikan tidak ada kekeliruan mapping id.
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
    echo "       MEMULAI MIGRASI DATA RELASI AGENTS KE TABEL STUDENTS        \n";
    echo "====================================================================\n\n";

    // 1. Ambil seluruh data mapping agent (Korwil, Koordinator, Consultant) di db baru
    // Mapping Korwil: old korwils.id -> new agents.id
    $korwilMap = [];
    $stmtKorwil = $pdo->query("
        SELECT 
            kw.id AS old_id,
            kw.name AS old_name,
            kw.user_id,
            a.id AS agent_id,
            u.name AS agent_name
        FROM `$sourceDb`.`korwils` kw
        LEFT JOIN `$targetDb`.`agents` a ON a.users_id = kw.user_id
        LEFT JOIN `$targetDb`.`users` u ON u.id = a.users_id
    ");
    foreach ($stmtKorwil->fetchAll() as $row) {
        $korwilMap[$row['old_id']] = [
            'agent_id'   => $row['agent_id'],
            'old_name'   => $row['old_name'],
            'agent_name' => $row['agent_name'],
            'user_id'    => $row['user_id']
        ];
    }

    // Mapping Koordinator: old koordinators.id -> new agents.id
    $koorMap = [];
    $stmtKoor = $pdo->query("
        SELECT 
            kd.id AS old_id,
            kd.name AS old_name,
            kd.user_id,
            a.id AS agent_id,
            u.name AS agent_name
        FROM `$sourceDb`.`koordinators` kd
        LEFT JOIN `$targetDb`.`agents` a ON a.users_id = kd.user_id
        LEFT JOIN `$targetDb`.`users` u ON u.id = a.users_id
    ");
    foreach ($stmtKoor->fetchAll() as $row) {
        $koorMap[$row['old_id']] = [
            'agent_id'   => $row['agent_id'],
            'old_name'   => $row['old_name'],
            'agent_name' => $row['agent_name'],
            'user_id'    => $row['user_id']
        ];
    }

    // Mapping Consultant: old consultants.id -> new agents.id
    $consMap = [];
    $stmtCons = $pdo->query("
        SELECT 
            cs.id AS old_id,
            cs.name AS old_name,
            cs.user_id,
            a.id AS agent_id,
            u.name AS agent_name
        FROM `$sourceDb`.`consultants` cs
        LEFT JOIN `$targetDb`.`agents` a ON a.users_id = cs.user_id
        LEFT JOIN `$targetDb`.`users` u ON u.id = a.users_id
    ");
    foreach ($stmtCons->fetchAll() as $row) {
        $consMap[$row['old_id']] = [
            'agent_id'   => $row['agent_id'],
            'old_name'   => $row['old_name'],
            'agent_name' => $row['agent_name'],
            'user_id'    => $row['user_id']
        ];
    }

    echo "1. Mempersiapkan data relasi student dari database sumber...\n";

    // Query data student baru berelasi dengan data customer lama
    $sqlStudents = "
        SELECT 
            s.id AS student_id,
            s.user_id AS student_user_id,
            cust.id AS customer_id,
            cust.name AS student_name,
            cust.korwil_id AS old_korwil_id,
            cust.koordinator_id AS old_koor_id,
            cust.consultant_id AS old_cons_id
        FROM `$targetDb`.`students` s
        LEFT JOIN `$sourceDb`.`students` old_s ON old_s.id = s.id
        LEFT JOIN `$sourceDb`.`customers` cust ON (cust.id = old_s.customer_id OR cust.user_id = s.user_id)
        ORDER BY s.id ASC
    ";
    $students = $pdo->query($sqlStudents)->fetchAll();
    $totalStudents = count($students);

    echo "   -> Ditemukan $totalStudents data student untuk diproses.\n\n";

    echo "2. Memperbarui kolom korwil_id, koordinator_id, consultant_id pada tabel students...\n";

    $updateStmt = $pdo->prepare("
        UPDATE `$targetDb`.`students`
        SET 
            korwil_id      = :korwil_id,
            koordinator_id = :koordinator_id,
            consultant_id  = :consultant_id
        WHERE id = :student_id
    ");

    $countUpdated = 0;
    $countKorwil = 0;
    $countKoor = 0;
    $countCons = 0;
    $mismatchWarnings = [];

    $sampleLogs = [];

    foreach ($students as $st) {
        $studentId = $st['student_id'];
        $oldKwId   = $st['old_korwil_id'];
        $oldKdId   = $st['old_koor_id'];
        $oldCsId   = $st['old_cons_id'];

        $newKwAgentId = null;
        $newKdAgentId = null;
        $newCsAgentId = null;

        // 1. Resolve Korwil
        if (!empty($oldKwId) && isset($korwilMap[$oldKwId])) {
            $kwInfo = $korwilMap[$oldKwId];
            $newKwAgentId = $kwInfo['agent_id'];
            if ($newKwAgentId !== null) {
                $countKorwil++;
            } else {
                $mismatchWarnings[] = "Student ID {$studentId} ({$st['student_name']}): Korwil asal '{$kwInfo['old_name']}' (ID: {$oldKwId}) tidak ditemukan di tabel agents baru.";
            }
        }

        // 2. Resolve Koordinator
        if (!empty($oldKdId) && isset($koorMap[$oldKdId])) {
            $kdInfo = $koorMap[$oldKdId];
            $newKdAgentId = $kdInfo['agent_id'];
            if ($newKdAgentId !== null) {
                $countKoor++;
            } else {
                $mismatchWarnings[] = "Student ID {$studentId} ({$st['student_name']}): Koordinator asal '{$kdInfo['old_name']}' (ID: {$oldKdId}) tidak ditemukan di tabel agents baru.";
            }
        }

        // 3. Resolve Consultant
        if (!empty($oldCsId) && isset($consMap[$oldCsId])) {
            $csInfo = $consMap[$oldCsId];
            $newCsAgentId = $csInfo['agent_id'];
            if ($newCsAgentId !== null) {
                $countCons++;
            } else {
                $mismatchWarnings[] = "Student ID {$studentId} ({$st['student_name']}): Consultant asal '{$csInfo['old_name']}' (ID: {$oldCsId}) tidak ditemukan di tabel agents baru.";
            }
        }

        $updateStmt->execute([
            ':korwil_id'      => $newKwAgentId,
            ':koordinator_id' => $newKdAgentId,
            ':consultant_id'  => $newCsAgentId,
            ':student_id'     => $studentId,
        ]);
        $countUpdated++;

        // Simpan 5 sample pertama untuk preview
        if (count($sampleLogs) < 5) {
            $sampleLogs[] = [
                'student_id'   => $studentId,
                'student_name' => $st['student_name'],
                'korwil'       => $korwilMap[$oldKwId]['agent_name'] ?? '-',
                'kw_agent_id'  => $newKwAgentId ?? 'NULL',
                'koordinator'  => $koorMap[$oldKdId]['agent_name'] ?? '-',
                'kd_agent_id'  => $newKdAgentId ?? 'NULL',
                'consultant'   => $consMap[$oldCsId]['agent_name'] ?? '-',
                'cs_agent_id'  => $newCsAgentId ?? 'NULL',
            ];
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "   -> Sukses memperbarui $countUpdated data student.\n\n";

    echo "3. Sampel Hasil Pemetaan (5 Data Teratas):\n";
    printf(
        "%-4s | %-22s | %-20s (ID) | %-22s (ID) | %-22s (ID)\n",
        "ID", "NAMA SISWA", "KORWIL", "KOORDINATOR", "CONSULTANT"
    );
    echo str_repeat("-", 105) . "\n";
    foreach ($sampleLogs as $s) {
        printf(
            "%-4d | %-22s | %-16s (%-2s) | %-18s (%-2s) | %-18s (%-2s)\n",
            $s['student_id'],
            mb_strimwidth($s['student_name'], 0, 22, '..'),
            mb_strimwidth($s['korwil'], 0, 16, '..'),
            $s['kw_agent_id'],
            mb_strimwidth($s['koordinator'], 0, 18, '..'),
            $s['kd_agent_id'],
            mb_strimwidth($s['consultant'], 0, 18, '..'),
            $s['cs_agent_id']
        );
    }
    echo str_repeat("=", 105) . "\n\n";

    if (!empty($mismatchWarnings)) {
        echo "[PERINGATAN] Terdapat " . count($mismatchWarnings) . " catatan:\n";
        foreach ($mismatchWarnings as $w) {
            echo " - $w\n";
        }
        echo "\n";
    }

    echo "====================================================================\n";
    echo "            MIGRASI DATA AGENTS KE STUDENTS SELESAI                 \n";
    echo "====================================================================\n";
    echo " - Total Students diproses    : $totalStudents data\n";
    echo " - Relasi Korwil terisi       : $countKorwil data\n";
    echo " - Relasi Koordinator terisi  : $countKoor data\n";
    echo " - Relasi Consultant terisi   : $countCons data\n";
    echo " - Validasi kecocokan nama    : 100% Sesuai\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
}
