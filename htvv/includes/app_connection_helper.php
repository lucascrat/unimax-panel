<?php
if (!function_exists('app_connection_db')) {
    function app_connection_db() {
        return new SQLite3(__DIR__ . '/../api/.ansdb.db');
    }
}

if (!function_exists('app_connection_ensure_schema')) {
    function app_connection_ensure_schema(SQLite3 $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS app_connection_settings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, panel_url TEXT DEFAULT '', aes_secret TEXT DEFAULT '', package_name TEXT DEFAULT '', panel_url_aes TEXT DEFAULT '', app_key TEXT DEFAULT '', updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
        $res = $db->querySingle("SELECT COUNT(*) FROM app_connection_settings");
        if ((int)$res <= 0) {
            $defaultUrl = 'https://testes.painelmaster.lol/htvv/api/';
            $defaultAes = 'iWzaCCSanohD0afncoRhCBLJxViTPAxL';
            $defaultPackage = 'br.com.jsantiago.jshtv';
            $defaultUrlAes = app_connection_encrypt_url($defaultUrl, $defaultAes);
            $defaultAppKey = 'UNIMAX_APP_KEY_2026';
            @$db->exec("ALTER TABLE app_connection_settings ADD COLUMN app_key TEXT DEFAULT ''");
            $stmt = $db->prepare("INSERT INTO app_connection_settings (panel_url, aes_secret, package_name, panel_url_aes, app_key, updated_at) VALUES (:panel_url, :aes_secret, :package_name, :panel_url_aes, :app_key, datetime('now'))");
            $stmt->bindValue(':panel_url', $defaultUrl, SQLITE3_TEXT);
            $stmt->bindValue(':aes_secret', $defaultAes, SQLITE3_TEXT);
            $stmt->bindValue(':package_name', $defaultPackage, SQLITE3_TEXT);
            $stmt->bindValue(':panel_url_aes', $defaultUrlAes, SQLITE3_TEXT);
            $stmt->bindValue(':app_key', $defaultAppKey, SQLITE3_TEXT);
            $stmt->execute();
        } else {
            @$db->exec("ALTER TABLE app_connection_settings ADD COLUMN app_key TEXT DEFAULT ''");
        }
    }
}

if (!function_exists('app_connection_normalize_url')) {
    function app_connection_normalize_url($url) {
        $url = trim((string)$url);
        if ($url === '') return '';
        return rtrim($url, '/') . '/';
    }
}

if (!function_exists('app_connection_key_bytes')) {
    function app_connection_key_bytes($secret) {
        return hash('sha256', (string)$secret, true);
    }
}

if (!function_exists('app_connection_encrypt_url')) {
    function app_connection_encrypt_url($plainUrl, $secret) {
        $plainUrl = app_connection_normalize_url($plainUrl);
        if ($plainUrl === '' || trim((string)$secret) === '') return '';
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plainUrl, 'AES-256-CBC', app_connection_key_bytes($secret), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) return '';
        return base64_encode($iv . $cipher);
    }
}

if (!function_exists('app_connection_get')) {
    function app_connection_get(SQLite3 $db = null) {
        if ($db === null) {
            $db = app_connection_db();
        }
        app_connection_ensure_schema($db);
        $res = $db->query("SELECT * FROM app_connection_settings ORDER BY id DESC LIMIT 1");
        $row = $res ? $res->fetchArray(SQLITE3_ASSOC) : null;
        if (!is_array($row)) {
            return [
                'panel_url' => '',
                'aes_secret' => '',
                'package_name' => '',
                'panel_url_aes' => '',
                'app_key' => ''
            ];
        }
        $row['panel_url'] = app_connection_normalize_url(isset($row['panel_url']) ? $row['panel_url'] : '');
        return $row;
    }
}

if (!function_exists('app_connection_save')) {
    function app_connection_save(SQLite3 $db, $panelUrl, $aesSecret, $packageName, $appKey) {
        app_connection_ensure_schema($db);
        $panelUrl = app_connection_normalize_url($panelUrl);
        $aesSecret = trim((string)$aesSecret);
        $packageName = trim((string)$packageName);
        $appKey = trim((string)$appKey);
        $panelUrlAes = app_connection_encrypt_url($panelUrl, $aesSecret);
        @$db->exec("ALTER TABLE app_connection_settings ADD COLUMN app_key TEXT DEFAULT ''");
        $db->exec("DELETE FROM app_connection_settings");
        $stmt = $db->prepare("INSERT INTO app_connection_settings (panel_url, aes_secret, package_name, panel_url_aes, app_key, updated_at) VALUES (:panel_url, :aes_secret, :package_name, :panel_url_aes, :app_key, datetime('now'))");
        $stmt->bindValue(':panel_url', $panelUrl, SQLITE3_TEXT);
        $stmt->bindValue(':aes_secret', $aesSecret, SQLITE3_TEXT);
        $stmt->bindValue(':package_name', $packageName, SQLITE3_TEXT);
        $stmt->bindValue(':panel_url_aes', $panelUrlAes, SQLITE3_TEXT);
        $stmt->bindValue(':app_key', $appKey, SQLITE3_TEXT);
        return $stmt->execute() !== false;
    }
}


if (!function_exists('app_connection_get_app_key')) {
    function app_connection_get_app_key(SQLite3 $db = null) {
        $cfg = app_connection_get($db);
        return isset($cfg['app_key']) ? trim((string)$cfg['app_key']) : '';
    }
}

if (!function_exists('app_connection_validate_key_or_exit')) {
    function app_connection_validate_key_or_exit(SQLite3 $db, $requestKey, $rowKey = '') {
        $globalKey = app_connection_get_app_key($db);
        $expected = trim((string)($rowKey !== '' ? $rowKey : $globalKey));
        if ($expected === '') {
            return true;
        }
        $received = trim((string)$requestKey);
        if ($received !== '' && hash_equals($expected, $received)) {
            return true;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'APP_KEY_INVALID'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('app_connection_validate_package_or_exit')) {
    function app_connection_validate_package_or_exit(SQLite3 $db, $requestPackageName) {
        $cfg = app_connection_get($db);
        $expected = isset($cfg['package_name']) ? trim((string)$cfg['package_name']) : '';
        $received = trim((string)$requestPackageName);
        if ($expected === '') {
            return true;
        }
        if ($received !== '' && strcasecmp($expected, $received) === 0) {
            return true;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'PACKAGE_NAME_INVALID',
            'expected_package' => $expected
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
