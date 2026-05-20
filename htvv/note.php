<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
error_reporting(E_ALL);

$file = "./api/note.json";
$jsondata = @file_get_contents($file);
$json = json_decode($jsondata, true);

if (!is_array($json)) {
    $json = [];
}

$title1 = isset($json["title"]) ? $json["title"] : "";
$content1 = isset($json["content"]) ? $json["content"] : "";
$statusText1 = isset($json["status_text"]) ? $json["status_text"] : "";
$supportUrl1 = isset($json["support_url"]) ? $json["support_url"] : "";
$playerNoticeEnabled1 = !empty($json["player_notice_enabled"]) ? 1 : 0;
$playerNoticeText1 = isset($json["player_notice_text"]) ? $json["player_notice_text"] : "";
$playerNoticeDuration1 = isset($json["player_notice_duration_sec"]) ? (int)$json["player_notice_duration_sec"] : 120;
$playerNoticeScope1 = isset($json["player_notice_scope"]) ? $json["player_notice_scope"] : "all";

$message = "<div class='alert alert-primary' id='flash-msg'><h4><i class='icon fa fa-check'></i> Items Updated!</h4></div>";

if (isset($_POST["text"])) {
    $enabled = isset($_POST["player_notice_enabled"]) ? 1 : 0;
    $noticeText = isset($_POST["player_notice_text"]) ? trim($_POST["player_notice_text"]) : "";
    $duration = isset($_POST["player_notice_duration_sec"]) ? max(10, (int)$_POST["player_notice_duration_sec"]) : 120;
    $scope = isset($_POST["player_notice_scope"]) ? trim($_POST["player_notice_scope"]) : "all";

    $replacementData = [
        "title" => isset($_POST["title"]) ? $_POST["title"] : "",
        "content" => isset($_POST["content"]) ? $_POST["content"] : "",
        "status_text" => isset($_POST["status_text"]) ? $_POST["status_text"] : "",
        "support_url" => isset($_POST["support_url"]) ? $_POST["support_url"] : "",
        "player_notice_enabled" => $enabled,
        "player_notice_text" => $noticeText,
        "player_notice_duration_sec" => $duration,
        "player_notice_scope" => $scope,
        "player_notice_started_at" => ($enabled && $noticeText !== "") ? time() : 0
    ];

    $newArrayData = array_replace_recursive($json, $replacementData);
    $newJsonData = json_encode($newArrayData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    file_put_contents($file, $newJsonData);

    header("Location: note.php?status=success");
    exit;
}

include "includes/header.php";
?>

<div class="container-fluid">

<?php if (isset($_GET["status"]) && $_GET["status"] === "success") { echo $message; } ?>

<h1 class="h3 mb-1 text-gray-800">Notifications</h1>

<div class="row">
    <div class="col-lg-12">
        <div class="card border-left-primary shadow h-100 card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-bullhorn"></i> Send Notifications</h6>
            </div>

            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label class="control-label" for="title"><strong>Title:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="TITLE" name="title" value="<?php echo htmlspecialchars($title1, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="content"><strong>Message:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="MESSAGE" name="content" value="<?php echo htmlspecialchars($content1, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="support_url"><strong>Link do QR de suporte:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="https://wa.me/... ou link de suporte" name="support_url" value="<?php echo htmlspecialchars($supportUrl1, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <small class="form-text text-muted">Se preencher, o app exibe um QR Code na splash para o cliente abrir o suporte.</small>
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="status_text"><strong>Texto rolando na Home:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="TEXTO DA HOME" name="status_text" value="<?php echo htmlspecialchars($statusText1, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <small class="form-text text-muted">Esse texto vai para o app nos campos status_text, home_status_text, welcome_text e marquee_text.</small>
                    </div>

                    <hr>
                    <h5 class="text-primary"><i class="fa fa-tv"></i> Aviso flutuante nos players</h5>

                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="player_notice_enabled" name="player_notice_enabled" <?php echo $playerNoticeEnabled1 ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="player_notice_enabled"><strong>Ativar aviso nos players (TV / Filmes / Séries)</strong></label>
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="player_notice_text"><strong>Texto do aviso:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="ATENÇÃO: FONTE DE FILMES E SERIES ESTÃO COM INSTABILIDADE" name="player_notice_text" value="<?php echo htmlspecialchars($playerNoticeText1, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <small class="form-text text-muted">Quando salvar com o aviso ativo, ele começa a valer na hora e para sozinho após o tempo definido.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="control-label" for="player_notice_duration_sec"><strong>Duração (segundos):</strong></label>
                            <input type="number" min="10" step="10" class="form-control" name="player_notice_duration_sec" value="<?php echo (int)$playerNoticeDuration1; ?>">
                        </div>

                        <div class="form-group col-md-4">
                            <label class="control-label" for="player_notice_scope"><strong>Onde mostrar:</strong></label>
                            <select class="form-control" name="player_notice_scope">
                               <option value="all" style="color: #000;" <?php echo $playerNoticeScope1 === 'all' ? 'selected' : ''; ?>>Todos os players</option>
    <option value="live" style="color: #000;" <?php echo $playerNoticeScope1 === 'live' ? 'selected' : ''; ?>>Somente canais</option>
    <option value="vod" style="color: #000;" <?php echo $playerNoticeScope1 === 'vod' ? 'selected' : ''; ?>>Somente filmes e séries</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="text" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function () {
    $("#flash-msg").delay(3000).fadeOut("slow");
});
</script>
</body>
</html>