<?php

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

$db = new SQLite3("./api/.ansdb.db");

// Criar tabela se não existir
$db->exec("CREATE TABLE IF NOT EXISTS theme(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100), url VARCHAR(255), logo_url VARCHAR(255) DEFAULT '', is_active INTEGER DEFAULT 0)");

// Migração leve
@$db->exec("ALTER TABLE theme ADD COLUMN logo_url VARCHAR(255) DEFAULT ''");
@$db->exec("ALTER TABLE theme ADD COLUMN is_active INTEGER DEFAULT 0");

// Função para detectar a URL base dinamicamente
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $currentPath = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
    return $protocol . $host . $currentPath;
}

// Ativar tema
if (isset($_GET["activate"])) {
    $id = (int) $_GET["activate"];
    $db->exec("UPDATE theme SET is_active = 0");
    $stmt = $db->prepare("UPDATE theme SET is_active = 1 WHERE id = :id");
    $stmt->bindValue(":id", $id, SQLITE3_INTEGER);
    $stmt->execute();
    header("Location: theme.php");
    exit;
}

// Verifica se precisa inserir temas padrão
$rows = $db->query("SELECT COUNT(*) as count FROM theme");
$row = $rows->fetchArray();
if ($row["count"] == 0) {
    $base_url = getBaseUrl();
    
    $HOSTa = $base_url . "img/red.jpg";
    $HOSTb = $base_url . "img/blue.jpg";
    $HOSTc = $base_url . "img/green.jpg";
    $HOSTa1 = $base_url . "img/g1.gif";

    $db->exec("INSERT INTO theme(name,url,logo_url,is_active) VALUES
        ('Red','$HOSTa','',1),
        ('Blue','$HOSTb','',0),
        ('Green','$HOSTc','',0),
        ('Gif Edition','$HOSTa1','',0)");
}

$res = $db->query("SELECT * FROM theme ORDER BY is_active DESC, id ASC");

include "includes/header.php";
?>

<style>
/* Animações Globais */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes glowPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
}

/* Grid de Temas Premium */
.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    padding: 25px 0;
}

.theme-card {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    height: 220px;
    background: linear-gradient(135deg, #1e1e2e 0%, #2d2d3a 100%);
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
}

.theme-card:nth-child(1) { animation-delay: 0.05s; }
.theme-card:nth-child(2) { animation-delay: 0.1s; }
.theme-card:nth-child(3) { animation-delay: 0.15s; }
.theme-card:nth-child(4) { animation-delay: 0.2s; }
.theme-card:nth-child(5) { animation-delay: 0.25s; }
.theme-card:nth-child(6) { animation-delay: 0.3s; }

.theme-card:hover {
    transform: translateY(-8px) scale(1.02);
    border-color: rgba(255,255,255,0.3);
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
}

.theme-card img.bg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.5s ease;
    filter: brightness(0.7);
}

.theme-card:hover img.bg {
    transform: scale(1.1);
    filter: brightness(0.5);
}

.theme-card .overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: #fff;
    padding: 20px;
    background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 100%);
    backdrop-filter: blur(2px);
}

.theme-card .logo {
    max-height: 45px;
    max-width: 160px;
    margin-bottom: 15px;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

.theme-name {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 12px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    letter-spacing: 0.5px;
    background: linear-gradient(135deg, #fff 0%, #e0e0e0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.theme-description {
    font-size: 12px;
    opacity: 0.8;
    margin-bottom: 20px;
    text-align: center;
}

.active-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    z-index: 10;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 25px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    letter-spacing: 0.5px;
    backdrop-filter: blur(5px);
    animation: glowPulse 2s infinite;
}

.active-badge i {
    margin-right: 5px;
    font-size: 10px;
}

/* Botões Estilizados */
.btn-action {
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.btn-action::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-action:hover::before {
    width: 300px;
    height: 300px;
}

.btn-activate {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(40,167,69,0.3);
}

.btn-activate:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40,167,69,0.4);
}

.btn-edit {
    background: linear-gradient(135deg, #007bff 0%, #00d2ff 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,123,255,0.4);
}

.btn-edit i, .btn-activate i {
    font-size: 14px;
}

/* Header Premium */
.header-premium {
    margin-bottom: 30px;
    padding: 20px 0;
    border-bottom: 2px solid rgba(255,255,255,0.1);
    background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 100%);
    border-radius: 15px;
}

.header-premium h1 {
    font-size: 32px;
    font-weight: 800;
    margin: 0;
    background: linear-gradient(135deg, #fff 0%, #a0a0ff 50%, #ffa0a0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.5px;
}

.header-premium p {
    color: rgba(255,255,255,0.7);
    margin: 10px 0 0 0;
    font-size: 14px;
}

.stats-badge {
    display: inline-flex;
    gap: 15px;
    background: rgba(255,255,255,0.1);
    padding: 8px 20px;
    border-radius: 50px;
    backdrop-filter: blur(10px);
}

.stats-badge span {
    color: #fff;
    font-size: 13px;
}

.stats-badge i {
    margin-right: 5px;
    color: #28a745;
}

/* Tooltip personalizado */
[data-tooltip] {
    position: relative;
    cursor: pointer;
}

[data-tooltip]:before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background: rgba(0,0,0,0.9);
    color: white;
    font-size: 12px;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    margin-bottom: 8px;
    font-weight: normal;
}

[data-tooltip]:hover:before {
    opacity: 1;
    transform: translateX(-50%) translateY(-5px);
}

/* Responsividade */
@media (max-width: 768px) {
    .themes-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .theme-card {
        height: 200px;
    }
    
    .btn-action {
        padding: 8px 20px;
        font-size: 12px;
    }
    
    .theme-name {
        font-size: 18px;
    }
}
</style>

<main role="main" class="col-15 pt-4 px-5">
    <div class="header-premium d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1>
                <i class="fas fa-palette mr-3"></i> 
                Gerenciar Temas
            </h1>
            <p>Personalize a aparência do seu sistema com Sua Logotipo e Background</p>
        </div>
        <div class="stats-badge mt-2 mt-sm-0">
            <span><i class="fas fa-check-circle"></i> Tema Ativo</span>
            <span><i class="fas fa-paint-brush"></i> Temas Selecionado</span>
        </div>
    </div>

    <div class="themes-grid">
        <?php 
        $total_active = 0;
        $db_active = $db->querySingle("SELECT COUNT(*) FROM theme WHERE is_active = 1");
        $total_themes = $db->querySingle("SELECT COUNT(*) FROM theme");
        
        while ($row = $res->fetchArray()): 
            if($row['is_active']) $total_active++;
        ?>
            <div class="theme-card">
                <?php if($row['is_active']): ?>
                    <span class="active-badge">
                        <i class="fas fa-check-circle"></i> ATIVO
                    </span>
                <?php endif; ?>
                
                <img class="bg" src="<?= htmlspecialchars($row['url']) ?>" onerror="this.src='img/placeholder.jpg'; this.onerror=null;">
                
                <div class="overlay">
                    <?php if(!empty($row['logo_url'])): ?>
                        <img class="logo" src="<?= htmlspecialchars($row['logo_url']) ?>" alt="Logo do Tema">
                    <?php endif; ?>
                    
                    <div class="theme-name"><?= htmlspecialchars($row['name']) ?></div>
                    <div class="theme-description">
                        <i class="fas fa-image"></i> Tema visual personalizado
                    </div>

                    <div class="d-flex gap-3">
                        <?php if(!$row['is_active']): ?>
                            <a class="btn-action btn-activate" 
                               href="theme.php?activate=<?= $row['id'] ?>"
                               data-tooltip="Ativar este tema">
                                <i class="fas fa-play"></i> Ativar Tema
                            </a>
                        <?php endif; ?>
                        
                        <a class="btn-action btn-edit" 
                           href="theme_update.php?update=<?= $row['id'] ?>"
                           data-tooltip="Editar configurações do tema">
                            <i class="fas fa-edit"></i> Editar Tema
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <!-- Rodapé informativo -->
    <div class="text-center mt-4 pt-3 border-top border-secondary">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> 
            Selecione um tema para visualizar as alterações em tempo real
        </small>
    </div>
</main>

<?php include "includes/footer.php"; ?>

<script>
$(document).ready(function() {
    // Efeito de fade-in suave para os cards
    $('.theme-card').each(function(index) {
        $(this).css('opacity', '1');
    });
    
    // Efeito de clique nos cards (opcional - para redirecionar para edição)
    $('.theme-card').on('click', function(e) {
        // Evita que o clique nos botões dispare o redirecionamento
        if(!$(e.target).closest('.btn-action').length) {
            var editLink = $(this).find('.btn-edit').attr('href');
            if(editLink) {
                window.location.href = editLink;
            }
        }
    });
    
    // Adicionar tooltips dinâmicos
    $('[data-tooltip]').each(function() {
        $(this).css('position', 'relative');
    });
    
    // Efeito de brilho no tema ativo
    $('.active-badge').each(function() {
        var parentCard = $(this).closest('.theme-card');
        parentCard.css('border', '2px solid rgba(40, 167, 69, 0.5)');
    });
});
</script>