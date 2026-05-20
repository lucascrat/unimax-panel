<?php
ini_set("display_errors", 0);
error_reporting(E_ALL);

$db = new SQLite3(__DIR__ . "/.ansdb.db");
@$db->exec("ALTER TABLE ibo ADD COLUMN support_contact VARCHAR(100)");
$dbPanel = new SQLite3(__DIR__ . "/.anspanel.db");

function normalize_mac($mac) {
    $clean = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', (string)$mac));
    $clean = substr($clean, 0, 12);
    if ($clean === '') return '';
    return implode(':', str_split($clean, 2));
}

function only_digits($value) {
    return preg_replace('/\D+/', '', (string)$value);
}

function get_panel_info($dbPanel) {
    // Definimos o caminho absoluto em relação ao domínio para evitar erros de pasta
    $defaultLogo = '/htvv/img/logo.png'; 
    
    $fallback = [
        'name' => 'Painel UniTV',
        'logo' => $defaultLogo
    ];

    if (!$dbPanel) return $fallback;

    $res = $dbPanel->query("SELECT * FROM USERS WHERE ID='1'");
    $row = $res ? $res->fetchArray(SQLITE3_ASSOC) : null;

    if (!$row) return $fallback;

    // Se o banco tiver algo, tentamos usar, senão usamos o nosso default fixo
    $logo = !empty($row['LOGO']) ? $row['LOGO'] : $defaultLogo;

    // Limpeza de segurança: se o banco tiver ../ ou algo assim, removemos
    $logo = str_replace('../', '', $logo);
    
    // Se o caminho no banco não começar com / nem http, garantimos que aponte para a pasta correta
    if (!str_starts_with($logo, '/') && !str_starts_with($logo, 'http')) {
        $logo = '/htvv/' . $logo;
    }

    return [
        'name' => $row['NAME'] ?? $fallback['name'],
        'logo' => $logo,
    ];
}

function render_page($panel, $title, $message, $buttonHref = '', $buttonText = '') {
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $msgEsc = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $panelName = htmlspecialchars($panel['name'], ENT_QUOTES, 'UTF-8');
    $logo = htmlspecialchars($panel['logo'], ENT_QUOTES, 'UTF-8');
    $buttonHtml = '';
    if ($buttonHref !== '' && $buttonText !== '') {
        $buttonHtml = '<a class="action-btn" href="' . htmlspecialchars($buttonHref, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $titleEsc . '</title><style>
    :root{--bg0:#020617;--bg1:#081133;--bg2:#0b173f;--card:rgba(8,17,51,.82);--border:rgba(255,255,255,.10);--txt:#eef2ff;--soft:#b8c2e7;--purple:#7c3aed;--blue:#2563eb;--red:#ef4444;}
    *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Arial,Helvetica,sans-serif;color:var(--txt);background:radial-gradient(circle at 78% 19%, rgba(239,68,68,.30) 0, rgba(239,68,68,.12) 18%, transparent 42%),radial-gradient(circle at 28% 77%, rgba(37,99,235,.28) 0, rgba(37,99,235,.10) 15%, transparent 32%),linear-gradient(135deg,var(--bg0) 0%,var(--bg1) 42%,var(--bg2) 100%);} .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}.card{width:100%;max-width:720px;background:var(--card);border:1px solid var(--border);border-radius:24px;padding:32px;box-shadow:0 24px 50px rgba(0,0,0,.28);backdrop-filter:blur(10px);text-align:center}.logo{width:96px;height:96px;margin:0 auto 18px;background:rgba(255,255,255,.95);border-radius:22px;display:flex;align-items:center;justify-content:center;padding:10px;box-shadow:0 10px 30px rgba(124,58,237,.28)}.logo img{max-width:100%;max-height:100%;object-fit:contain}.tag{display:inline-block;padding:8px 14px;border-radius:999px;background:rgba(124,58,237,.16);border:1px solid rgba(124,58,237,.28);color:#ddd6fe;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:16px}h1{margin:0 0 14px;font-size:30px;line-height:1.18}p{margin:0;color:var(--soft);font-size:17px;line-height:1.7}.action-btn{margin-top:24px;display:inline-flex;align-items:center;justify-content:center;padding:14px 22px;border-radius:14px;text-decoration:none;font-weight:700;color:#fff;background:linear-gradient(90deg,var(--blue),var(--purple));box-shadow:0 10px 24px rgba(37,99,235,.25)}.hint{margin-top:16px;font-size:13px;opacity:.9}@media (max-width:640px){.card{padding:24px}h1{font-size:24px}p{font-size:15px}}</style></head><body><div class="wrap"><div class="card"><div class="logo"><img src="' . $logo . '" alt="Logo"></div><div class="tag">' . $panelName . '</div><h1>' . $titleEsc . '</h1><p>' . $msgEsc . '</p>' . $buttonHtml . '<div class="hint">Atendimento e ativação vinculados ao cadastro do cliente.</div></div></div></body></html>';
}

$panel = get_panel_info($dbPanel);
$mac = normalize_mac($_GET['mac'] ?? '');
if ($mac === '') {
    render_page($panel, 'Cadastro não encontrado', 'Desculpe, você ainda não tem cadastro existente no nosso aplicativo. Procure um vendedor mais próximo para ativá-lo.');
    exit;
}

$stmt = $db->prepare('SELECT mac_address, title, support_contact FROM ibo WHERE mac_address = :mac LIMIT 1');
$stmt->bindValue(':mac', $mac, SQLITE3_TEXT);
$res = $stmt->execute();
$row = $res ? $res->fetchArray(SQLITE3_ASSOC) : null;

if (!$row) {
    render_page($panel, 'Cadastro não encontrado', 'Desculpe, você ainda não tem cadastro existente no nosso aplicativo. Procure um vendedor mais próximo para ativá-lo.');
    exit;
}

$digits = only_digits($row['support_contact'] ?? '');
if ($digits === '') {
    render_page($panel, 'Contato indisponível', 'Seu cadastro foi localizado, mas o contato do vendedor ainda não foi configurado. Procure o suporte que realizou sua ativação para concluir o atendimento.');
    exit;
}

$clientName = trim((string)($row['title'] ?? 'Cliente'));
$message = 'Olá, preciso de suporte no aplicativo. Cliente: ' . $clientName . ' | ID: ' . $mac;
$waUrl = 'https://wa.me/' . rawurlencode($digits) . '?text=' . rawurlencode($message);
header('Location: ' . $waUrl, true, 302);
exit;
