<?php
session_start();
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(E_ALL);

if (!isset($_SESSION["ansnscript_admin"])) {
    header("location:login.php");
    exit;
}

$dbans = new SQLite3("./api/.ansdb.db");
require_once __DIR__ . '/app_connection_helper.php';
app_connection_ensure_schema($dbans);
$dbans->exec("CREATE TABLE IF NOT EXISTS ibo(id INTEGER PRIMARY KEY NOT NULL,mac_address VARCHAR(100),key VARCHAR(100),username VARCHAR(100),password VARCHAR(100),expire_date VARCHAR(100),dns VARCHAR(100),epg_url VARCHAR(100),title VARCHAR(100),url VARCHAR(100), type VARCHAR(100))");
$dbans->exec("CREATE TABLE IF NOT EXISTS playlist(id INTEGER PRIMARY KEY NOT NULL,mac_address VARCHAR(100),url VARCHAR(100),name VARCHAR(100))");
$dbans->exec("CREATE TABLE IF NOT EXISTS theme(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100), url VARCHAR(100))");

$mac_count_row = $dbans->query("SELECT COUNT(*) as count FROM ibo")->fetchArray(SQLITE3_ASSOC);
$mac_count = $mac_count_row['count'] ?? 0;

$dbpans = new SQLite3("./api/.anspanel.db");
$resans = $dbpans->query("SELECT * FROM USERS WHERE ID='1'");
$rowans = $resans ? $resans->fetchArray(SQLITE3_ASSOC) : [];
$nameans = $rowans['NAME'] ?? 'Painel UniTV';
$logoans = $rowans['LOGO'] ?? '';
$logoSrc = !empty($logoans) ? $logoans : 'img/logo.png';
$currentPage = basename($_SERVER['PHP_SELF']);

function navItem($href, $icon, $label, $currentPage) {
    $active = $currentPage === basename($href) ? ' active' : '';
    return '<li class="nav-item"><a class="nav-link'.$active.'" href="'.$href.'"><i class="'.$icon.'"></i><span>'.$label.'</span></a></li>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="google" content="notranslate">
    <title><?php echo htmlspecialchars($nameans); ?> - Painel UniMax</title>
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
            --sidebar-width: 286px;
            --topbar-height: 78px;
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
        .theme-orb.orb-d { width: 54px; height: 54px; top: 40px; left: 15%; background: radial-gradient(circle, rgba(239,68,68,0.95), rgba(87,11,34,0.62)); }
        .theme-orb.orb-e { width: 34px; height: 34px; top: 166px; left: 10%; background: radial-gradient(circle, rgba(239,68,68,0.95), rgba(87,11,34,0.62)); }
        a { color: inherit; }
        #wrapper { display: flex; min-height: 100vh; position: relative; z-index: 1; }
        .sidebar {
            width: var(--sidebar-width); min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 1100; padding-top: 16px;
            background: linear-gradient(180deg, rgba(2,8,35,0.96) 0%, rgba(3,9,38,0.96) 100%);
            border-right: 1px solid var(--border); backdrop-filter: blur(14px); overflow-y: auto; overflow-x: hidden; box-shadow: 16px 0 45px rgba(0,0,0,0.32);
        }
        .sidebar-brand {
            min-height: 132px; margin: 0 18px 18px; border-radius: 28px; background: linear-gradient(160deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.07); display: flex; flex-direction: column; gap: 10px; align-items: center; justify-content: center; text-decoration: none;
        }
        .sidebar-brand img { max-width: 78px; max-height: 78px; border-radius: 22px; background: rgba(255,255,255,0.94); padding: 8px; }
        .brand-copy { text-align: center; line-height: 1.2; }
        .brand-copy strong { display: block; font-size: 1rem; font-weight: 800; color: #fff; }
        .brand-copy span { color: var(--text-soft); font-size: 0.79rem; }
        .sidebar .nav-item { margin: 0 14px 9px; }
        .sidebar .nav-link { border-radius: 16px; min-height: 52px; display: flex; align-items: center; font-size: 0.88rem; font-weight: 700; color: var(--text-main); padding: 10px 14px; background: rgba(255,255,255,0.022); border: 1px solid rgba(255,255,255,0.05); transition: all .22s ease; }
        .sidebar .nav-link i { width: 22px; margin-right: 12px; color: #ff8a00; text-align: center; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { transform: translateX(4px); background: linear-gradient(90deg, rgba(37,99,235,0.18), rgba(124,58,237,0.18)); border-color: rgba(96,165,250,0.40); color: #fff; }
        .sidebar-divider { border-top: 1px solid rgba(255,255,255,0.08); margin: 14px 18px; }
        .collapse-inner { background: rgba(13, 24, 66, 0.95) !important; border: 1px solid rgba(255,255,255,0.07); border-radius: 18px; padding: 8px; }
        .collapse-item { border-radius: 12px; color: var(--text-main) !important; font-weight: 600; }
        .collapse-item:hover { background: rgba(124,58,237,0.14); }
        #content-wrapper { width: 100%; margin-left: var(--sidebar-width); min-height: 100vh; background: transparent !important; }
        .topbar { min-height: var(--topbar-height); background: rgba(5, 12, 38, 0.78) !important; backdrop-filter: blur(14px); border-bottom: 1px solid rgba(255,255,255,0.08); padding-left: 22px; padding-right: 22px; }
        .topbar .title-box { display: flex; align-items: center; gap: 14px; }
        .mini-logo { width: 46px; height: 46px; border-radius: 16px; object-fit: cover; background: rgba(255,255,255,0.94); padding: 5px; }
        .topbar h5 { color: #fff !important; font-size: 0.95rem; font-weight: 800; margin: 0; line-height: 1.2; }
        .topbar small { color: var(--text-soft) !important; }
        .topbar .badge-danger { background: linear-gradient(90deg, #ef4444, #b91c1c); border: 0; color: #fff; padding: 9px 12px; border-radius: 999px; }
        .topbar-divider { border-right: 1px solid rgba(255,255,255,0.08); }
        main[role="main"], .container-fluid { position: relative; z-index: 1; padding: 24px !important; }
        .card, .modal-content, .table-responsive, .bg-white, .input-group-text, .dropdown-menu, .alert, .pagination .page-link {
            background: var(--card-bg) !important; color: var(--text-main) !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 22px !important; box-shadow: 0 24px 50px rgba(0,0,0,0.18); backdrop-filter: blur(12px);
        }
        .modal-header, .modal-footer { border-color: rgba(255,255,255,0.08) !important; }
        .modal { z-index: 3000 !important; }
        .modal-backdrop { z-index: 2990 !important; }
        .modal-dialog { position: relative; z-index: 3001; pointer-events: none; }
        .modal-content, .modal-header, .modal-body, .modal-footer { pointer-events: auto; }
        .modal.show { display: block; }
        body.modal-open { overflow: hidden; padding-right: 0 !important; }
        #wrapper, #content-wrapper, .sidebar, .topbar, main[role="main"] { isolation: auto; }
        .table { color: var(--text-main); margin-bottom: 0; }
        .table thead th { border-top: 0; border-bottom: 1px solid rgba(255,255,255,0.08); color: #cad3f6 !important; font-size: 0.82rem; letter-spacing: 0.05em; text-transform: uppercase; white-space: nowrap; }
        .table td, .table th { border-top-color: rgba(255,255,255,0.06) !important; vertical-align: middle; }
        .table-striped tbody tr:nth-of-type(odd) { background: rgba(255,255,255,0.018); }
        .table tbody tr:hover { background: rgba(124,58,237,0.08); }
        .form-control, .custom-select, textarea, select { background: rgba(255,255,255,0.04) !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.10) !important; border-radius: 14px !important; min-height: 46px; box-shadow: none !important; }
        .form-control::placeholder { color: #9ba8d0; }
        .form-control:focus, .custom-select:focus, textarea:focus, select:focus { border-color: rgba(96,165,250,0.60) !important; box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.15) !important; }
        .btn { border-radius: 14px; font-weight: 700; border: none; box-shadow: none !important; }
        .btn-primary, .btn-success, .badge-primary, .bg-primary, .progress-bar { background: linear-gradient(90deg, var(--blue), var(--purple)) !important; color: #fff !important; }
        .btn-danger, .badge-danger { background: linear-gradient(90deg, #ef4444, #b91c1c) !important; color: #fff !important; }
        .btn-warning { background: linear-gradient(90deg, #f59e0b, #fb7185) !important; color: #fff !important; }
        .btn-secondary, .btn-dark, .badge-secondary { background: rgba(255,255,255,0.08) !important; color: #fff !important; }
        h1, h2, h3, h4, h5, h6, .text-primary, .text-gray-800 { color: #f8fbff !important; }
        .text-muted, .text-secondary, .small, .text-gray-600, .text-gray-500, label { color: var(--text-soft) !important; }
        .sticky-footer { background: transparent !important; color: var(--text-soft); padding: 20px 0 28px; }
        .scroll-to-top { background: linear-gradient(90deg, var(--blue), var(--purple)) !important; }
        .page-top-card { border-radius: 24px; padding: 22px; background: linear-gradient(135deg, rgba(37,99,235,0.18), rgba(124,58,237,0.18)); border: 1px solid rgba(255,255,255,0.08); }
        .icon-bullet { width: 42px; height: 42px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; background: linear-gradient(135deg, rgba(37,99,235,0.25), rgba(124,58,237,0.25)); color: #fff; }
        .step-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.06); color: #dfe7ff; font-size: 0.88rem; font-weight: 700; }
        .page-top-card h2 { font-size: 2.1rem; }
        .page-top-card p { font-size: 1rem; }
        .card-header h4 { font-size: 1.8rem; }
        .card-header p { font-size: 1rem; }
        .input-group-text { min-width: 52px; justify-content: center; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table td, .table th { font-size: 0.92rem; padding: 0.78rem 0.7rem; }
        .table .btn { font-size: 0.78rem; padding: 0.42rem 0.7rem; white-space: nowrap; }
        .compact-card { overflow: hidden; }
        .compact-search .form-control { font-size: 0.95rem; }
        .compact-search .input-group-text { border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
        .compact-search .form-control { border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; }

        .manage-table { table-layout: auto; width: 100%; }
        .manage-table th:nth-child(6), .manage-table td:nth-child(6) { min-width: 160px; max-width: 220px; }
        .manage-table th:nth-child(2), .manage-table td:nth-child(2) { min-width: 140px; }
        .manage-table th:nth-child(3), .manage-table td:nth-child(3) { min-width: 100px; }
        .manage-table th:nth-child(4), .manage-table td:nth-child(4) { min-width: 100px; }
        .manage-table th:nth-child(5), .manage-table td:nth-child(5) { min-width: 110px; }
        
        /* Ajuste da coluna de ações para não cortar */
        .manage-table .actions-col, .manage-table .actions-cell { 
            min-width: 140px; 
            width: 140px; 
            position: sticky; 
            right: 0; 
            z-index: 10; 
            box-shadow: -8px 0 12px -6px rgba(0,0,0,0.5);
        }
        .manage-table thead .actions-col { 
            background: #0a1236 !important; 
            z-index: 11; 
        }
        .manage-table tbody .actions-cell { 
            background: #071030 !important; 
        }
        
        .manage-table td:nth-child(6) { white-space: normal; word-break: break-all; line-height: 1.2; font-size: 0.85rem; }
        .table-actions { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .action-btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            width: 36px; 
            height: 36px; 
            border-radius: 10px !important;
            padding: 0 !important;
        }
        .action-btn i { font-size: 1rem; }
        .action-btn span { display: none; }

        /* Indicador de scroll para mobile */
        .table-responsive {
            position: relative;
            border-radius: 15px !important;
            overflow-x: auto;
        }
        .table-responsive::-webkit-scrollbar { height: 6px; }
        .table-responsive::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

        /* ESTILOS PARA CARDS RESPONSIVOS */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            width: 100%;
        }
        
        .access-card {
            border-radius: 18px;
            background: var(--card-bg) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            overflow: hidden;
            box-shadow: 0 24px 50px rgba(0,0,0,0.18);
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }
        
        .access-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 60px rgba(0,0,0,0.25);
            border-color: rgba(96,165,250,0.40) !important;
        }
        
        .card-header-mobile {
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37,99,235,0.15), rgba(124,58,237,0.15));
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        
        .card-logo {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            object-fit: cover;
            background: rgba(255,255,255,0.94);
            padding: 6px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        
        .card-body-mobile {
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .card-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .card-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #a9b3d4;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .card-value {
            font-size: 0.95rem;
            color: #eef2ff;
            word-break: break-word;
            line-height: 1.3;
        }
        
        .card-dns {
            font-size: 0.85rem;
            color: #60a5fa;
            font-family: 'Courier New', monospace;
        }
        
        .card-footer-mobile {
            padding: 14px 18px;
            display: flex;
            gap: 10px;
            border-top: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.02);
        }
        
        .action-btn-card {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            padding: 0.5rem 0.8rem !important;
            border-radius: 12px !important;
        }
        
        .action-btn-card i {
            margin-right: 6px;
        }

        .manage-table .btn i { margin-right: 0; }
        .step-chip { font-size: 0.82rem; padding: 7px 11px; }
        .page-top-actions .btn { font-size: 0.88rem; padding: 0.55rem 0.9rem; }
        @media (max-width: 1200px) {
            .page-top-card h2 { font-size: 1.8rem; }
            .card-header h4 { font-size: 1.45rem; }
            .table td, .table th { font-size: 0.86rem; padding: 0.7rem 0.58rem; }
            .table .btn { font-size: 0.74rem; padding: 0.38rem 0.58rem; }
        }
        @media (max-width: 991px) {
            main[role="main"], .container-fluid { padding: 16px !important; }
            .topbar { padding-left: 14px; padding-right: 14px; }
            .page-top-card { padding: 18px; }
            .page-top-card h2 { font-size: 1.55rem; }
            .page-top-card p, .card-header p { font-size: 0.92rem; }
            .card-header h4 { font-size: 1.22rem; }
            .table td, .table th { font-size: 0.8rem; padding: 0.62rem 0.5rem; }
            .table .btn { font-size: 0.7rem; padding: 0.34rem 0.5rem; }
            .sidebar-brand { min-height: 112px; }
            .sidebar-brand img { max-width: 64px; max-height: 64px; }
            .sidebar .nav-link { font-size: 0.84rem; min-height: 48px; }
            .topbar h5 { font-size: 0.88rem; }
            .mini-logo { width: 40px; height: 40px; }
        }
        @media (max-width: 575px) {
            .page-top-card { padding: 15px; border-radius: 18px; }
            .page-top-card h2 { font-size: 1.28rem; }
            .card-header h4 { font-size: 1.05rem; }
            .page-top-actions { width: 100%; flex-direction: column; }
            .page-top-actions .btn { width: 100%; justify-content: center; }
            .step-chip { width: 100%; justify-content: flex-start; }
            .table td, .table th { font-size: 0.75rem; padding: 0.5rem 0.4rem; }
            
            /* No mobile, removemos o sticky para evitar bugs visuais em telas muito pequenas se necessário, 
               ou ajustamos a largura */
            .manage-table .actions-col, .manage-table .actions-cell { 
                min-width: 100px; 
                width: 100px; 
            }
            .action-btn { width: 32px; height: 32px; }
            .action-btn i { font-size: 0.9rem; }
            
            /* Ajustes para cards em mobile muito pequeno */
            .card-logo { width: 50px; height: 50px; }
            .card-body-mobile { padding: 14px; gap: 10px; }
            .card-row { gap: 3px; }
            .card-label { font-size: 0.75rem; }
            .card-value { font-size: 0.9rem; }
            .card-footer-mobile { padding: 12px 14px; gap: 8px; }
            .action-btn-card { font-size: 0.8rem; padding: 0.4rem 0.6rem !important; }
        }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); transition: transform .25s ease; }
            .sidebar.mobile-open { transform: translateX(0); }
            #content-wrapper { margin-left: 0; }
            .topbar .title-box small { display: none; }
        }
    </style>
</head>
<body id="page-top">
<div class="theme-orb orb-a"></div>
<div class="theme-orb orb-b"></div>
<div class="theme-orb orb-c"></div>
<div class="theme-orb orb-d"></div>
<div class="theme-orb orb-e"></div>
<div id="wrapper">
    <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="users.php">
            <div class="sidebar-brand-icon text-center">
                <img class="img-profile" src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo">
                <div class="brand-copy mt-2">
                    <strong><?php echo htmlspecialchars($nameans); ?></strong>
                    <span>Admin Dashboard</span>
                </div>
            </div>
        </a>

        <hr class="sidebar-divider my-0">

        <?php echo navItem('users.php', 'fas fa-user-cog', 'Gerenciar Acessos ('.$mac_count.')', $currentPage); ?>
        <?php echo navItem('note.php', 'fas fa-bullhorn', 'Notificações & QRcode', $currentPage); ?>
        <?php echo navItem('theme.php', 'fas fa-paint-brush', 'Logotipo & Background', $currentPage); ?>
        <?php echo navItem('app_connection.php', 'fas fa-lock', 'Conexão Aplicativo', $currentPage); ?>
        <?php echo navItem('splash_slider.php', 'fas fa-images', 'Tela de Carregamento', $currentPage); ?>
        <?php echo navItem('update.php', 'fas fa-sync-alt', 'Atualização APK', $currentPage); ?>
        <?php echo navItem('snoop.php', 'fas fa-eye', 'Snoop', $currentPage); ?>
        <?php echo navItem('profile.php', 'fas fa-user', 'Perfil', $currentPage); ?>

        <li class="nav-item mt-2 mb-4">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </li>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand topbar mb-4 static-top">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3 text-light">
                    <i class="fa fa-bars"></i>
                </button>

                <div class="title-box">
                    <img class="mini-logo" src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo pequeno">
                    <div>
                        <h5 class="m-0"><?php echo htmlspecialchars($nameans); ?><br><small>Painel UniTV</small></h5>
                    </div>
                </div>

                <ul class="navbar-nav ml-auto align-items-center">
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="logout.php">
                            <span class="badge badge-danger">Sair</span>
                            <i class="fas fa-sign-out-alt fa-sm fa-fw ml-2 text-light"></i>
                        </a>
                    </li>
                </ul>
            </nav>
