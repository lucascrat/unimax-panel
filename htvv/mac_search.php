<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

$db = new SQLite3("./api/.ansdb.db");
require_once __DIR__ . '/includes/app_connection_helper.php';
$db->exec("CREATE TABLE IF NOT EXISTS ibo(id INTEGER PRIMARY KEY NOT NULL, mac_address VARCHAR(100), key VARCHAR(100), username VARCHAR(100), password VARCHAR(100), expire_date VARCHAR(100), dns VARCHAR(100), epg_url VARCHAR(100), title VARCHAR(100), url VARCHAR(100), type VARCHAR(100))");
@$db->exec("ALTER TABLE ibo ADD COLUMN support_contact VARCHAR(100)");

function normalize_mac($mac) {
    $clean = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', (string)$mac));
    $clean = substr($clean, 0, 12);
    if ($clean === '') {
        return '';
    }
    return implode(':', str_split($clean, 2));
}

function parse_playlist_input($input) {
    $input = trim((string)$input);
    if ($input === '') {
        return [null, 'Informe a linha Xtream Codes ou a URL M3U.'];
    }

    if (preg_match('~https?://[^\s]+~i', $input, $m)) {
        $input = $m[0];
    }

    $parts = parse_url($input);
    parse_str($parts['query'] ?? '', $query);

    $host = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? '');
    if (!empty($parts['port'])) {
        $host .= ':' . $parts['port'];
    }

    $username = trim($query['username'] ?? '');
    $password = trim($query['password'] ?? '');

    if ($host === 'http://' || $host === 'https://' || $username === '' || $password === '') {
        return [null, 'Não foi possível extrair DNS, usuário e senha da linha informada.'];
    }

    return [[
        'dns' => rtrim($host, '/'),
        'username' => $username,
        'password' => $password,
        'url' => rtrim($host, '/') . '/get.php?username=' . rawurlencode($username) . '&password=' . rawurlencode($password) . '&type=m3u_plus&output=ts'
    ], null];
}

function fetch_all_by_mac(SQLite3 $db, $mac) {
    $rows = [];
    $stmt = $db->prepare('SELECT * FROM ibo WHERE mac_address = :mac ORDER BY id DESC');
    $stmt->bindValue(':mac', $mac, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function count_by_mac(SQLite3 $db, $mac) {
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM ibo WHERE mac_address = :mac');
    $stmt->bindValue(':mac', $mac, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    return (int)($row['total'] ?? 0);
}

$active_tab = 'search';
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'] === 'create' ? 'create' : 'search';
}

$search_mac = '';
$mac_data = [];
$search_performed = false;
$error = '';
$success = '';

if (isset($_POST['delete_submit'])) {
    $id = (int)($_POST['id'] ?? 0);
    $searchMacAfter = normalize_mac($_POST['search_mac'] ?? '');

    if ($id <= 0) {
        $error = 'Cadastro inválido para exclusão.';
    } else {
        $stmt = $db->prepare('DELETE FROM ibo WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        $redirect = 'mac_search.php';
        if ($searchMacAfter !== '') {
            $redirect .= '?search=' . urlencode($searchMacAfter);
        }
        header('Location: ' . $redirect . '&deleted=1');
        exit;
    }
}

if (isset($_POST['create_submit'])) {
    $mac = normalize_mac($_POST['mac_address'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $expire = trim($_POST['expire_date'] ?? '');
    $playlistInput = trim($_POST['playlist_input'] ?? '');
    $supportContact = trim($_POST['support_contact'] ?? '');

    [$parsed, $parseError] = parse_playlist_input($playlistInput);
    $dateTs = strtotime($expire);
    $expireDate = $dateTs ? date('Y-m-d', $dateTs) : '';

    if ($mac === '' || strlen(str_replace(':', '', $mac)) !== 12) {
        $error = 'Informe um ID do dispositivo válido.';
    } elseif ($title === '') {
        $error = 'Informe o nome do cliente.';
    } elseif ($expireDate === '') {
        $error = 'Informe uma data de expiração válida.';
    } elseif ($parseError) {
        $error = $parseError;
    } else {
        $stmt = $db->prepare('INSERT INTO ibo (mac_address, key, username, password, expire_date, dns, epg_url, title, url, type, support_contact) VALUES (:mac, :key, :username, :password, :expire, :dns, :epg, :title, :url, :type, :support_contact)');
        $stmt->bindValue(':mac', $mac, SQLITE3_TEXT);
        $stmt->bindValue(':key', app_connection_get_app_key($db), SQLITE3_TEXT);
        $stmt->bindValue(':username', $parsed['username'], SQLITE3_TEXT);
        $stmt->bindValue(':password', $parsed['password'], SQLITE3_TEXT);
        $stmt->bindValue(':expire', $expireDate, SQLITE3_TEXT);
        $stmt->bindValue(':dns', $parsed['dns'], SQLITE3_TEXT);
        $stmt->bindValue(':epg', '', SQLITE3_TEXT);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':url', $parsed['url'], SQLITE3_TEXT);
        $stmt->bindValue(':type', '0', SQLITE3_TEXT);
        $stmt->bindValue(':support_contact', $supportContact, SQLITE3_TEXT);
        $stmt->execute();

        header('Location: mac_search.php?search=' . urlencode($mac) . '&created=1');
        exit;
    }
}

if (isset($_POST['update_submit'])) {
    $id = (int)($_POST['id'] ?? 0);
    $originalSearchMac = normalize_mac($_POST['search_mac'] ?? '');
    $mac = normalize_mac($_POST['mac_address'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $expire = trim($_POST['expire_date'] ?? '');
    $playlistInput = trim($_POST['playlist_input'] ?? '');
    $supportContact = trim($_POST['support_contact'] ?? '');

    [$parsed, $parseError] = parse_playlist_input($playlistInput);
    $dateTs = strtotime($expire);
    $expireDate = $dateTs ? date('Y-m-d', $dateTs) : '';

    if ($id <= 0) {
        $error = 'Cadastro inválido para atualização.';
    } elseif ($mac === '' || strlen(str_replace(':', '', $mac)) !== 12) {
        $error = 'Informe um ID do dispositivo válido.';
    } elseif ($title === '') {
        $error = 'Informe o nome do cliente.';
    } elseif ($expireDate === '') {
        $error = 'Informe uma data de expiração válida.';
    } elseif ($parseError) {
        $error = $parseError;
    } else {
        $update = $db->prepare('UPDATE ibo SET mac_address = :mac, key = :key, username = :username, password = :password, expire_date = :expire, dns = :dns, epg_url = :epg, title = :title, url = :url, type = :type, support_contact = :support_contact WHERE id = :id');
        $update->bindValue(':mac', $mac, SQLITE3_TEXT);
        $update->bindValue(':key', app_connection_get_app_key($db), SQLITE3_TEXT);
        $update->bindValue(':username', $parsed['username'], SQLITE3_TEXT);
        $update->bindValue(':password', $parsed['password'], SQLITE3_TEXT);
        $update->bindValue(':expire', $expireDate, SQLITE3_TEXT);
        $update->bindValue(':dns', $parsed['dns'], SQLITE3_TEXT);
        $update->bindValue(':epg', '', SQLITE3_TEXT);
        $update->bindValue(':title', $title, SQLITE3_TEXT);
        $update->bindValue(':url', $parsed['url'], SQLITE3_TEXT);
        $update->bindValue(':type', '0', SQLITE3_TEXT);
        $update->bindValue(':support_contact', $supportContact, SQLITE3_TEXT);
        $update->bindValue(':id', $id, SQLITE3_INTEGER);
        $update->execute();

        $redirectMac = $mac !== '' ? $mac : $originalSearchMac;
        header('Location: mac_search.php?search=' . urlencode($redirectMac) . '&updated=1');
        exit;
    }
}

if (isset($_POST['search'])) {
    $search_mac = normalize_mac($_POST['mac_address'] ?? '');
    $search_performed = true;
    $active_tab = 'search';

    if ($search_mac === '' || strlen(str_replace(':', '', $search_mac)) !== 12) {
        $error = 'Informe um MAC Address válido.';
    } else {
        $mac_data = fetch_all_by_mac($db, $search_mac);
    }
}

if (isset($_GET['search']) && !$search_performed) {
    $search_mac = normalize_mac($_GET['search'] ?? '');
    $search_performed = true;
    $active_tab = 'search';

    if ($search_mac === '' || strlen(str_replace(':', '', $search_mac)) !== 12) {
        $error = 'Informe um MAC Address válido.';
    } else {
        $mac_data = fetch_all_by_mac($db, $search_mac);
    }
}

if (isset($_GET['created'])) {
    $success = 'Cadastro criado com sucesso.';
}
if (isset($_GET['updated'])) {
    $success = 'Cadastro atualizado com sucesso.';
}
if (isset($_GET['deleted'])) {
    $success = 'Cadastro deletado com sucesso.';
}

$dbpans = new SQLite3("./api/.anspanel.db");
$resans = $dbpans->query("SELECT * FROM USERS WHERE ID='1'");
$rowans = $resans ? $resans->fetchArray(SQLITE3_ASSOC) : [];
$nameans = $rowans['NAME'] ?? 'Painel UniTV';
$logoans = $rowans['LOGO'] ?? '';
$logoSrc = !empty($logoans) ? $logoans : 'img/logo.png';
$totalResults = !empty($search_mac) ? count_by_mac($db, $search_mac) : 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="google" content="notranslate">
    <title><?php echo htmlspecialchars($nameans); ?> - Gerenciador MAC Premium</title>
    <link rel="shortcut icon" href="./img/logo.png" type="image/png">
    <link rel="icon" href="./img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/jquery.datetimepicker.min.css"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-0: #020617;
            --bg-1: #030b2a;
            --bg-2: #071238;
            --card-bg: rgba(8, 17, 51, 0.74);
            --border: rgba(255,255,255,0.08);
            --text-main: #eef2ff;
            --text-soft: #a9b3d4;
            --blue: #2563eb;
            --purple: #7c3aed;
            --red: #ef4444;
            --green: #10b981;
            --yellow: #f59e0b;
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                radial-gradient(circle at 78% 19%, rgba(239,68,68,0.30) 0, rgba(239,68,68,0.16) 18%, transparent 42%),
                radial-gradient(circle at 28% 77%, rgba(239,68,68,0.24) 0, rgba(239,68,68,0.10) 15%, transparent 32%),
                radial-gradient(circle at 49% 16%, rgba(37,99,235,0.26) 0, rgba(37,99,235,0.12) 14%, transparent 29%),
                linear-gradient(135deg, var(--bg-0) 0%, var(--bg-1) 42%, var(--bg-2) 100%);
            background-attachment: fixed;
            color: var(--text-main);
            overflow-x: hidden;
        }
        body::before, body::after {
            content: "";
            position: fixed;
            left: -12%; right: -12%; height: 18px; z-index: 0; pointer-events: none; border-radius: 999px; filter: blur(16px);
        }
        body::before {
            top: 27%;
            background: linear-gradient(90deg, rgba(255,45,162,0), rgba(112,52,255,0.95), rgba(255,45,162,0));
            box-shadow: 0 0 28px rgba(124,58,237,0.50);
        }
        body::after {
            top: 63%;
            background: linear-gradient(90deg, rgba(255,45,162,0), rgba(59,130,246,0.88), rgba(255,45,162,0));
            box-shadow: 0 0 24px rgba(59,130,246,0.35);
            opacity: 0.85;
        }
        .theme-orb { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; opacity: 0.72; }
        .theme-orb.orb-a { width: 540px; height: 540px; top: 74px; right: -175px; background: radial-gradient(circle at 32% 36%, rgba(255,255,255,0.10), rgba(239,68,68,0.86) 0, rgba(80,13,56,0.65) 58%, rgba(12,9,37,0.12) 100%); }
        .theme-orb.orb-b { width: 275px; height: 275px; bottom: 34px; left: 170px; background: radial-gradient(circle at 30% 38%, rgba(255,255,255,0.08), rgba(239,68,68,0.78) 0, rgba(57,9,48,0.70) 64%, transparent 100%); }
        .theme-orb.orb-c { width: 208px; height: 208px; top: 40px; left: 35%; background: radial-gradient(circle at 35% 35%, rgba(255,255,255,0.08), rgba(37,99,235,0.68) 0, rgba(7,19,56,0.72) 70%, transparent 100%); }
        .container-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .main-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 24px 50px rgba(0,0,0,0.18);
            backdrop-filter: blur(12px);
            width: 100%;
            max-width: 1200px;
            padding: 40px;
        }
        .header-section { text-align: center; margin-bottom: 40px; }
        .header-logo {
            width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(255,255,255,0.94); border-radius: 20px; padding: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(112,52,255,0.3);
        }
        .header-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .header-section h1 {
            font-size: 2rem; font-weight: 800; margin: 0 0 10px 0; background: linear-gradient(135deg, #fff, #a9b3d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .header-section p { color: var(--text-soft); font-size: 0.95rem; margin: 0; }
        .tabs-nav { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .tab-button {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-soft); padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;
        }
        .tab-button:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .tab-button.active { background: linear-gradient(90deg, var(--blue), var(--purple)); border-color: transparent; color: #fff; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #fff; font-size: 0.9rem; }
        .form-group label i { margin-right: 8px; color: #7c3aed; }
        .form-control, textarea, select {
            background: rgba(255,255,255,0.04) !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.10) !important; border-radius: 14px !important; min-height: 46px; box-shadow: none !important; font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .form-control::placeholder { color: #9ba8d0; }
        .form-control:focus, textarea:focus, select:focus { border-color: rgba(96,165,250,0.60) !important; box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.15) !important; }
        .btn {
            border-radius: 14px; font-weight: 700; border: none; box-shadow: none !important; min-height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;
        }
        .btn-primary, .btn-success { background: linear-gradient(90deg, var(--blue), var(--purple)) !important; color: #fff !important; }
        .btn-primary:hover, .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,58,237,0.3) !important; }
        .btn-secondary, .btn-dark { background: rgba(255,255,255,0.08) !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.15) !important; }
        .btn-secondary:hover, .btn-dark:hover { background: rgba(255,255,255,0.12) !important; transform: translateY(-2px); }
        .btn-danger { background: linear-gradient(90deg, #ef4444, #b91c1c) !important; color: #fff !important; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(239,68,68,0.3) !important; }
        .alert { background: rgba(255,255,255,0.05) !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 14px !important; color: var(--text-main) !important; margin-bottom: 20px; }
        .alert-danger { background: rgba(239,68,68,0.15) !important; border-color: rgba(239,68,68,0.3) !important; color: #fecaca !important; }
        .alert-success { background: rgba(16,185,129,0.15) !important; border-color: rgba(16,185,129,0.3) !important; color: #a7f3d0 !important; }
        .search-input-group { display: flex; gap: 10px; }
        .search-input-group .form-control { flex: 1; }
        .stats-bar {
            display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; margin: 18px 0 24px; border-radius: 16px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
        }
        .stats-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(124,58,237,0.18); color: #ddd6fe; font-weight: 700; font-size: 0.85rem;
        }
        .result-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 22px;
        }
        .result-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 24px; position: relative; overflow: hidden;
        }
        .result-card::before {
            content: ""; position: absolute; inset: 0 auto auto 0; width: 100%; height: 3px; background: linear-gradient(90deg, var(--blue), var(--purple), var(--green)); opacity: 0.95;
        }
        .result-header { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .result-icon {
            width: 52px; height: 52px; background: linear-gradient(135deg, var(--blue), var(--purple)); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .result-icon i { font-size: 22px; color: #fff; }
        .result-title h3 { margin: 0; font-size: 1.15rem; font-weight: 800; color: #fff; }
        .result-title p { margin: 4px 0 0 0; color: var(--text-soft); font-size: 0.82rem; }
        .mini-badges { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 18px 0; }
        .mini-badge {
            display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;
            background: rgba(255,255,255,0.06); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.08);
        }
        .mini-badge.id { background: rgba(37,99,235,0.14); color: #bfdbfe; }
        .mini-badge.server { background: rgba(245,158,11,0.14); color: #fde68a; }
        .mini-badge.expire { background: rgba(16,185,129,0.14); color: #a7f3d0; }
        .result-details {
            display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 18px;
        }
        .detail-item { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 13px 14px; }
        .detail-label { font-size: 0.72rem; color: var(--text-soft); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .detail-value { font-size: 0.92rem; color: #fff; font-weight: 600; word-break: break-word; }
        .accordion-box { margin-top: 18px; }
        .accordion-toggle {
            width: 100%; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.10); border-radius: 12px; padding: 12px 14px; font-weight: 700; display: flex; align-items: center; justify-content: space-between;
        }
        .accordion-panel { display: none; padding-top: 16px; }
        .accordion-panel.open { display: block; }
        .button-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .button-group .btn, .button-group form { flex: 1; }
        .button-group form .btn { width: 100%; }
        .empty-state { text-align: center; padding: 40px 20px; }
        .empty-state i { font-size: 3rem; color: rgba(255,255,255,0.2); margin-bottom: 15px; }
        .empty-state h4 { color: #fff; font-weight: 700; margin-bottom: 10px; }
        .empty-state p { color: var(--text-soft); margin-bottom: 20px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .main-card { padding: 24px; }
            .header-section h1 { font-size: 1.55rem; }
            .tabs-nav, .search-input-group, .button-group, .stats-bar { flex-direction: column; }
            .tab-button, .button-group .btn, .button-group form { width: 100%; }
            .result-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="theme-orb orb-a"></div>
    <div class="theme-orb orb-b"></div>
    <div class="theme-orb orb-c"></div>

    <div class="container-wrapper">
        <div class="main-card">
            <div class="header-section">
                <div class="header-logo">
                    <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo">
                </div>
                <h1>Gerenciador MAC Premium</h1>
                <p>Agora com multi-cadastro por MAC, edição individual e exclusão direta</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="tabs-nav">
                <button type="button" class="tab-button <?php echo $active_tab === 'search' ? 'active' : ''; ?>" data-tab="search">
                    <i class="fas fa-search"></i> Buscar Cadastro
                </button>
                <button type="button" class="tab-button <?php echo $active_tab === 'create' ? 'active' : ''; ?>" data-tab="create">
                    <i class="fas fa-plus-circle"></i> Criar Novo
                </button>
            </div>

            <div id="search-tab" class="tab-content <?php echo $active_tab === 'search' ? 'active' : ''; ?>">
                <form method="post">
                    <div class="form-group">
                        <label for="mac_address"><i class="fas fa-search"></i>Buscar por MAC Address</label>
                        <div class="search-input-group">
                            <input type="text" class="form-control mac-mask" id="mac_address" name="mac_address" placeholder="Ex.: 00:11:22:33:44:55" maxlength="17" value="<?php echo htmlspecialchars($search_mac); ?>" required>
                            <button type="submit" name="search" class="btn btn-primary" style="min-width: 150px;">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>

                <?php if ($search_performed && $search_mac !== '' && empty($error)): ?>
                    <div class="stats-bar">
                        <div>
                            <div style="font-weight: 800; color: #fff; margin-bottom: 4px;">Resultado da busca</div>
                            <div style="color: var(--text-soft); font-size: 0.92rem;">MAC pesquisado: <?php echo htmlspecialchars($search_mac); ?></div>
                        </div>
                        <div class="stats-badge">
                            <i class="fas fa-layer-group"></i>
                            <?php echo (int)$totalResults; ?> cadastro(s) encontrado(s)
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($search_performed): ?>
                    <?php if (!empty($mac_data)): ?>
                        <div class="result-grid">
                            <?php foreach ($mac_data as $item): ?>
                                <?php
                                    $currentPlaylist = $item['url'] ?: ($item['dns'] . '/get.php?username=' . ($item['username'] ?? '') . '&password=' . ($item['password'] ?? '') . '&type=m3u_plus&output=ts');
                                    $panelId = (int)($item['id'] ?? 0);
                                ?>
                                <div class="result-card">
                                    <div class="result-header">
                                        <div class="result-icon">
                                            <i class="fas fa-server"></i>
                                        </div>
                                        <div class="result-title">
                                            <h3><?php echo htmlspecialchars($item['title'] ?: 'Sem nome'); ?></h3>
                                            <p>Cadastro individual pronto para editar ou deletar</p>
                                        </div>
                                    </div>

                                    <div class="mini-badges">
                                        <span class="mini-badge id"><i class="fas fa-hashtag"></i> ID <?php echo $panelId; ?></span>
                                        <span class="mini-badge server"><i class="fas fa-network-wired"></i> <?php echo htmlspecialchars($item['dns'] ?: 'Sem DNS'); ?></span>
                                        <span class="mini-badge expire"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($item['expire_date'] ?: 'Sem validade'); ?></span>
                                    </div>

                                    <div class="result-details">
                                        <div class="detail-item">
                                            <div class="detail-label">MAC Address</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($item['mac_address']); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Usuário</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($item['username'] ?: ''); ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Contato suporte vendedor</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($item['support_contact'] ?: 'Não informado'); ?></div>
                                        </div>
                                    </div>

                                    <div class="accordion-box">
                                        <button type="button" class="accordion-toggle" data-target="panel-<?php echo $panelId; ?>">
                                            <span><i class="fas fa-edit mr-2"></i>Editar este cadastro</span>
                                            <i class="fas fa-chevron-down"></i>
                                        </button>

                                        <div class="accordion-panel" id="panel-<?php echo $panelId; ?>">
                                            <form method="post">
                                                <input type="hidden" name="id" value="<?php echo $panelId; ?>">
                                                <input type="hidden" name="search_mac" value="<?php echo htmlspecialchars($search_mac); ?>">

                                                <div class="form-group">
                                                    <label for="edit_mac_<?php echo $panelId; ?>"><i class="fas fa-mobile-alt"></i>ID do Dispositivo</label>
                                                    <input type="text" class="form-control mac-mask" id="edit_mac_<?php echo $panelId; ?>" name="mac_address" maxlength="17" required value="<?php echo htmlspecialchars($item['mac_address']); ?>">
                                                </div>

                                                <div class="form-group">
                                                    <label for="edit_title_<?php echo $panelId; ?>"><i class="fas fa-user"></i>Nome do Cliente</label>
                                                    <input type="text" class="form-control" id="edit_title_<?php echo $panelId; ?>" name="title" required value="<?php echo htmlspecialchars($item['title']); ?>">
                                                </div>

                                                <div class="form-group">
                                                    <label for="edit_expire_<?php echo $panelId; ?>"><i class="fas fa-calendar-alt"></i>Data de Expiração</label>
                                                    <input type="text" class="form-control datepicker" id="edit_expire_<?php echo $panelId; ?>" name="expire_date" placeholder="YYYY-MM-DD" required value="<?php echo htmlspecialchars($item['expire_date']); ?>">
                                                </div>

                                                <div class="form-group">
                                                    <label for="edit_support_contact_<?php echo $panelId; ?>"><i class="fab fa-whatsapp"></i>Contato Suporte Vendedor</label>
                                                    <input type="text" class="form-control" id="edit_support_contact_<?php echo $panelId; ?>" name="support_contact" value="<?php echo htmlspecialchars($item['support_contact'] ?? ''); ?>" placeholder="Ex.: 5591999999999 ou +55 (91) 99999-9999">
                                                    <small class="form-text text-muted">Esse número é usado pelo QR da splash para abrir o WhatsApp do vendedor deste cliente.</small>
                                                </div>

                                                <div class="form-group">
                                                    <label for="edit_playlist_<?php echo $panelId; ?>"><i class="fas fa-link"></i>Linha M3U / Xtream Codes</label>
                                                    <textarea class="form-control" id="edit_playlist_<?php echo $panelId; ?>" name="playlist_input" rows="5" required><?php echo htmlspecialchars($currentPlaylist); ?></textarea>
                                                    <small class="form-text text-muted">O painel salva apenas DNS, usuário e senha.</small>
                                                </div>

                                                <div class="button-group">
                                                    <button type="submit" name="update_submit" class="btn btn-success">
                                                        <i class="fas fa-save"></i> Salvar Alterações
                                                    </button>
                                                    <button type="reset" class="btn btn-secondary">
                                                        <i class="fas fa-undo"></i> Limpar
                                                    </button>
                                                </div>
                                            </form>

                                            <div style="height: 10px;"></div>

                                            <form method="post" onsubmit="return confirm('Deseja realmente deletar este cadastro?');">
                                                <input type="hidden" name="id" value="<?php echo $panelId; ?>">
                                                <input type="hidden" name="search_mac" value="<?php echo htmlspecialchars($search_mac); ?>">
                                                <button type="submit" name="delete_submit" class="btn btn-danger btn-block">
                                                    <i class="fas fa-trash"></i> Deletar este cadastro
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="result-card">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h4>MAC Address não encontrado</h4>
                                <p>Nenhum cadastro foi encontrado para este MAC Address.</p>
                                <p style="color: var(--text-soft); font-size: 0.9rem;">Clique na aba "Criar Novo" para adicionar um novo cadastro.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h4>Comece a buscar</h4>
                        <p>Digite um MAC Address acima para buscar todos os cadastros existentes.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="create-tab" class="tab-content <?php echo $active_tab === 'create' ? 'active' : ''; ?>">
                <form method="post">
                    <div class="form-group">
                        <label for="create_mac"><i class="fas fa-mobile-alt"></i>ID do Dispositivo</label>
                        <input type="text" class="form-control mac-mask" id="create_mac" name="mac_address" placeholder="Ex.: 00:11:22:33:44:55" maxlength="17" required>
                    </div>

                    <div class="form-group">
                        <label for="create_title"><i class="fas fa-user"></i>Nome do Cliente</label>
                        <input type="text" class="form-control" id="create_title" name="title" placeholder="Ex.: Cliente João" required>
                    </div>

                    <div class="form-group">
                        <label for="create_expire"><i class="fas fa-calendar-alt"></i>Data de Expiração</label>
                        <input type="text" class="form-control datepicker" id="create_expire" name="expire_date" placeholder="YYYY-MM-DD" required>
                    </div>

                    <div class="form-group">
                        <label for="create_support_contact"><i class="fab fa-whatsapp"></i>Contato Suporte Vendedor</label>
                        <input type="text" class="form-control" id="create_support_contact" name="support_contact" placeholder="Ex.: 5591999999999 ou +55 (91) 99999-9999">
                        <small class="form-text text-muted">O QR da splash vai abrir este WhatsApp para o cliente que pertence a este cadastro.</small>
                    </div>

                    <div class="form-group">
                        <label for="create_playlist"><i class="fas fa-link"></i>Linha M3U / Xtream Codes</label>
                        <textarea class="form-control" id="create_playlist" name="playlist_input" rows="5" placeholder="Cole aqui a URL M3U ou a linha com username e password" required></textarea>
                        <small class="form-text text-muted">O sistema extrai DNS, usuário e senha automaticamente.</small>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="create_submit" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Criar Cadastro
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Limpar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="js/jquery.datetimepicker.js"></script>

    <script>
        function initDatePickers() {
            $('.datepicker').datetimepicker({
                timepicker: false,
                format: 'Y-m-d'
            });
        }

        function formatMACInput(input) {
            let mac = input.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase().slice(0, 12);
            input.value = mac.match(/.{1,2}/g)?.join(':') || mac;
        }

        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('mac-mask')) {
                formatMACInput(e.target);
            }
        });

        document.querySelectorAll('.tab-button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
                document.querySelectorAll('.tab-button').forEach(button => button.classList.remove('active'));
                document.getElementById(tabName + '-tab').classList.add('active');
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.accordion-toggle').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const panel = document.getElementById(targetId);
                if (panel) {
                    panel.classList.toggle('open');
                }
            });
        });

        initDatePickers();
    </script>
</body>
</html>
