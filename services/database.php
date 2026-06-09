<?php

require_once __DIR__ . '/utils.php';

function leadDbConfig(): array
{
    $path = __DIR__ . '/../config/database.php';

    if (!file_exists($path)) {
        return ['enabled' => false];
    }

    $config = require $path;

    return is_array($config) ? $config : ['enabled' => false];
}

function leadDbEnabled(): bool
{
    $config = leadDbConfig();

    return !empty($config['enabled']);
}

function leadDbConnection(): ?PDO
{
    static $pdo = null;
    static $failed = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($failed || !leadDbEnabled()) {
        return null;
    }

    $config = leadDbConfig();
    $charset = $config['charset'] ?? 'utf8mb4';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'] ?? '127.0.0.1',
        (int) ($config['port'] ?? 3306),
        $config['database'] ?? 'lead_gen_tool',
        $charset
    );

    try {
        $pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        ensureLeadDbSchema($pdo);

        return $pdo;
    } catch (Throwable $exception) {
        $failed = true;
        writeDatabaseDebug('connection_error.txt', sprintf(
            "host=%s\nport=%s\ndatabase=%s\nusername=%s\nerror=%s",
            (string) ($config['host'] ?? ''),
            (string) ($config['port'] ?? ''),
            (string) ($config['database'] ?? ''),
            (string) ($config['username'] ?? ''),
            $exception->getMessage()
        ));

        return null;
    }
}

function saveSearchResultsToDatabase(
    string $postalCode,
    string $keyword,
    string $filter,
    array $businesses,
    array $stats,
    bool $cached
): array {
    $pdo = leadDbConnection();

    if (!$pdo) {
        return [
            'enabled' => leadDbEnabled(),
            'saved' => false,
            'message' => leadDbEnabled() ? 'MySQL no disponible' : 'MySQL desactivado',
        ];
    }

    try {
        $pdo->beginTransaction();

        $insertRun = $pdo->prepare(
            'INSERT INTO search_runs (postal_code, keyword, filter_name, total_results, cached, stats_json)
             VALUES (:postal_code, :keyword, :filter_name, :total_results, :cached, :stats_json)'
        );

        $insertRun->execute([
            ':postal_code' => $postalCode,
            ':keyword' => $keyword,
            ':filter_name' => $filter,
            ':total_results' => count($businesses),
            ':cached' => $cached ? 1 : 0,
            ':stats_json' => json_encode($stats, JSON_UNESCAPED_UNICODE),
        ]);

        $runId = (int) $pdo->lastInsertId();
        $savedBusinesses = 0;

        foreach ($businesses as $position => $business) {
            $businessId = upsertBusiness($pdo, $business);

            if ($businessId <= 0) {
                continue;
            }

            $link = $pdo->prepare(
                'INSERT IGNORE INTO search_run_businesses (search_run_id, business_id, position_number)
                 VALUES (:search_run_id, :business_id, :position_number)'
            );
            $link->execute([
                ':search_run_id' => $runId,
                ':business_id' => $businessId,
                ':position_number' => $position + 1,
            ]);

            $savedBusinesses++;
        }

        $pdo->commit();

        return [
            'enabled' => true,
            'saved' => true,
            'message' => 'Guardado en MySQL',
            'run_id' => $runId,
            'businesses' => $savedBusinesses,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        writeDatabaseDebug('save_error.txt', $exception->getMessage());

        return [
            'enabled' => true,
            'saved' => false,
            'message' => 'No se pudo guardar en MySQL',
        ];
    }
}

function upsertBusiness(PDO $pdo, array $business): int
{
    $analysis = $business['analysis'] ?? [];
    $audit = $business['audit'] ?? [];
    $hash = businessDatabaseHash($business);

    $statement = $pdo->prepare(
        'INSERT INTO businesses (
            business_hash, name, category, address, city, postal_code, phone, email, website,
            website_is_doubtful, instagram, facebook, latitude, longitude, score, level_name,
            issues_json, audit_json, raw_json, last_seen_at
        ) VALUES (
            :business_hash, :name, :category, :address, :city, :postal_code, :phone, :email, :website,
            :website_is_doubtful, :instagram, :facebook, :latitude, :longitude, :score, :level_name,
            :issues_json, :audit_json, :raw_json, CURRENT_TIMESTAMP
        )
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            category = VALUES(category),
            address = VALUES(address),
            city = VALUES(city),
            postal_code = VALUES(postal_code),
            phone = VALUES(phone),
            email = VALUES(email),
            website = VALUES(website),
            website_is_doubtful = VALUES(website_is_doubtful),
            instagram = VALUES(instagram),
            facebook = VALUES(facebook),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            score = VALUES(score),
            level_name = VALUES(level_name),
            issues_json = VALUES(issues_json),
            audit_json = VALUES(audit_json),
            raw_json = VALUES(raw_json),
            last_seen_at = CURRENT_TIMESTAMP'
    );

    $statement->execute([
        ':business_hash' => $hash,
        ':name' => $business['name'] ?? '',
        ':category' => $business['category'] ?? '',
        ':address' => $business['address'] ?? '',
        ':city' => $business['city'] ?? '',
        ':postal_code' => $business['postal_code'] ?? '',
        ':phone' => $business['phone'] ?? '',
        ':email' => $business['email'] ?? '',
        ':website' => $business['website'] ?? '',
        ':website_is_doubtful' => !empty($business['website_is_doubtful']) ? 1 : 0,
        ':instagram' => $business['instagram'] ?? '',
        ':facebook' => $business['facebook'] ?? '',
        ':latitude' => $business['latitude'] ?? null,
        ':longitude' => $business['longitude'] ?? null,
        ':score' => $analysis['score'] ?? null,
        ':level_name' => $analysis['level'] ?? '',
        ':issues_json' => json_encode($analysis['issues'] ?? [], JSON_UNESCAPED_UNICODE),
        ':audit_json' => json_encode($audit, JSON_UNESCAPED_UNICODE),
        ':raw_json' => json_encode($business, JSON_UNESCAPED_UNICODE),
    ]);

    $id = (int) $pdo->lastInsertId();

    if ($id > 0) {
        return $id;
    }

    $select = $pdo->prepare('SELECT id FROM businesses WHERE business_hash = :business_hash LIMIT 1');
    $select->execute([':business_hash' => $hash]);

    return (int) ($select->fetchColumn() ?: 0);
}

function businessDatabaseHash(array $business): string
{
    return sha1(implode('|', [
        normalizeText($business['name'] ?? ''),
        normalizeText($business['address'] ?? ''),
        round((float) ($business['latitude'] ?? 0), 5),
        round((float) ($business['longitude'] ?? 0), 5),
    ]));
}

function ensureLeadDbSchema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS search_runs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            postal_code VARCHAR(10) NOT NULL,
            keyword VARCHAR(120) NOT NULL,
            filter_name VARCHAR(60) NOT NULL DEFAULT "all",
            total_results INT UNSIGNED NOT NULL DEFAULT 0,
            cached TINYINT(1) NOT NULL DEFAULT 0,
            stats_json LONGTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_search_runs_lookup (postal_code, keyword, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS businesses (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            business_hash CHAR(40) NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(120) NULL,
            address VARCHAR(500) NULL,
            city VARCHAR(160) NULL,
            postal_code VARCHAR(10) NULL,
            phone VARCHAR(60) NULL,
            email VARCHAR(180) NULL,
            website VARCHAR(500) NULL,
            website_is_doubtful TINYINT(1) NOT NULL DEFAULT 0,
            instagram VARCHAR(500) NULL,
            facebook VARCHAR(500) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            score INT UNSIGNED NULL,
            level_name VARCHAR(60) NULL,
            issues_json LONGTEXT NULL,
            audit_json LONGTEXT NULL,
            raw_json LONGTEXT NULL,
            first_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_business_hash (business_hash),
            INDEX idx_businesses_score (score),
            INDEX idx_businesses_postal_category (postal_code, category),
            INDEX idx_businesses_contact (phone, email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS search_run_businesses (
            search_run_id BIGINT UNSIGNED NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL,
            position_number INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (search_run_id, business_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function writeDatabaseDebug(string $filename, string $message): void
{
    $dir = __DIR__ . '/../storage/debug';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($dir . '/' . $filename, $message);
}
