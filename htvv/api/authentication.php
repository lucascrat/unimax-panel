<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

$started = microtime(true);
$db = new SQLite3("./.ansdb.db");
require_once dirname(__DIR__) . '/includes/app_connection_helper.php';
app_connection_ensure_schema($db);
app_connection_validate_package_or_exit($db, isset($_REQUEST['package_name']) ? $_REQUEST['package_name'] : '');

function json_response($array) {
    echo json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

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

function choose_best_row(array $rows) {
    if (empty($rows)) {
        return null;
    }

    $validRows = [];
    foreach ($rows as $row) {
        if (is_active_row($row)) {
            $validRows[] = $row;
        }
    }

    $pool = !empty($validRows) ? $validRows : $rows;
    usort($pool, function ($a, $b) {
        $daysA = get_days_remaining(isset($a['expire_date']) ? $a['expire_date'] : '');
        $daysB = get_days_remaining(isset($b['expire_date']) ? $b['expire_date'] : '');
        return $daysB <=> $daysA;
    });

    return $pool[0];
}

if (!isset($_REQUEST['e'])) {
    json_response([
        "success" => false,
        "message" => "Parâmetro 'e' não informado"
    ]);
}

$eRaw = $_REQUEST['e'];
$params = [];
parse_str(ltrim($eRaw, '?'), $params);

$wifi = isset($params['wifi']) ? $params['wifi'] : '';
$lan  = isset($params['lan']) ? $params['lan'] : '';

$wifi = strtoupper(str_replace("/android", "", $wifi));
$lan  = strtoupper(str_replace("/android", "", $lan));

$invalidMacs = [
    '02:00:00:00:00:00',
    '00:00:00:00:00',
    '00:00:00:00:00:00'
];

if (in_array($wifi, $invalidMacs, true)) {
    $wifi = '';
}
if (in_array($lan, $invalidMacs, true)) {
    $lan = '';
}

if (empty($wifi) && empty($lan)) {
    json_response([
        "success" => false,
        "message" => "MAC não informado ou inválido"
    ]);
}

$address1 = $db->escapeString($wifi);
$address2 = $db->escapeString($lan);

$sql = "
    SELECT *
    FROM ibo
    WHERE mac_address = '{$address1}'
       OR mac_address = '{$address2}'
";

$res = $db->query($sql);
$rows = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $rows[] = $row;
}

if (empty($rows)) {
    json_response([
        "success" => false,
        "message" => "MAC não registrado"
    ]);
}

$row = choose_best_row($rows);
$expire_date = isset($row['expire_date']) ? normalize_date_value($row['expire_date']) : '';
$rowKey = isset($row['key']) ? trim((string)$row['key']) : '';
$requestKey = isset($params['key']) ? trim((string)$params['key']) : '';
app_connection_validate_key_or_exit($db, $requestKey, $rowKey);
$key = $rowKey !== '' ? $rowKey : app_connection_get_app_key($db);
$daysRemaining = get_days_remaining($expire_date);

if ($daysRemaining < 0) {
    json_response([
        "success" => false,
        "message" => "Assinatura expirada em {$expire_date}"
    ]);
}

$url_user = isset($row['dns']) ? $row['dns'] : '';
$username = isset($row['username']) ? $row['username'] : '';
$password = isset($row['password']) ? $row['password'] : '';
$statusText = get_panel_status_text();
$panelNotice = get_panel_notice_config();

$cfg = app_connection_get($db);
$expectedPackage = isset($cfg['package_name']) ? trim((string)$cfg['package_name']) : '';

$data = [
    "success" => true,
    "server" => $url_user,
    "username" => $username,
    "password" => $password,
    "validity_days" => $daysRemaining === PHP_INT_MAX ? 999999 : $daysRemaining,
    "activecode" => $key,
    "expire_date" => $expire_date,
    "expDate" => !empty($expire_date) ? (string) strtotime($expire_date) : "",
    "message" => "Login realizado com sucesso",
    "secure_panel" => 1,
    "app_key_valid" => 1,
    "app_key_echo" => $key,
    "package_name_echo" => $expectedPackage,
    "protection_version" => "umx_key_lock_v2"
];

$response["player_notice_enabled"] = $panelNotice["player_notice_enabled"];
$response["player_notice_text"] = $panelNotice["player_notice_text"];
$response["player_notice_duration_sec"] = $panelNotice["player_notice_duration_sec"];
$response["player_notice_started_at"] = $panelNotice["player_notice_started_at"];
$response["player_notice_scope"] = $panelNotice["player_notice_scope"];

if ($statusText !== '') {
    $data["status_text"] = $statusText;
    $data["home_status_text"] = $statusText;
    $data["welcome_text"] = $statusText;
    $data["marquee_text"] = $statusText;
    $data["status"] = $statusText;
}

$end = microtime(true);
$difference = $end - $started;
$data["calc_time"] = number_format($difference, 6);

json_response($data);
?>
