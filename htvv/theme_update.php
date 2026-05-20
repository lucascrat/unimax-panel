<?php
$db = new SQLite3("./api/.ansdb.db");

$id = $_GET['update'];

// Busca os dados atuais do tema
$stmt_fetch = $db->prepare("SELECT * FROM theme WHERE id = :id");
$stmt_fetch->bindValue(':id', $id);
$res = $stmt_fetch->execute();
$row = $res->fetchArray();

// Função para detectar a URL base do servidor
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
    if (!in_array($file['type'], $allowed)) return null;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid() . "." . $ext;

    $dir = "uploads/themes/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $path = $dir . $name;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        // Retorna a URL completa
        return getBaseUrl() . $path;
    }
    
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $bg   = uploadImage($_FILES['background']);
    $logo = uploadImage($_FILES['logo']);

    // Monta a query dinamicamente
    $sql = "UPDATE theme SET name = :name";

    if ($bg)   $sql .= ", url = :bg";
    if ($logo) $sql .= ", logo_url = :logo";

    $sql .= " WHERE id = :id";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':id', $id);

    if ($bg)   $stmt->bindValue(':bg', $bg);
    if ($logo) $stmt->bindValue(':logo', $logo);

    $stmt->execute();

    header("Location: theme.php");
    exit();
}
?>

<?php include "includes/header.php"; ?>

<div class="container mt-4">
    <h3>Editar Tema</h3>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Nome do Tema</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Novo Background (Deixe vazio para manter o atual)</label>
            <input class="form-control" type="file" name="background" accept="image/png,image/jpeg">
            <?php if ($row['url']): ?>
                <small class="text-muted">Atual: <?= $row['url'] ?></small>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Nova Logo (Deixe vazio para manter o atual)</label>
            <input class="form-control" type="file" name="logo" accept="image/png,image/jpeg">
            <?php if ($row['logo_url']): ?>
                <small class="text-muted">Atual: <?= $row['logo_url'] ?></small>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Atualizar Tema</button>
        <a href="theme.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include "includes/footer.php"; ?>