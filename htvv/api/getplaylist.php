<?php
// Developer eonbry
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(32767);
header("Content-Type: application/json; charset=utf-8");

function get_panel_status_text() {
    $notePath = __DIR__ . '/note.json';
    if (!file_exists($notePath)) {
        return '';
    }
    $raw = @file_get_contents($notePath);
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return '';
    }
    $keys = ['status_text', 'home_status_text', 'welcome_text', 'marquee_text', 'status'];
    foreach ($keys as $key) {
        if (!empty($json[$key])) {
            return trim((string) $json[$key]);
        }
    }
    return '';
}


function get_panel_notice_config() {
    $notePath = __DIR__ . '/note.json';
    if (!file_exists($notePath)) {
        return [
            'player_notice_enabled' => false,
            'player_notice_text' => '',
            'player_notice_duration_sec' => 120,
            'player_notice_started_at' => 0,
            'player_notice_scope' => 'all'
        ];
    }
    $raw = @file_get_contents($notePath);
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        $json = [];
    }
    return [
        'player_notice_enabled' => !empty($json['player_notice_enabled']),
        'player_notice_text' => !empty($json['player_notice_text']) ? trim((string)$json['player_notice_text']) : '',
        'player_notice_duration_sec' => !empty($json['player_notice_duration_sec']) ? max(10, (int)$json['player_notice_duration_sec']) : 120,
        'player_notice_started_at' => !empty($json['player_notice_started_at']) ? (int)$json['player_notice_started_at'] : 0,
        'player_notice_scope' => !empty($json['player_notice_scope']) ? trim((string)$json['player_notice_scope']) : 'all'
    ];
}

function normalize_date_value($value) {
    $value = trim((string) $value);
    if ($value === '' || $value === '0' || $value === '0000-00-00') {
        return '';
    }
    return $value;
}

function get_days_remaining($expireDate) {
    $expireDate = normalize_date_value($expireDate);
    if ($expireDate === '') {
        return PHP_INT_MAX;
    }
    $actualDate = strtotime(date('Y-m-d'));
    $targetDate = strtotime($expireDate);
    if ($targetDate === false) {
        return -1;
    }
    return (int) floor(($targetDate - $actualDate) / 86400);
}

function is_active_row(array $row) {
    $daysRemaining = get_days_remaining(isset($row['expire_date']) ? $row['expire_date'] : '');
    if ($daysRemaining < 0) {
        return false;
    }
    foreach (['active', 'enabled'] as $boolField) {
        if (isset($row[$boolField]) && $row[$boolField] !== '' && $row[$boolField] !== null) {
            $value = strtolower(trim((string) $row[$boolField]));
            if (in_array($value, ['0', 'false', 'no', 'off', 'disabled', 'inactive'], true)) {
                return false;
            }
        }
    }
    foreach (['status', 'status_text', 'message'] as $textField) {
        if (!empty($row[$textField])) {
            $value = strtolower(trim((string) $row[$textField]));
            if (strpos($value, 'expir') !== false || strpos($value, 'venc') !== false || strpos($value, 'block') !== false || strpos($value, 'disabled') !== false || strpos($value, 'inactive') !== false) {
                return false;
            }
        }
    }
    return true;
}

function build_playlist_entry(array $row) {
    if (isset($row['type']) && (string) $row['type'] === '0') {
        $playlist_type = 'general';
        $url_user = $row['dns'] . '/get.php?username=' . $row['username'] . '&password=' . $row['password'] . '&type=m3u_plus&output=ts';
        $username = $row['username'];
        $password = $row['password'];
    } else {
        $playlist_type = 'general';
        $url_user = $row['url'];
        $username = 'playlist';
        $password = 'playlist';
    }

    $expireDate = isset($row['expire_date']) ? normalize_date_value($row['expire_date']) : '';
    $daysRemaining = get_days_remaining($expireDate);
    return [
        'url' => $url_user,
        'epg_url' => isset($row['epg_url']) ? $row['epg_url'] : '',
        'playlist_name' => !empty($row['title']) ? $row['title'] : (isset($row['name']) ? $row['name'] : ''),
        'username' => $username,
        'password' => $password,
        'playlist_type' => $playlist_type,
        'id' => isset($row['id']) ? (string) $row['id'] : '0',
        'expire_date' => $expireDate,
        'expDate' => !empty($expireDate) ? (string) strtotime($expireDate) : '',
        'validity_days' => $daysRemaining === PHP_INT_MAX ? 999999 : max(0, $daysRemaining),
        'active' => is_active_row($row) ? '1' : '0',
        'status' => is_active_row($row) ? 'ACTIVE' : 'EXPIRED'
    ];
}


function get_active_theme(SQLite3 $db) {
    $res = $db->query("SELECT * FROM theme WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    if ($res) {
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if (is_array($row)) {
            return $row;
        }
    }
    $res = $db->query("SELECT * FROM theme ORDER BY id ASC LIMIT 1");
    if ($res) {
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if (is_array($row)) {
            return $row;
        }
    }
    return null;
}


function get_splash_slider(SQLite3 $db) {
    @$db->exec("CREATE TABLE IF NOT EXISTS splash_slider (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title TEXT DEFAULT '', image_url TEXT NOT NULL, sort_order INTEGER DEFAULT 0, is_active INTEGER DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
    $items = [];
    $res = $db->query("SELECT id, title, image_url, sort_order, is_active FROM splash_slider WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    if ($res) {
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $items[] = [
                "id" => isset($row["id"]) ? (string)$row["id"] : "0",
                "title" => isset($row["title"]) ? $row["title"] : "",
                "image_url" => isset($row["image_url"]) ? $row["image_url"] : "",
                "sort_order" => isset($row["sort_order"]) ? (int)$row["sort_order"] : 0,
                "is_active" => isset($row["is_active"]) ? (string)$row["is_active"] : "1"
            ];
        }
    }
    return $items;
}

function choose_best_row(array $rows) {
    if (empty($rows)) {
        return null;
    }
    usort($rows, function ($a, $b) {
        $daysA = get_days_remaining(isset($a['expire_date']) ? $a['expire_date'] : '');
        $daysB = get_days_remaining(isset($b['expire_date']) ? $b['expire_date'] : '');
        return $daysB <=> $daysA;
    });
    return $rows[0];
}

$started = microtime(true);
$db23 = new SQLite3("./.ansdb.db");
$re2s = $db23->query("SELECT * FROM theme");
$dat2a = [];
while ($row23 = $re2s->fetchArray(SQLITE3_ASSOC)) {
    $dat2a[] = [
        "name" => $row23["name"],
        "url" => $row23["url"],
        "background_url" => $row23["url"],
        "logo_url" => isset($row23["logo_url"]) ? $row23["logo_url"] : "",
        "is_active" => isset($row23["is_active"]) ? (string)$row23["is_active"] : "0"
    ];
}
$activeTheme = get_active_theme($db23);
$splashSlider = get_splash_slider($db23);
$activeThemePayload = null;
if (is_array($activeTheme)) {
    $activeThemePayload = [
        "id" => isset($activeTheme["id"]) ? (string)$activeTheme["id"] : "0",
        "name" => isset($activeTheme["name"]) ? $activeTheme["name"] : "",
        "background_url" => isset($activeTheme["url"]) ? $activeTheme["url"] : "",
        "logo_url" => isset($activeTheme["logo_url"]) ? $activeTheme["logo_url"] : "",
        "is_active" => "1"
    ];
}

$jsondata = @file_get_contents("./update.json");
$data = json_decode($jsondata, true);
$json = isset($data["app_info"]) && is_array($data["app_info"]) ? $data["app_info"] : [];
$android_version_code = isset($json["android_version_code"]) ? $json["android_version_code"] : "1.0.0";
$apk_url = isset($json["apk_url"]) ? $json["apk_url"] : "";

$db = new SQLite3("./.ansdb.db");
require_once dirname(__DIR__) . '/includes/app_connection_helper.php';
app_connection_ensure_schema($db);
app_connection_validate_package_or_exit($db, isset($_REQUEST['package_name']) ? $_REQUEST['package_name'] : '');
$lang = @file_get_contents("./language.json");
$note = @file_get_contents("./note.json");
$statusText = get_panel_status_text();
$panelNotice = get_panel_notice_config();
$get_key = rand(100000, 999999);

if (!isset($_GET["m"])) {
    echo json_encode(["error" => "No Way... Sorry!!!"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$address = str_replace("/android", "", $_GET["m"]);
$address1 = strtoupper($address);
$res = $db->query("SELECT * FROM ibo WHERE mac_address='" . $db->escapeString($address1) . "'");
$rows = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $rows[] = $row;
}

$requestKey = isset($_REQUEST['key']) ? trim((string)$_REQUEST['key']) : '';
$bestKeyRow = choose_best_row($rows);
$rowKey = is_array($bestKeyRow) && isset($bestKeyRow['key']) ? trim((string)$bestKeyRow['key']) : '';
app_connection_validate_key_or_exit($db, $requestKey, $rowKey);
$cfg = app_connection_get($db);
$expectedPackage = isset($cfg['package_name']) ? trim((string)$cfg['package_name']) : '';
$effectiveSecurityKey = $rowKey !== '' ? $rowKey : app_connection_get_app_key($db);

if (empty($rows)) {
    $response = [
        "android_version_code" => $android_version_code,
        "apk_url" => $apk_url,
        "mac_registered" => true,
        "urls" => [[
            "url" => "http://no.play",
            "epg_url" => "",
            "playlist_name" => "No Playlist",
            "username" => "demo",
            "password" => "demo",
            "playlist_type" => "general",
            "id" => "0"
        ]],
        "themes" => $dat2a,
        "active_theme" => $activeThemePayload,
        "theme_background_url" => is_array($activeTheme) ? (isset($activeTheme["url"]) ? $activeTheme["url"] : "") : "",
        "theme_logo_url" => is_array($activeTheme) ? (isset($activeTheme["logo_url"]) ? $activeTheme["logo_url"] : "") : "",
        "splash_slider" => $splashSlider,
        "trial_days" => 0,
        "device_key" => (string)$get_key,
        "is_trial" => 0,
        "expire_date" => "2021-11-21",
        "expDate" => (string) strtotime("2021-11-21"),
        "notification" => json_decode($note, true),
        "languages" => [json_decode($lang, true)],
        "calc_time" => 0.221
    ];
} else {
    $validRows = [];
    foreach ($rows as $row) {
        if (is_active_row($row)) {
            $validRows[] = $row;
        }
    }

    $activeRows = !empty($validRows) ? $validRows : [];
    if (!empty($activeRows)) {
        $data2 = [];
        foreach ($activeRows as $row2) {
            $data2[] = build_playlist_entry($row2);
        }
        $bestRow = choose_best_row($activeRows);
        $expire_date = isset($bestRow['expire_date']) ? normalize_date_value($bestRow['expire_date']) : '';
        $daysRemaining = get_days_remaining($expire_date);
        $key = isset($bestRow['key']) ? $bestRow['key'] : (string)$get_key;

        $end = microtime(true);
        $difference = $end - $started;
        $response = [
            "android_version_code" => $android_version_code,
            "apk_url" => $apk_url,
            "mac_registered" => true,
            "urls" => $data2,
            "themes" => $dat2a,
            "active_theme" => $activeThemePayload,
            "theme_background_url" => is_array($activeTheme) ? (isset($activeTheme["url"]) ? $activeTheme["url"] : "") : "",
            "theme_logo_url" => is_array($activeTheme) ? (isset($activeTheme["logo_url"]) ? $activeTheme["logo_url"] : "") : "",
        "splash_slider" => $splashSlider,
            "trial_days" => $daysRemaining === PHP_INT_MAX ? 365 : max(0, $daysRemaining),
            "device_key" => $key,
            "is_trial" => 0,
            "expire_date" => $expire_date,
            "expDate" => !empty($expire_date) ? (string) strtotime($expire_date) : "",
            "notification" => json_decode($note, true),
            "languages" => [json_decode($lang, true)],
            "calc_time" => number_format($difference, 16)
        ];
    } else {
        $bestRow = choose_best_row($rows);
        $expire_date = isset($bestRow['expire_date']) ? normalize_date_value($bestRow['expire_date']) : '';
        $key = isset($bestRow['key']) ? $bestRow['key'] : (string)$get_key;
        $response = [
            "android_version_code" => $android_version_code,
            "apk_url" => $apk_url,
            "mac_registered" => true,
            "urls" => [[
                "url" => "http://tprohd.net:8080/get.php?username=iboplayer&password=UDAcdwofMG&type=m3u_plus&output=ts",
                "epg_url" => "",
                "playlist_name" => "Expired",
                "username" => "Expired",
                "password" => "Expired",
                "playlist_type" => "general",
                "id" => "00"
            ]],
            "themes" => $dat2a,
            "active_theme" => $activeThemePayload,
            "theme_background_url" => is_array($activeTheme) ? (isset($activeTheme["url"]) ? $activeTheme["url"] : "") : "",
            "theme_logo_url" => is_array($activeTheme) ? (isset($activeTheme["logo_url"]) ? $activeTheme["logo_url"] : "") : "",
        "splash_slider" => $splashSlider,
            "trial_days" => 1,
            "device_key" => $key,
            "is_trial" => 1,
            "expire_date" => $expire_date,
            "expDate" => !empty($expire_date) ? (string) strtotime($expire_date) : "",
            "notification" => json_decode($note, true),
            "languages" => [json_decode($lang, true)],
            "calc_time" => 0.221
        ];
    }
}

$response["secure_panel"] = 1;
$response["app_key_valid"] = 1;
$response["app_key_echo"] = $effectiveSecurityKey;
$response["package_name_echo"] = $expectedPackage;
$response["protection_version"] = "umx_key_lock_v2";

$response["player_notice_enabled"] = $panelNotice["player_notice_enabled"];
$response["player_notice_text"] = $panelNotice["player_notice_text"];
$response["player_notice_duration_sec"] = $panelNotice["player_notice_duration_sec"];
$response["player_notice_started_at"] = $panelNotice["player_notice_started_at"];
$response["player_notice_scope"] = $panelNotice["player_notice_scope"];

if ($statusText !== '') {
    $response["status_text"] = $statusText;
    $response["home_status_text"] = $statusText;
    $response["welcome_text"] = $statusText;
    $response["marquee_text"] = $statusText;
    $response["status"] = $statusText;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
