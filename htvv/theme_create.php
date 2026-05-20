<?php
$db = new SQLite3("./api/.ansdb.db");

// Função para detectar a URL base do servidor automaticamente
function getBaseUrl() {
    // Detecta o protocolo (http ou https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    
    // Pega o domínio (testes.painelmaster.lol)
    $host = $_SERVER['HTTP_HOST'];
    
    // Pega o caminho da pasta onde o script está rodando
    // Isso resolve o problema de estar em subpasta ou subdomínio
    $currentPath = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
    
    // Retorna a URL completa: https://testes.painelmaster.lol/caminho/da/pasta/
    return $protocol . $host . $currentPath;
}

function uploadImage($file) {
    if ($file['error'] !== 0) return null;

    $allowed = ['image/png', 'image/jpeg'];

    if (!in_array($file['type'], $allowed)) {
        die("Somente PNG/JPG permitido");
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        die("Máx 2MB");
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid() . "." . $ext;

    $dir = "uploads/themes/";

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $path = $dir . $name;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        // Retorna a URL completa concatenando a BaseUrl com o caminho relativo
        return getBaseUrl() . $path;
    }
    
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];

    $bg   = uploadImage($_FILES['background']);
    $logo = uploadImage($_FILES['logo']);

    $stmt = $db->prepare("INSERT INTO theme(name,url,logo_url,is_active) VALUES (:name,:url,:logo,0)");
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':url', $bg);
    $stmt->bindValue(':logo', $logo);
    $stmt->execute();

    header("Location: theme.php");
    exit();
}
?>

<?php include "includes/header.php"; ?>

<div class="container mt-4">
    <h3>Criar Tema</h3>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <input class="form-control" name="name" placeholder="Nome do tema" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Background</label>
            <input class="form-control" type="file" name="background" accept="image/png,image/jpeg" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Logo</label>
            <input class="form-control" type="file" name="logo" accept="image/png,image/jpeg">
        </div>

        <button type="submit" class="btn btn-success">Salvar Tema</button>
    </form>
</div>

<?php include "includes/footer.php"; ?>