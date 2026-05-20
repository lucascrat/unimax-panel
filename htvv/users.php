<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

$db = new SQLite3("./api/.ansdb.db");
$db->exec("CREATE TABLE IF NOT EXISTS ibo(id INTEGER PRIMARY KEY NOT NULL, mac_address VARCHAR(100), key VARCHAR(100), username VARCHAR(100), password VARCHAR(100), expire_date VARCHAR(100), dns VARCHAR(100), epg_url VARCHAR(100), title VARCHAR(100), url VARCHAR(100), type VARCHAR(100))");
@$db->exec("ALTER TABLE ibo ADD COLUMN support_contact VARCHAR(100)");

if (isset($_GET["delete"])) {
    $stmt = $db->prepare("DELETE FROM ibo WHERE id = :id");
    $stmt->bindValue(':id', (int)$_GET["delete"], SQLITE3_INTEGER);
    $stmt->execute();
    header("Location: users.php?tab=gerenciar");
    exit;
}

$tab = $_GET['tab'] ?? 'gerenciar';
include "includes/header.php";
?>

<style>
    /* Estilização Geral e Grid de Cards */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 35px 20px;
        padding-top: 30px;
    }

    /* O Card */
    .access-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        position: relative;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        backdrop-filter: blur(10px);
        overflow: hidden;
    }

    .access-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.08);
        border-color: #7000ff;
    }

    /* Logo no Lado Esquerdo */
    .card-header-logo {
        position: absolute;
        top: 20px;
        left: 20px;
        width: 50px;
        height: 50px;
        background: #121212;
        border-radius: 12px;
        padding: 5px;
        border: 2px solid #7000ff;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
        box-shadow: 0 4px 15px rgba(0,0,0,0.5);
    }

    .card-logo-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 8px;
    }

    /* Conteúdo do Card - Ajustado para a logo lateral */
    .card-body-custom {
        padding: 20px 20px 20px 85px;
        flex-grow: 1;
    }

    .card-title-custom {
        margin-bottom: 20px;
        text-align: left;
    }

    .card-title-custom h5 {
        margin: 0;
        color: #fff;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 1.1rem;
    }

    .card-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .info-label {
        font-size: 0.75rem;
        color: #aaa;
        text-transform: uppercase;
    }

    .info-value {
        font-size: 0.85rem;
        color: #eee;
        font-weight: 500;
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Rodapé com Botões */
    .card-footer-custom {
        padding: 15px;
        display: flex;
        gap: 10px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0 0 15px 15px;
    }

    .card-footer-custom .btn {
        flex: 1;
        font-size: 0.8rem;
        font-weight: bold;
        border-radius: 8px;
    }

    /* Responsividade para telas menores */
    @media (max-width: 768px) {
        .card-header-logo {
            width: 40px;
            height: 40px;
            top: 15px;
            left: 15px;
        }
        
        .card-body-custom {
            padding: 15px 15px 15px 70px;
        }
        
        .card-title-custom h5 {
            font-size: 0.95rem;
        }
        
        .info-value {
            font-size: 0.75rem;
            max-width: 120px;
        }
        
        .info-label {
            font-size: 0.7rem;
        }
    }
    
    /* Modal de Confirmação Estilizado */
    .confirm-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: fadeIn 0.2s ease;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .confirm-modal-content {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 20px;
        max-width: 420px;
        width: 90%;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: slideUp 0.3s ease;
        border: 1px solid rgba(112, 0, 255, 0.3);
    }
    
    .confirm-modal-header {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.2) 0%, rgba(220, 53, 69, 0.05) 100%);
        padding: 25px 25px 15px 25px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .confirm-modal-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    
    .confirm-modal-icon i {
        font-size: 32px;
        color: white;
    }
    
    .confirm-modal-header h3 {
        color: #fff;
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 8px 0;
    }
    
    .confirm-modal-header p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        margin: 0;
    }
    
    .confirm-modal-body {
        padding: 25px;
        text-align: center;
    }
    
    .confirm-item-name {
        background: rgba(112, 0, 255, 0.15);
        border: 1px solid rgba(112, 0, 255, 0.3);
        border-radius: 12px;
        padding: 12px 15px;
        margin: 15px 0 10px;
        display: inline-block;
        width: auto;
        min-width: 200px;
    }
    
    .confirm-item-name i {
        color: #7000ff;
        margin-right: 8px;
    }
    
    .confirm-item-name strong {
        color: #fff;
        font-size: 16px;
        word-break: break-word;
    }
    
    .warning-text {
        color: #ffc107;
        font-size: 13px;
        margin-top: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .warning-text i {
        font-size: 14px;
    }
    
    .confirm-modal-footer {
        padding: 20px 25px 25px 25px;
        display: flex;
        gap: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .confirm-modal-footer button {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-cancel {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    .btn-delete {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }
    
    .btn-delete:active {
        transform: translateY(0);
    }
    
    @media (max-width: 480px) {
        .confirm-modal-content {
            width: 95%;
            margin: 10px;
        }
        
        .confirm-modal-header {
            padding: 20px 20px 12px 20px;
        }
        
        .confirm-modal-icon {
            width: 60px;
            height: 60px;
        }
        
        .confirm-modal-icon i {
            font-size: 28px;
        }
        
        .confirm-modal-header h3 {
            font-size: 20px;
        }
        
        .confirm-modal-body {
            padding: 20px;
        }
        
        .confirm-item-name strong {
            font-size: 14px;
        }
        
        .confirm-modal-footer {
            padding: 15px 20px 20px 20px;
        }
        
        .confirm-modal-footer button {
            padding: 10px;
            font-size: 13px;
        }
    }
</style>

<main role="main" class="col-12">
    <div class="page-top-card mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:12px;">
            <div>
                <h2 class="mb-2"><span class="icon-bullet"><i class="fas fa-user-shield"></i></span>Central de acessos</h2>
                <p class="mb-0 text-muted"><i class="fas fa-magic mr-2"></i>Gerencie todos os seus acessos de forma visual e rápida.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex flex-wrap page-top-actions" style="gap:10px;">
                <a href="users.php?tab=criar" class="btn <?php echo $tab === 'criar' ? 'btn-success' : 'btn-secondary'; ?>">
                    <i class="fas fa-plus-circle mr-2"></i>Criar acesso
                </a>
                <a href="users.php?tab=gerenciar" class="btn <?php echo $tab === 'gerenciar' ? 'btn-success' : 'btn-secondary'; ?>">
                    <i class="fas fa-tasks mr-2"></i>Gerenciar acessos
                </a>
            </div>
        </div>
    </div>

    <?php if ($tab === 'gerenciar'): ?>
        <div class="card shadow mb-4 compact-card">
            <div class="card-header border-0 py-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:12px;">
                    <h4 class="mb-0"><i class="fas fa-database mr-2"></i>Lista de Acessos</h4>
                    <div class="input-group compact-search" style="max-width: 300px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input class="form-control" type="text" id="search" placeholder="Buscar..." />
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div id="cardsContainer" class="cards-grid">
                    <?php
                    $res = $db->query("SELECT * FROM ibo ORDER BY id DESC");
                    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                        $iid = (int)$row["id"];
                        $imac = htmlspecialchars($row["mac_address"] ?? '');
                        $rawTitle = (string)($row['title'] ?? 'Sem Nome');
                        $ititle = htmlspecialchars($rawTitle, ENT_QUOTES, 'UTF-8');
                        $iusername = htmlspecialchars($row["username"] ?: ('user-' . $iid));
                        $iexpire_date = htmlspecialchars($row["expire_date"] ?? '');
                        $idns = htmlspecialchars($row["dns"] ?: $row["url"]);
                        $search_data = strtolower($ititle . ' ' . $imac . ' ' . $iusername);
                    ?>
                        <div class="access-card" data-search="<?php echo $search_data; ?>" data-id="<?php echo $iid; ?>" data-title="<?php echo htmlspecialchars($rawTitle, ENT_QUOTES, "UTF-8"); ?>">
                            <div class="card-header-logo">
                                <img src="img/logo.png" alt="Logo" class="card-logo-img" onerror="this.src='https://via.placeholder.com/50'">
                            </div>

                            <div class="card-body-custom">
                                <div class="card-title-custom">
                                    <h5><?php echo $ititle; ?></h5>
                                    <span class="badge badge-dark">ID: #<?php echo $iid; ?></span>
                                </div>

                                <div class="card-info-row">
                                    <span class="info-label"><i class="fas fa-mobile-alt mr-1"></i> MAC:</span>
                                    <span class="info-value"><?php echo $imac; ?></span>
                                </div>
                                <div class="card-info-row">
                                    <span class="info-label"><i class="fas fa-user mr-1"></i> Usuário:</span>
                                    <span class="info-value"><?php echo $iusername; ?></span>
                                </div>
                                <div class="card-info-row">
                                    <span class="info-label"><i class="fas fa-calendar-check mr-1"></i> Expira:</span>
                                    <span class="info-value text-success"><?php echo $iexpire_date; ?></span>
                                </div>
                                <div class="card-info-row" style="border:none">
                                    <span class="info-label"><i class="fas fa-server mr-1"></i> DNS:</span>
                                    <span class="info-value text-muted"><?php echo $idns; ?></span>
                                </div>
                            </div>

                            <div class="card-footer-custom">
                                <a class="btn btn-warning" title="Editar" href="users_update.php?update=<?php echo $iid; ?>">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <button class="btn btn-danger delete-btn" type="button" data-id="<?php echo $iid; ?>" data-title="<?php echo htmlspecialchars($rawTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fas fa-trash-alt"></i> Excluir
                                </button>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'criar'): ?>
        <div class="card shadow mb-4 p-4 text-center">
             <h4>Redirecionando para formulário...</h4>
             <a href="users_create.php" class="btn btn-success mt-3">Clique aqui para abrir</a>
        </div>
    <?php endif; ?>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Filtro de Busca Dinâmico
    $("#search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".access-card").each(function() {
            var searchText = $(this).data("search");
            if(searchText && searchText.indexOf(value) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Função para criar modal de confirmação estilizado
    function showConfirmModal(id, title) {
        // Remove qualquer modal existente
        $('.confirm-modal').remove();
        
        // Cria o modal com design moderno
        var modalHTML = `
            <div class="confirm-modal">
                <div class="confirm-modal-content">
                    <div class="confirm-modal-header">
                        <div class="confirm-modal-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Confirmar Exclusão</h3>
                        <p>Esta ação não poderá ser desfeita</p>
                    </div>
                    <div class="confirm-modal-body">
                        <p style="color: rgba(255,255,255,0.8); margin: 0;">
                            Você está prestes a excluir o acesso:
                        </p>
                        <div class="confirm-item-name">
                            <i class="fas fa-tv"></i>
                            <strong>${escapeHtml(title)}</strong>
                        </div>
                        <div class="warning-text">
                            <i class="fas fa-clock"></i>
                            <span>Todos os dados serão removidos permanentemente</span>
                        </div>
                    </div>
                    <div class="confirm-modal-footer">
                        <button class="btn-cancel" onclick="closeConfirmModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn-delete" onclick="confirmDeleteItem(${id})">
                            <i class="fas fa-trash-alt"></i> Sim, excluir
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        // Adicionar evento de tecla ESC para fechar
        $(document).on('keydown.confirmModal', function(e) {
            if (e.key === 'Escape') {
                closeConfirmModal();
            }
        });
    }
    
    // Função para escapar HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Função para fechar o modal
    window.closeConfirmModal = function() {
        $('.confirm-modal').remove();
        $(document).off('keydown.confirmModal');
    };
    
    // Função para confirmar e executar a exclusão
    window.confirmDeleteItem = function(id) {
        window.location.href = './users.php?tab=gerenciar&delete=' + id;
    };
    
    // Evento de clique no botão de excluir
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data('id');
        var title = $(this).data('title');
        showConfirmModal(id, title);
    });
    
    // Fechar modal ao clicar no overlay
    $(document).on('click', '.confirm-modal', function(e) {
        if (e.target === this) {
            closeConfirmModal();
        }
    });
});
</script>

<?php include "includes/footer.php"; ?>
</body>
</html>