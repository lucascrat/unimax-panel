<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

$db = new SQLite3("./api/.ansdb.db");
require_once __DIR__ . '/includes/app_connection_helper.php';
$db->exec("CREATE TABLE IF NOT EXISTS ibo(id INTEGER PRIMARY KEY NOT NULL, mac_address VARCHAR(100), key VARCHAR(100), username VARCHAR(100), password VARCHAR(100), expire_date VARCHAR(100), dns VARCHAR(100), epg_url VARCHAR(100), title VARCHAR(100), url VARCHAR(100), type VARCHAR(100))");
@$db->exec("ALTER TABLE ibo ADD COLUMN support_contact VARCHAR(100)");

function normalize_mac($mac) {
    $clean = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $mac));
    return implode(':', str_split(substr($clean, 0, 12), 2));
}

function parse_playlist_input($input) {
    $input = trim($input);
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

$id = isset($_GET['update']) ? (int)$_GET['update'] : (int)($_POST['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM ibo WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;

if (!$row && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php?tab=gerenciar');
    exit;
}

$error = '';
if (isset($_POST['submit'])) {
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
        header('Location: users.php?tab=gerenciar');
        exit;
    }
}

$currentPlaylist = $row ? ($row['url'] ?: ($row['dns'] . '/get.php?username=' . ($row['username'] ?? '') . '&password=' . ($row['password'] ?? '') . '&type=m3u_plus&output=ts')) : '';
include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="page-top-card mb-4">
        <h2 class="mb-2"><span class="icon-bullet"><i class="fas fa-edit"></i></span>Editar acesso</h2>
        <p class="mb-0 text-muted"><i class="fas fa-pen-to-square mr-2"></i>Atualize os dados do cliente e da linha Xtream Codes.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-triangle-exclamation mr-2"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header border-0 py-4">
            <h4 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Gerenciar dados do acesso</h4>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

                <div class="form-group mb-4">
                    <label for="mac_address"><i class="fas fa-mobile-alt mr-2"></i><strong>ID do dispositivo</strong></label>
                    <input class="form-control" id="mac_address" name="mac_address" type="text" maxlength="17" required value="<?php echo htmlspecialchars($_POST['mac_address'] ?? ($row['mac_address'] ?? '')); ?>">
                </div>

                <div class="form-group mb-4">
                    <label for="title"><i class="fas fa-user mr-2"></i><strong>Nome do cliente</strong></label>
                    <input type="text" class="form-control" name="title" id="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ($row['title'] ?? '')); ?>">
                </div>

                <div class="form-group mb-4">
                    <label for="expire_date"><i class="fas fa-calendar-alt mr-2"></i><strong>Data de expiração</strong></label>
                    <input type="text" class="form-control" name="expire_date" id="datetimepicker" placeholder="YYYY-MM-DD" required value="<?php echo htmlspecialchars($_POST['expire_date'] ?? ($row['expire_date'] ?? '')); ?>">
                </div>

                <div class="form-group mb-4">
                    <label for="support_contact"><i class="fab fa-whatsapp mr-2"></i><strong>Contato Suporte Vendedor</strong></label>
                    <input type="text" class="form-control" name="support_contact" id="support_contact" placeholder="Ex: 5571999999999" value="<?php echo htmlspecialchars($_POST['support_contact'] ?? ($row['support_contact'] ?? '')); ?>">
                    <small class="form-text text-muted"><i class="fas fa-circle-info mr-1"></i>Informe apenas números com DDI e DDD. Exemplo: 5571999999999</small>
                </div>

                <div class="form-group mb-4">
                    <label for="playlist_input"><i class="fas fa-link mr-2"></i><strong>Linha M3U / Xtream Codes</strong></label>
                    <textarea class="form-control" name="playlist_input" id="playlist_input" rows="5" required><?php echo htmlspecialchars($_POST['playlist_input'] ?? $currentPlaylist); ?></textarea>
                    <small class="form-text text-muted"><i class="fas fa-circle-info mr-1"></i>O painel salva apenas os dados necessários: DNS, usuário e senha.</small>
                </div>

                <div class="d-flex flex-wrap" style="gap:10px;">
                    <button class="btn btn-success" name="submit" type="submit"><i class="fas fa-save mr-2"></i>Salvar alterações</button>
                    <a href="users.php?tab=gerenciar" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
$('#datetimepicker').datetimepicker({
    timepicker:false,
    format:'Y-m-d'
});

document.getElementById('mac_address').addEventListener('input', function() {
    let mac = this.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase().slice(0, 12);
    this.value = mac.match(/.{1,2}/g)?.join(':') || mac;
});
</script>
</body>
</html>
