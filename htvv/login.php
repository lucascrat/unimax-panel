<?php
session_start();
ob_start();
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (isset($_SESSION['ansnscript_admin'])) {
    header('Location: users.php');
    exit;
}

$db = new SQLite3('./api/.anspanel.db');
$db->exec('CREATE TABLE IF NOT EXISTS USERS(id INTEGER PRIMARY KEY, NAME TEXT, LOGO TEXT, USERNAME TEXT, PASSWORD TEXT)');
$res = $db->query("SELECT * FROM USERS WHERE ID='1'");
$row = $res ? $res->fetchArray(SQLITE3_ASSOC) : [];
$name_login = $row['NAME'] ?? 'UniTV';
$logo_login = $row['LOGO'] ?? 'img/logo.png';

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = $db->prepare("SELECT * FROM USERS WHERE ID='1' AND USERNAME = :username AND PASSWORD = :password");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $password, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($user) {
            $_SESSION['ansnscript_admin'] = true;
            header('Location: users.php');
            exit;
        }
        $login_error = 'Usuário ou senha inválidos';
    } else {
        $login_error = 'Preencha usuário e senha';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($name_login); ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="./img/logo.png" type="image/png">
    <style>
        :root {
            --blue: #2563eb;
            --purple: #7c3aed;
            --red: #ef4444;
            --text: #eef2ff;
            --muted: #a5b4d6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background:
                radial-gradient(circle at 82% 28%, rgba(239,68,68,0.38) 0, rgba(239,68,68,0.18) 18%, transparent 46%),
                radial-gradient(circle at 30% 75%, rgba(239,68,68,0.32) 0, rgba(239,68,68,0.12) 16%, transparent 34%),
                radial-gradient(circle at 48% 20%, rgba(37,99,235,0.30) 0, rgba(37,99,235,0.12) 14%, transparent 30%),
                linear-gradient(135deg, #020617 0%, #030b2a 45%, #071238 100%);
            position: relative;
        }
        body::before,
        body::after {
            content: '';
            position: absolute;
            left: -8%;
            right: -8%;
            height: 18px;
            border-radius: 999px;
            filter: blur(16px);
            pointer-events: none;
        }
        body::before {
            top: 33%;
            background: linear-gradient(90deg, rgba(255,45,162,0), rgba(96,52,255,0.95), rgba(255,45,162,0));
            box-shadow: 0 0 26px rgba(124,58,237,0.42);
        }
        body::after {
            top: 67%;
            background: linear-gradient(90deg, rgba(255,45,162,0), rgba(59,130,246,0.88), rgba(255,45,162,0));
            box-shadow: 0 0 24px rgba(59,130,246,0.34);
        }
        .login-shell {
            width: min(1180px, calc(100vw - 32px));
            min-height: 660px;
            border-radius: 34px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.22fr 0.78fr;
            background: rgba(5, 12, 38, 0.64);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 35px 90px rgba(0,0,0,0.35);
            backdrop-filter: blur(16px);
            position: relative;
            z-index: 1;
        }
        .login-art {
            position: relative;
            padding: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .planet {
            position: absolute;
            border-radius: 50%;
            filter: blur(0.15px);
        }
        .planet.big-red {
            width: 600px; height: 600px; right: -190px; top: 28px;
            background: radial-gradient(circle at 32% 35%, rgba(255,255,255,0.08), rgba(239,68,68,0.94) 0, rgba(116,14,53,0.62) 58%, rgba(10,9,35,0.15) 100%);
            opacity: 0.92;
        }
        .planet.mid-blue {
            width: 225px; height: 225px; left: 40%; top: 34px;
            background: radial-gradient(circle at 35% 35%, rgba(255,255,255,0.08), rgba(37,99,235,0.68) 0, rgba(7,19,56,0.72) 70%, transparent 100%);
        }
        .planet.mid-red {
            width: 285px; height: 285px; left: 20%; bottom: 16px;
            background: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.08), rgba(239,68,68,0.84) 0, rgba(58,9,49,0.80) 68%, transparent 100%);
        }
        .planet.small-red-a {
            width: 58px; height: 58px; left: 24%; top: 40px;
            background: radial-gradient(circle, rgba(239,68,68,0.95), rgba(87,11,34,0.62));
        }
        .planet.small-red-b {
            width: 38px; height: 38px; left: 10%; top: 160px;
            background: radial-gradient(circle, rgba(239,68,68,0.95), rgba(87,11,34,0.62));
        }
        .streak,
        .streak-two,
        .streak-three {
            position: absolute;
            left: -14%;
            right: 34%;
            height: 16px;
            border-radius: 999px;
            filter: blur(14px);
        }
        .streak {
            top: 45%;
            background: linear-gradient(90deg, rgba(255,45,162,0), rgba(96,52,255,0.98), rgba(255,45,162,0));
        }
        .streak-two {
            top: 62%;
            left: 10%;
            right: 44%;
            background: linear-gradient(90deg, rgba(255,45,162,0), rgba(78,118,255,0.92), rgba(255,45,162,0));
        }
        .streak-three {
            top: 27%;
            left: -12%;
            right: 70%;
            background: linear-gradient(90deg, rgba(255,45,162,0), rgba(239,68,68,0.86), rgba(255,45,162,0));
        }
        .art-brand {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 14px;
            margin-left: 130px;
        }
        .art-brand .logo-box {
            width: 108px;
            height: 108px;
            border-radius: 24px;
            background: rgba(255,255,255,0.96);
            box-shadow: 0 24px 50px rgba(0,0,0,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }
        .art-brand img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .art-brand h1 {
            font-size: 1.55rem;
            font-weight: 800;
            margin: 0;
        }
        .art-brand p {
            margin: 0;
            font-size: 1rem;
            color: #ffffff;
            opacity: 0.92;
        }
        .login-form-wrap {
            padding: 42px 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, rgba(7,13,37,0.82), rgba(8,16,45,0.88));
            border-left: 1px solid rgba(255,255,255,0.06);
        }
        .login-card {
            width: 100%;
            max-width: 395px;
            border-radius: 30px;
            padding: 38px 30px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
        }
        .brand-box {
            text-align: center;
            margin-bottom: 26px;
        }
        .brand-box img {
            width: 88px;
            height: 88px;
            object-fit: cover;
            border-radius: 24px;
            background: rgba(255,255,255,0.96);
            padding: 8px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.28);
        }
        .brand-box h2 {
            margin: 16px 0 6px;
            font-size: 1.35rem;
            font-weight: 800;
        }
        .brand-box p {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
        }
        .form-group label {
            font-size: 0.82rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #cfd7f8;
            letter-spacing: 0.06em;
        }
        .form-control {
            min-height: 52px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.10);
            color: #fff;
            border-radius: 15px;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.06);
            border-color: rgba(96,165,250,0.58);
            box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.16);
            color: #fff;
        }
        .form-control::placeholder { color: #9ba8d0; }
        .input-icon { position: relative; }
        .input-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ba8d0;
        }
        .input-icon .form-control { padding-left: 44px; }
        .btn-login {
            min-height: 52px;
            border: 0;
            border-radius: 15px;
            width: 100%;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: #fff;
            background: linear-gradient(90deg, var(--blue), var(--purple));
            box-shadow: 0 18px 30px rgba(37,99,235,0.26);
        }
        .btn-login:hover { filter: brightness(1.06); }
        .alert { border-radius: 14px; border: 1px solid rgba(255,255,255,0.08); }
        .login-footer { text-align: center; margin-top: 18px; color: var(--muted); font-size: 0.85rem; }
        @media (max-width: 980px) {
            .login-shell { grid-template-columns: 1fr; min-height: auto; }
            .login-art { min-height: 320px; }
            .art-brand { margin-left: 0; }
            .login-form-wrap { padding-top: 0; }
        }
        @media (max-width: 540px) {
            .login-shell { width: calc(100vw - 20px); border-radius: 22px; }
            .login-card { padding: 28px 20px; }
            .art-brand .logo-box { width: 92px; height: 92px; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <section class="login-art">
            <div class="planet big-red"></div>
            <div class="planet mid-blue"></div>
            <div class="planet mid-red"></div>
            <div class="planet small-red-a"></div>
            <div class="planet small-red-b"></div>
            <div class="streak"></div>
            <div class="streak-two"></div>
            <div class="streak-three"></div>
            <div class="art-brand">
                <div class="logo-box">
                    <img src="<?php echo htmlspecialchars($logo_login); ?>" alt="Logo">
                </div>
                <h1><?php echo htmlspecialchars($name_login); ?></h1>
                <p>Admin Dashboard</p>
            </div>
        </section>

        <section class="login-form-wrap">
            <div class="login-card">
                <div class="brand-box">
                    <img src="<?php echo htmlspecialchars($logo_login); ?>" alt="Logo do painel">
                    <h2>Entrar no painel</h2>
                    <p>Entre com sues dados Abaixo</p>
                </div>

                <?php if (!empty($login_error)) : ?>
                    <div class="alert alert-danger text-center"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label>Usuário</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" class="form-control" placeholder="Digite seu usuário">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Senha</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="Digite sua senha">
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-login mt-2">Acessar painel</button>
                </form>

                <div class="login-footer"> www.maxloja.xyz</div>
            </div>
        </section>
    </div>
</body>
</html>
