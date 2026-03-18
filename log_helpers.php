<?php

function ensure_islem_loglari_table(PDO $db): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $db->exec(
        "CREATE TABLE IF NOT EXISTS islem_loglari (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_name VARCHAR(255) NOT NULL DEFAULT '',
            modul VARCHAR(100) NOT NULL,
            islem VARCHAR(50) NOT NULL,
            kayit_id INT NULL,
            aciklama TEXT NULL,
            veri_json LONGTEXT NULL,
            ip_adresi VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_modul (modul),
            INDEX idx_user_id (user_id),
            INDEX idx_kayit_id (kayit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $initialized = true;
}

function get_log_user_info(PDO $db): array
{
    static $userInfo = null;

    if ($userInfo !== null) {
        return $userInfo;
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userInfo = [
        'id' => $userId ?: null,
        'name' => 'Bilinmeyen Kullanici'
    ];

    if ($userId > 0) {
        $stmt = $db->prepare("SELECT namesurname, username FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $userInfo['name'] = trim((string)($row['namesurname'] ?? '')) ?: (string)($row['username'] ?? 'Bilinmeyen Kullanici');
        }
    }

    return $userInfo;
}

function log_format_pairs(array $pairs): string
{
    $out = [];
    foreach ($pairs as $label => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        if (is_bool($value)) {
            $value = $value ? 'Evet' : 'Hayir';
        }
        $out[] = $label . ': ' . $value;
    }

    return implode(' | ', $out);
}

function log_islem(PDO $db, string $modul, string $islem, ?int $kayitId, string $aciklama, array $veri = []): void
{
    try {
        ensure_islem_loglari_table($db);

        $userInfo = get_log_user_info($db);
        $stmt = $db->prepare(
            "INSERT INTO islem_loglari
            (user_id, user_name, modul, islem, kayit_id, aciklama, veri_json, ip_adresi, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        $stmt->execute([
            $userInfo['id'],
            $userInfo['name'],
            $modul,
            $islem,
            $kayitId,
            $aciklama,
            !empty($veri) ? json_encode($veri, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('islem log yazilamadi: ' . $e->getMessage());
    }
}