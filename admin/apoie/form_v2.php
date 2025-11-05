<?php
/**
 * MOBILIZA+ V2 - Formulário de Apoio Completo
 * Criar/editar formulários de apoio com TODOS os recursos da especificação
 */

require_once '../../config.php';
verificar_autenticacao();

$pdo = conectar_db();
$id = $_GET['id'] ?? null;
$titulo_pagina = $id ? "Editar Formulário de Apoio" : "Novo Formulário de Apoio";

// Se está editando, buscar dados
$formulario = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM formularios_apoio WHERE id = ?");
    $stmt->execute([$id]);
    $formulario = $stmt->fetch();

    if (!$formulario) {
        header('Location: index.php');
        exit;
    }
}

// Processar envio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Dados básicos
        $titulo = $_POST['titulo'] ?? '';
        $slug = $_POST['slug'] ?? gerar_slug($titulo, 'formularios_apoio', 'slug', $id);
        $descricao = $_POST['descricao'] ?? '';
        $tipo = $_POST['tipo'] ?? 'apoio';
        $status = $_POST['status'] ?? 'rascunho';

        // Objetivo e resultado
        $mostrar_objetivo = isset($_POST['mostrar_objetivo']) ? 1 : 0;
        $meta_assinaturas = intval($_POST['meta_assinaturas'] ?? 1000);
        $mostrar_resultado = isset($_POST['mostrar_resultado']) ? 1 : 0;
        $mostrar_resultado_parcial = isset($_POST['mostrar_resultado_parcial']) ? 1 : 0;

        // Botões
        $permitir_apoio = isset($_POST['permitir_apoio']) ? 1 : 0;
        $permitir_rejeicao = isset($_POST['permitir_rejeicao']) ? 1 : 0;
        $permitir_abstencao = isset($_POST['permitir_abstencao']) ? 1 : 0;
        $texto_botao_apoio = $_POST['texto_botao_apoio'] ?? 'Apoiar';
        $texto_botao_rejeicao = $_POST['texto_botao_rejeicao'] ?? 'Rejeitar';
        $texto_botao_abstencao = $_POST['texto_botao_abstencao'] ?? 'Abstenção';

        // Campos do formulário
        $campo_cidade_ativo = isset($_POST['campo_cidade_ativo']) ? 1 : 0;
        $campo_email_ativo = isset($_POST['campo_email_ativo']) ? 1 : 0;
        $campo_email_obrigatorio = isset($_POST['campo_email_obrigatorio']) ? 1 : 0;
        $campo_cpf_ativo = isset($_POST['campo_cpf_ativo']) ? 1 : 0;
        $campo_cpf_obrigatorio = isset($_POST['campo_cpf_obrigatorio']) ? 1 : 0;

        // Campos extras
        $campo_extra1_nome = $_POST['campo_extra1_nome'] ?? null;
        $campo_extra1_tipo = $_POST['campo_extra1_tipo'] ?? null;
        $campo_extra1_opcoes = $_POST['campo_extra1_opcoes'] ?? null;
        $campo_extra1_obrigatorio = isset($_POST['campo_extra1_obrigatorio']) ? 1 : 0;

        $campo_extra2_nome = $_POST['campo_extra2_nome'] ?? null;
        $campo_extra2_tipo = $_POST['campo_extra2_tipo'] ?? null;
        $campo_extra2_opcoes = $_POST['campo_extra2_opcoes'] ?? null;
        $campo_extra2_obrigatorio = isset($_POST['campo_extra2_obrigatorio']) ? 1 : 0;

        $campo_extra3_nome = $_POST['campo_extra3_nome'] ?? null;
        $campo_extra3_tipo = $_POST['campo_extra3_tipo'] ?? null;
        $campo_extra3_opcoes = $_POST['campo_extra3_opcoes'] ?? null;
        $campo_extra3_obrigatorio = isset($_POST['campo_extra3_obrigatorio']) ? 1 : 0;

        // WhatsApp
        $link_grupo_whatsapp = $_POST['link_grupo_whatsapp'] ?? null;
        $grupo_evolution_id = $_POST['grupo_evolution_id'] ?? null;
        $mensagem_apos_assinatura = $_POST['mensagem_apos_assinatura'] ?? null;

        // Datas
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;

        // Upload de imagem
        $imagem_cabecalho = $formulario['imagem_cabecalho'] ?? null;
        if (isset($_FILES['imagem_cabecalho']) && $_FILES['imagem_cabecalho']['error'] === UPLOAD_ERR_OK) {
            $imagem_cabecalho = fazer_upload($_FILES['imagem_cabecalho'], 'apoie');
        }

        if ($id) {
            // Atualizar
            $sql = "
                UPDATE formularios_apoio SET
                    titulo = ?, slug = ?, descricao = ?, imagem_cabecalho = ?, tipo = ?, status = ?,
                    mostrar_objetivo = ?, meta_assinaturas = ?, mostrar_resultado = ?, mostrar_resultado_parcial = ?,
                    permitir_apoio = ?, permitir_rejeicao = ?, permitir_abstencao = ?,
                    texto_botao_apoio = ?, texto_botao_rejeicao = ?, texto_botao_abstencao = ?,
                    campo_cidade_ativo = ?, campo_email_ativo = ?, campo_email_obrigatorio = ?,
                    campo_cpf_ativo = ?, campo_cpf_obrigatorio = ?,
                    campo_extra1_nome = ?, campo_extra1_tipo = ?, campo_extra1_opcoes = ?, campo_extra1_obrigatorio = ?,
                    campo_extra2_nome = ?, campo_extra2_tipo = ?, campo_extra2_opcoes = ?, campo_extra2_obrigatorio = ?,
                    campo_extra3_nome = ?, campo_extra3_tipo = ?, campo_extra3_opcoes = ?, campo_extra3_obrigatorio = ?,
                    link_grupo_whatsapp = ?, grupo_evolution_id = ?, mensagem_apos_assinatura = ?,
                    data_inicio = ?, data_fim = ?
                WHERE id = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $titulo, $slug, $descricao, $imagem_cabecalho, $tipo, $status,
                $mostrar_objetivo, $meta_assinaturas, $mostrar_resultado, $mostrar_resultado_parcial,
                $permitir_apoio, $permitir_rejeicao, $permitir_abstencao,
                $texto_botao_apoio, $texto_botao_rejeicao, $texto_botao_abstencao,
                $campo_cidade_ativo, $campo_email_ativo, $campo_email_obrigatorio,
                $campo_cpf_ativo, $campo_cpf_obrigatorio,
                $campo_extra1_nome, $campo_extra1_tipo, $campo_extra1_opcoes, $campo_extra1_obrigatorio,
                $campo_extra2_nome, $campo_extra2_tipo, $campo_extra2_opcoes, $campo_extra2_obrigatorio,
                $campo_extra3_nome, $campo_extra3_tipo, $campo_extra3_opcoes, $campo_extra3_obrigatorio,
                $link_grupo_whatsapp, $grupo_evolution_id, $mensagem_apos_assinatura,
                $data_inicio, $data_fim,
                $id
            ]);

            registrar_log('formulario_apoio_atualizado', 'formularios_apoio', $id);
            $mensagem = 'Formulário atualizado com sucesso!';
        } else {
            // Inserir
            $sql = "
                INSERT INTO formularios_apoio (
                    titulo, slug, descricao, imagem_cabecalho, tipo, status,
                    mostrar_objetivo, meta_assinaturas, mostrar_resultado, mostrar_resultado_parcial,
                    permitir_apoio, permitir_rejeicao, permitir_abstencao,
                    texto_botao_apoio, texto_botao_rejeicao, texto_botao_abstencao,
                    campo_cidade_ativo, campo_email_ativo, campo_email_obrigatorio,
                    campo_cpf_ativo, campo_cpf_obrigatorio,
                    campo_extra1_nome, campo_extra1_tipo, campo_extra1_opcoes, campo_extra1_obrigatorio,
                    campo_extra2_nome, campo_extra2_tipo, campo_extra2_opcoes, campo_extra2_obrigatorio,
                    campo_extra3_nome, campo_extra3_tipo, campo_extra3_opcoes, campo_extra3_obrigatorio,
                    link_grupo_whatsapp, grupo_evolution_id, mensagem_apos_assinatura,
                    data_inicio, data_fim, criado_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $titulo, $slug, $descricao, $imagem_cabecalho, $tipo, $status,
                $mostrar_objetivo, $meta_assinaturas, $mostrar_resultado, $mostrar_resultado_parcial,
                $permitir_apoio, $permitir_rejeicao, $permitir_abstencao,
                $texto_botao_apoio, $texto_botao_rejeicao, $texto_botao_abstencao,
                $campo_cidade_ativo, $campo_email_ativo, $campo_email_obrigatorio,
                $campo_cpf_ativo, $campo_cpf_obrigatorio,
                $campo_extra1_nome, $campo_extra1_tipo, $campo_extra1_opcoes, $campo_extra1_obrigatorio,
                $campo_extra2_nome, $campo_extra2_tipo, $campo_extra2_opcoes, $campo_extra2_obrigatorio,
                $campo_extra3_nome, $campo_extra3_tipo, $campo_extra3_opcoes, $campo_extra3_obrigatorio,
                $link_grupo_whatsapp, $grupo_evolution_id, $mensagem_apos_assinatura,
                $data_inicio, $data_fim, $_SESSION['usuario_id']
            ]);

            $id = $pdo->lastInsertId();
            registrar_log('formulario_apoio_criado', 'formularios_apoio', $id);
            $mensagem = 'Formulário criado com sucesso!';
        }

        $pdo->commit();

        $_SESSION['mensagem_sucesso'] = $mensagem;
        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

include '../header.php';
?>

<style>
.form-section {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.form-section h5 {
    color: #667eea;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
.campo-extra-group {
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    background: #fafafa;
}
.preview-image {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    margin-top: 10px;
}
</style>

<div class="container-fluid" style="padding: 30px;">
    <div class="row mb-3">
        <div class="col">
            <h1><?php echo $titulo_pagina; ?></h1>
        </div>
        <div class="col-auto">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="formApoio">
        <div class="row">
            <!-- Coluna Principal -->
            <div class="col-md-8">
                <!-- Básico -->
                <div class="form-section">
                    <h5><i class="fas fa-info-circle"></i> Informações Básicas</h5>

                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" class="form-control" name="titulo" required
                               value="<?php echo htmlspecialchars($formulario['titulo'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug (URL amigável)</label>
                        <input type="text" class="form-control" name="slug"
                               value="<?php echo htmlspecialchars($formulario['slug'] ?? ''); ?>"
                               placeholder="deixe-vazio-para-gerar-automaticamente">
                        <small class="text-muted">Exemplo: minha-causa-importante</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="5"><?php echo htmlspecialchars($formulario['descricao'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Imagem de Cabeçalho</label>
                        <input type="file" class="form-control" name="imagem_cabecalho" accept="image/*">
                        <?php if ($formulario && $formulario['imagem_cabecalho']): ?>
                            <img src="/<?php echo $formulario['imagem_cabecalho']; ?>" class="preview-image" alt="Preview">
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo *</label>
                            <select class="form-control" name="tipo" id="tipoSelect" required>
                                <option value="apoio" <?php echo ($formulario['tipo'] ?? '') == 'apoio' ? 'selected' : ''; ?>>
                                    Apoio (apenas apoiar)
                                </option>
                                <option value="rejeicao" <?php echo ($formulario['tipo'] ?? '') == 'rejeicao' ? 'selected' : ''; ?>>
                                    Rejeição (apenas rejeitar)
                                </option>
                                <option value="apoio_ou_rejeicao" <?php echo ($formulario['tipo'] ?? '') == 'apoio_ou_rejeicao' ? 'selected' : ''; ?>>
                                    Apoio ou Rejeição (ambos)
                                </option>
                                <option value="pesquisa_opiniao" <?php echo ($formulario['tipo'] ?? '') == 'pesquisa_opiniao' ? 'selected' : ''; ?>>
                                    Pesquisa de Opinião
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-control" name="status" required>
                                <option value="rascunho" <?php echo ($formulario['status'] ?? '') == 'rascunho' ? 'selected' : ''; ?>>
                                    Rascunho
                                </option>
                                <option value="ativo" <?php echo ($formulario['status'] ?? '') == 'ativo' ? 'selected' : ''; ?>>
                                    Ativo
                                </option>
                                <option value="pausado" <?php echo ($formulario['status'] ?? '') == 'pausado' ? 'selected' : ''; ?>>
                                    Pausado
                                </option>
                                <option value="encerrado" <?php echo ($formulario['status'] ?? '') == 'encerrado' ? 'selected' : ''; ?>>
                                    Encerrado
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Objetivo e Resultado -->
                <div class="form-section">
                    <h5><i class="fas fa-bullseye"></i> Objetivo e Resultado</h5>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="mostrar_objetivo" id="mostrarObjetivo"
                               <?php echo ($formulario['mostrar_objetivo'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostrarObjetivo">
                            <strong>Mostrar meta de assinaturas</strong>
                            <small class="d-block text-muted">Exibe barra de progresso (X de Y assinaturas)</small>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta de assinaturas</label>
                        <input type="number" class="form-control" name="meta_assinaturas" min="1"
                               value="<?php echo $formulario['meta_assinaturas'] ?? 1000; ?>">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="mostrar_resultado" id="mostrarResultado"
                               <?php echo ($formulario['mostrar_resultado'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostrarResultado">
                            <strong>Mostrar resultado final</strong>
                            <small class="d-block text-muted">Mostra quantas pessoas apoiaram/rejeitaram após assinar</small>
                        </label>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="mostrar_resultado_parcial" id="mostrarParcial"
                               <?php echo ($formulario['mostrar_resultado_parcial'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostrarParcial">
                            <strong>Mostrar resultado parcial em tempo real</strong>
                            <small class="d-block text-muted">Exibe % de apoio vs rejeição antes mesmo de assinar</small>
                        </label>
                    </div>
                </div>

                <!-- Botões -->
                <div class="form-section">
                    <h5><i class="fas fa-mouse-pointer"></i> Configuração dos Botões</h5>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="permitir_apoio" id="permitirApoio"
                                       <?php echo ($formulario['permitir_apoio'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="permitirApoio">
                                    <strong>Permitir Apoio</strong>
                                </label>
                            </div>
                            <input type="text" class="form-control" name="texto_botao_apoio"
                                   value="<?php echo htmlspecialchars($formulario['texto_botao_apoio'] ?? 'Apoiar'); ?>"
                                   placeholder="Texto do botão">
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="permitir_rejeicao" id="permitirRejeicao"
                                       <?php echo ($formulario['permitir_rejeicao'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="permitirRejeicao">
                                    <strong>Permitir Rejeição</strong>
                                </label>
                            </div>
                            <input type="text" class="form-control" name="texto_botao_rejeicao"
                                   value="<?php echo htmlspecialchars($formulario['texto_botao_rejeicao'] ?? 'Rejeitar'); ?>"
                                   placeholder="Texto do botão">
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="permitir_abstencao" id="permitirAbstencao"
                                       <?php echo ($formulario['permitir_abstencao'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="permitirAbstencao">
                                    <strong>Permitir Abstenção</strong>
                                </label>
                            </div>
                            <input type="text" class="form-control" name="texto_botao_abstencao"
                                   value="<?php echo htmlspecialchars($formulario['texto_botao_abstencao'] ?? 'Abstenção'); ?>"
                                   placeholder="Texto do botão">
                        </div>
                    </div>
                </div>

                <!-- Campos do Formulário -->
                <div class="form-section">
                    <h5><i class="fas fa-list"></i> Campos do Formulário</h5>
                    <p class="text-muted">Nome e WhatsApp são sempre obrigatórios</p>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="campo_cidade_ativo"
                               <?php echo ($formulario['campo_cidade_ativo'] ?? true) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Campo Cidade</label>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="campo_email_ativo"
                                   <?php echo ($formulario['campo_email_ativo'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Campo Email</label>
                        </div>
                        <div class="col-md-6 mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="campo_email_obrigatorio"
                                   <?php echo ($formulario['campo_email_obrigatorio'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Email Obrigatório</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="campo_cpf_ativo"
                                   <?php echo ($formulario['campo_cpf_ativo'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Campo CPF</label>
                        </div>
                        <div class="col-md-6 mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="campo_cpf_obrigatorio"
                                   <?php echo ($formulario['campo_cpf_obrigatorio'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label">CPF Obrigatório</label>
                        </div>
                    </div>
                </div>

                <!-- Campos Extras -->
                <div class="form-section">
                    <h5><i class="fas fa-plus-square"></i> Campos Extras (até 3)</h5>

                    <!-- Campo Extra 1 -->
                    <div class="campo-extra-group">
                        <h6>Campo Extra 1</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label>Nome do campo</label>
                                <input type="text" class="form-control" name="campo_extra1_nome"
                                       value="<?php echo htmlspecialchars($formulario['campo_extra1_nome'] ?? ''); ?>"
                                       placeholder="Ex: Profissão">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>Tipo</label>
                                <select class="form-control" name="campo_extra1_tipo">
                                    <option value="">Selecione</option>
                                    <option value="texto" <?php echo ($formulario['campo_extra1_tipo'] ?? '') == 'texto' ? 'selected' : ''; ?>>Texto</option>
                                    <option value="select" <?php echo ($formulario['campo_extra1_tipo'] ?? '') == 'select' ? 'selected' : ''; ?>>Select (dropdown)</option>
                                    <option value="textarea" <?php echo ($formulario['campo_extra1_tipo'] ?? '') == 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                                    <option value="numero" <?php echo ($formulario['campo_extra1_tipo'] ?? '') == 'numero' ? 'selected' : ''; ?>>Número</option>
                                    <option value="email" <?php echo ($formulario['campo_extra1_tipo'] ?? '') == 'email' ? 'selected' : ''; ?>>Email</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Opções (se for select)</label>
                                <input type="text" class="form-control" name="campo_extra1_opcoes"
                                       value="<?php echo htmlspecialchars($formulario['campo_extra1_opcoes'] ?? ''); ?>"
                                       placeholder="Op1,Op2,Op3">
                            </div>
                            <div class="col-md-1 mb-2">
                                <label>Obrig?</label>
                                <input type="checkbox" class="form-check-input" name="campo_extra1_obrigatorio"
                                       <?php echo ($formulario['campo_extra1_obrigatorio'] ?? false) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Campo Extra 2 -->
                    <div class="campo-extra-group">
                        <h6>Campo Extra 2</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <input type="text" class="form-control" name="campo_extra2_nome"
                                       value="<?php echo htmlspecialchars($formulario['campo_extra2_nome'] ?? ''); ?>"
                                       placeholder="Nome do campo">
                            </div>
                            <div class="col-md-3 mb-2">
                                <select class="form-control" name="campo_extra2_tipo">
                                    <option value="">Selecione</option>
                                    <option value="texto" <?php echo ($formulario['campo_extra2_tipo'] ?? '') == 'texto' ? 'selected' : ''; ?>>Texto</option>
                                    <option value="select" <?php echo ($formulario['campo_extra2_tipo'] ?? '') == 'select' ? 'selected' : ''; ?>>Select</option>
                                    <option value="textarea" <?php echo ($formulario['campo_extra2_tipo'] ?? '') == 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                                    <option value="numero" <?php echo ($formulario['campo_extra2_tipo'] ?? '') == 'numero' ? 'selected' : ''; ?>>Número</option>
                                    <option value="email" <?php echo ($formulario['campo_extra2_tipo'] ?? '') == 'email' ? 'selected' : ''; ?>>Email</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" class="form-control" name="campo_extra2_opcoes"
                                       value="<?php echo htmlspecialchars($formulario['campo_extra2_opcoes'] ?? ''); ?>"
                                       placeholder="Opções (se for select)">
                            </div>
                            <div class="col-md-1 mb-2">
                                <input type="checkbox" class="form-check-input" name="campo_extra2_obrigatorio"
                                       <?php echo ($formulario['campo_extra2_obrigatorio'] ?? false) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Campo Extra 3 -->
                    <div class="campo-extra-group">
                        <h6>Campo Extra 3</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <input type="text" class="form-control" name="campo_extra3_nome"
                                       value="<?php echo htmlspecialchars($formulario['campo_extra3_nome'] ?? ''); ?>"
                                       placeholder="Nome do campo">
                            </div>
                            <div class="col-md-3 mb-2">
                                <select class="form-control" name="campo_extra3_tipo">
                                    <option value="">Selecione</option>
                                    <option value="texto" <?php echo ($formulario['campo_extra3_tipo'] ?? '') == 'texto' ? 'selected' : ''; ?>>Texto</option>
                                    <option value="select" <?php echo ($formulario['campo_extra3_tipo'] ?? '') == 'select' ? 'selected' : ''; ?>>Select</option>
                                    <option value="textarea" <?php echo ($formulario['campo_extra3_tipo'] ?? '') == 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                                    <option value="numero" <?php echo ($formulario['campo_extra3_tipo'] ?? '') == 'numero' ? 'selected' : ''; ?>>Número</option>
                                    <option value="email" <?php echo ($formulario['campo_extra3_tipo'] ?? '') == 'email' ? 'selected' : ''; ?>>Email</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" class="form-control" name="campo_extra3_opcoes"
                                       value="<?php echo htmlspecialchars($formulario['campo_extra3_opcoes'] ?? ''); ?>"
                                       placeholder="Opções (se for select)">
                            </div>
                            <div class="col-md-1 mb-2">
                                <input type="checkbox" class="form-check-input" name="campo_extra3_obrigatorio"
                                       <?php echo ($formulario['campo_extra3_obrigatorio'] ?? false) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="form-section">
                    <h5><i class="fab fa-whatsapp"></i> Integração WhatsApp</h5>

                    <div class="mb-3">
                        <label class="form-label">Link do Grupo WhatsApp</label>
                        <input type="url" class="form-control" name="link_grupo_whatsapp"
                               value="<?php echo htmlspecialchars($formulario['link_grupo_whatsapp'] ?? ''); ?>"
                               placeholder="https://chat.whatsapp.com/...">
                        <small class="text-muted">Será exibido após a assinatura</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ID do Grupo (Evolution API)</label>
                        <input type="text" class="form-control" name="grupo_evolution_id"
                               value="<?php echo htmlspecialchars($formulario['grupo_evolution_id'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mensagem após assinatura</label>
                        <textarea class="form-control" name="mensagem_apos_assinatura" rows="3"><?php echo htmlspecialchars($formulario['mensagem_apos_assinatura'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral -->
            <div class="col-md-4">
                <!-- Ações -->
                <div class="form-section">
                    <h5><i class="fas fa-check"></i> Ações</h5>
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> Salvar Formulário
                    </button>
                    <?php if ($formulario): ?>
                        <a href="/apoio/?slug=<?php echo $formulario['slug']; ?>" target="_blank" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-eye"></i> Ver Página Pública
                        </a>
                        <button type="button" class="btn btn-info w-100 mb-2" onclick="copiarLink()">
                            <i class="fas fa-copy"></i> Copiar Link
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Datas -->
                <div class="form-section">
                    <h5><i class="fas fa-calendar"></i> Período</h5>

                    <div class="mb-3">
                        <label class="form-label">Data de Início</label>
                        <input type="datetime-local" class="form-control" name="data_inicio"
                               value="<?php echo $formulario['data_inicio'] ? date('Y-m-d\TH:i', strtotime($formulario['data_inicio'])) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Data de Término</label>
                        <input type="datetime-local" class="form-control" name="data_fim"
                               value="<?php echo $formulario['data_fim'] ? date('Y-m-d\TH:i', strtotime($formulario['data_fim'])) : ''; ?>">
                    </div>
                </div>

                <!-- Preview -->
                <?php if ($formulario): ?>
                    <div class="form-section">
                        <h5><i class="fas fa-chart-bar"></i> Estatísticas</h5>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT
                                COUNT(*) as total,
                                SUM(CASE WHEN posicao = 'apoio' THEN 1 ELSE 0 END) as apoio,
                                SUM(CASE WHEN posicao = 'rejeicao' THEN 1 ELSE 0 END) as rejeicao,
                                SUM(CASE WHEN posicao = 'abstencao' THEN 1 ELSE 0 END) as abstencao
                            FROM assinaturas
                            WHERE formulario_apoio_id = ?
                        ");
                        $stmt->execute([$formulario['id']]);
                        $stats = $stmt->fetch();
                        ?>
                        <div class="mb-2">
                            <strong>Total:</strong> <?php echo number_format($stats['total'], 0, ',', '.'); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Apoio:</strong> <?php echo number_format($stats['apoio'], 0, ',', '.'); ?>
                            (<?php echo $stats['total'] > 0 ? round(($stats['apoio'] / $stats['total']) * 100, 1) : 0; ?>%)
                        </div>
                        <div class="mb-2">
                            <strong>Rejeição:</strong> <?php echo number_format($stats['rejeicao'], 0, ',', '.'); ?>
                            (<?php echo $stats['total'] > 0 ? round(($stats['rejeicao'] / $stats['total']) * 100, 1) : 0; ?>%)
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
function copiarLink() {
    const link = window.location.origin + '/apoio/?slug=<?php echo $formulario['slug'] ?? ''; ?>';
    navigator.clipboard.writeText(link).then(() => {
        alert('Link copiado: ' + link);
    });
}
</script>

<?php include '../footer.php'; ?>
