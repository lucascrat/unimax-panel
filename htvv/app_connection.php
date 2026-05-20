<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/app_connection_helper.php';

$db = new SQLite3('./api/.ansdb.db');
app_connection_ensure_schema($db);

$message = '';
$error = '';

$config = app_connection_get($db);

$panelUrl = isset($config['panel_url']) ? (string)$config['panel_url'] : '';
$aesSecret = isset($config['aes_secret']) ? (string)$config['aes_secret'] : '';
$packageName = isset($config['package_name']) ? (string)$config['package_name'] : '';
$panelUrlAes = isset($config['panel_url_aes']) ? (string)$config['panel_url_aes'] : '';
$appKey = isset($config['app_key']) ? (string)$config['app_key'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $panelUrl = isset($_POST['panel_url']) ? trim((string)$_POST['panel_url']) : '';
    $aesSecret = isset($_POST['aes_secret']) ? trim((string)$_POST['aes_secret']) : '';
    $packageName = isset($_POST['package_name']) ? trim((string)$_POST['package_name']) : '';
    $appKey = isset($_POST['app_key']) ? trim((string)$_POST['app_key']) : '';
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

    if ($action === 'generate') {
        if ($panelUrl === '') {
            $error = 'Informe a URL do painel.';
        } elseif ($packageName === '') {
            $error = 'Informe o package name permitido.';
        } else {
            if ($appKey === '') { $appKey = bin2hex(random_bytes(12)); }
            $aesSecret = bin2hex(random_bytes(16));
            $panelUrlAes = app_connection_encrypt_url($panelUrl, $aesSecret);
            $message = 'Nova chave AES gerada com sucesso.';
        }
    } elseif ($action === 'save') {
        if ($panelUrl === '') {
            $error = 'Informe a URL do painel.';
        } elseif ($aesSecret === '') {
            $error = 'Informe a AES do app.';
        } elseif ($packageName === '') {
            $error = 'Informe o package name permitido.';
        } else {
            if ($appKey === '') { $appKey = bin2hex(random_bytes(12)); }
            $saved = app_connection_save($db, $panelUrl, $aesSecret, $packageName, $appKey);
            if ($saved) {
                $config = app_connection_get($db);
                $panelUrl = isset($config['panel_url']) ? (string)$config['panel_url'] : $panelUrl;
                $aesSecret = isset($config['aes_secret']) ? (string)$config['aes_secret'] : $aesSecret;
                $packageName = isset($config['package_name']) ? (string)$config['package_name'] : $packageName;
                $panelUrlAes = isset($config['panel_url_aes']) ? (string)$config['panel_url_aes'] : app_connection_encrypt_url($panelUrl, $aesSecret);
                $appKey = isset($config['app_key']) ? (string)$config['app_key'] : $appKey;
                $message = 'Configuração salva com sucesso.';
            } else {
                $error = 'Não foi possível salvar a configuração.';
            }
        }
    }
}
?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <div class="card border-0 shadow-lg" style="border-radius:18px; overflow:hidden; background:linear-gradient(135deg,#081229 0%,#091a4b 55%,#170d45 100%);">
                <div class="card-body p-4 p-md-5 text-white">
                    <h2 class="fw-bold mb-3">Configuração simples do app</h2>

                    <ol class="mb-4" style="opacity:.9; line-height:1.9; padding-left:18px;">
                        <li>Cole a URL do painel no campo abaixo.</li>
                        <li>Clique em <strong>Gerar AES</strong>.</li>
                        <li>Clique em <strong>Salvar configuração</strong>.</li>
                        <li>No app, procure <strong>SUA_PANEL_URL_AES</strong>, <strong>SUA_AES</strong>, <strong>SUA_PACKAGE</strong> e <strong>SUA_APP_KEY</strong>.</li>
                    </ol>

                    <?php if ($message !== ''): ?>
                        <div class="alert alert-success border-0 mb-4" style="border-radius:14px;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger border-0 mb-4" style="border-radius:14px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-white">URL do painel</label>
                            <input type="text" name="panel_url" class="form-control form-control-lg" value="<?php echo htmlspecialchars($panelUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://testes.painelmaster.lol/htvv/api/" style="border-radius:14px; background:rgba(255,255,255,.08); color:#fff; border:1px solid rgba(255,255,255,.08);">
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-white">AES do app</label>
                            <div class="d-flex gap-2 flex-column flex-md-row">
                                <input type="text" name="aes_secret" class="form-control form-control-lg" value="<?php echo htmlspecialchars($aesSecret, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Será gerada ao clicar no botão" style="border-radius:14px; background:rgba(255,255,255,.08); color:#fff; border:1px solid rgba(255,255,255,.08);">
                                <button type="submit" name="action" value="generate" class="btn btn-primary btn-lg px-4" style="border-radius:14px; min-width:160px;">Gerar AES</button>
                            </div>
                            <small class="d-block mt-2" style="opacity:.85;">Pode usar esta mesma chave no app procurando por <strong>SUA_AES</strong>.</small>
                        </div>

                        <div class="mb-4 mt-4">
                            <label class="form-label fw-semibold text-white">APP KEY de identificação</label>
                            <div class="d-flex gap-2 flex-column flex-md-row">
                                <input type="text" name="app_key" class="form-control form-control-lg" value="<?php echo htmlspecialchars($appKey, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ex.: UNIMAX_APP_KEY_2026" style="border-radius:14px; background:rgba(255,255,255,.08); color:#fff; border:1px solid rgba(255,255,255,.08);">
                                <button type="button" class="btn btn-outline-light btn-lg px-4" style="border-radius:14px; min-width:160px;" onclick="(function(){const i=document.querySelector('input[name=app_key]'); if(i&&!i.value.trim()){ i.value='KEY_'+Math.random().toString(36).slice(2,10).toUpperCase()+'_'+Date.now().toString().slice(-6);} else if(i){ i.value='KEY_'+Math.random().toString(36).slice(2,10).toUpperCase()+'_'+Date.now().toString().slice(-6);} })()">Gerar APP KEY</button>
                            </div>
                            <small class="d-block mt-2" style="opacity:.85;">O app só autentica se enviar esta mesma chave.</small>
                        </div>

                        <div class="mb-4 mt-4">
                            <label class="form-label fw-semibold text-white">Package name permitido</label>
                            <input type="text" name="package_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($packageName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="br.com.jsantiago.jshtv" style="border-radius:14px; background:rgba(255,255,255,.08); color:#fff; border:1px solid rgba(255,255,255,.08);">
                            <small class="d-block mt-2" style="opacity:.85;">Se o app enviar outro package name, a API bloqueia.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-white">O que colar no app</label>
                            <div style="border-radius:16px; background:rgba(0,0,0,.35); border:1px solid rgba(255,255,255,.06); padding:18px; color:#fff; font-weight:600; line-height:2; word-break:break-all;">
                                SUA_PANEL_URL_AES = <?php echo htmlspecialchars($panelUrlAes, ENT_QUOTES, 'UTF-8'); ?><br>
                                SUA_AES = <?php echo htmlspecialchars($aesSecret, ENT_QUOTES, 'UTF-8'); ?><br>
                                SUA_PACKAGE = <?php echo htmlspecialchars($packageName, ENT_QUOTES, 'UTF-8'); ?><br>
                                SUA_APP_KEY = <?php echo htmlspecialchars($appKey, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-start">
                            <button type="submit" name="action" value="save" class="btn btn-info btn-lg px-5 fw-bold" style="border-radius:14px;">Salvar configuração</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
