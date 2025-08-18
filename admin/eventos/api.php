<?php
require_once '../../config.php';
header('Content-Type: application/json');

// Verificar autenticação
if (!verificar_login()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

$pdo = conectar_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'salvar':
            $pdo->beginTransaction();

            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $titulo = sanitizar($_POST['titulo'] ?? '');
            $slug = sanitizar($_POST['slug'] ?? '', 'slug');
            $descricao = sanitizar($_POST['descricao'] ?? '');
            $data_evento = !empty($_POST['data_evento']) ? $_POST['data_evento'] : null;
            $local_evento = sanitizar($_POST['local_evento'] ?? '');
            $link_whatsapp = sanitizar($_POST['link_whatsapp'] ?? '', 'url');
            $titulo_participantes = sanitizar($_POST['titulo_participantes'] ?? 'Palestrantes');

            // Validações
            if (empty($titulo)) {
                throw new Exception('Título é obrigatório');
            }

            if (empty($slug)) {
                $slug = gerar_slug($titulo, 'eventos', 'slug', $id);
            } else {
                // Verificar se slug já existe
                $stmt = $pdo->prepare("SELECT id FROM eventos WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $id ?: 0]);
                if ($stmt->fetch()) {
                    throw new Exception('Este slug já está em uso');
                }
            }

            /**
             * Upload da imagem principal (cabeçalho)
             * Importante: checar error === UPLOAD_ERR_OK e size > 0
             * Manter imagem atual (imagem_cabecalho_atual) quando não houver upload novo
             */
            $imagem_cabecalho = $_POST['imagem_cabecalho_atual'] ?? '';
            if (
                isset($_FILES['imagem_cabecalho']) &&
                is_array($_FILES['imagem_cabecalho']) &&
                ($_FILES['imagem_cabecalho']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK &&
                ($_FILES['imagem_cabecalho']['size'] ?? 0) > 0
            ) {
                try {
                    $imagem_cabecalho = fazer_upload($_FILES['imagem_cabecalho'], 'eventos');
                } catch (Exception $e) {
                    error_log("Erro no upload da imagem de cabeçalho: " . $e->getMessage());
                    // Se falhar o upload, preserva a atual, se houver
                    $imagem_cabecalho = $_POST['imagem_cabecalho_atual'] ?? '';
                }
            }

            if ($id) {
                // Atualizar evento
                $stmt = $pdo->prepare("
                    UPDATE eventos SET 
                        titulo = ?, slug = ?, descricao = ?, titulo_participantes = ?, 
                        data_evento = ?, local_evento = ?, link_whatsapp = ?, 
                        imagem_cabecalho = ?, data_atualizacao = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $titulo, $slug, $descricao, $titulo_participantes,
                    $data_evento, $local_evento, $link_whatsapp,
                    $imagem_cabecalho, $id
                ]);
                registrar_log('evento_atualizado', 'eventos', $id, "Evento atualizado: $titulo");
            } else {
                // Criar novo evento
                $stmt = $pdo->prepare("
                    INSERT INTO eventos 
                        (titulo, slug, descricao, titulo_participantes, data_evento, 
                         local_evento, link_whatsapp, imagem_cabecalho, criado_por, ativo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $titulo, $slug, $descricao, $titulo_participantes,
                    $data_evento, $local_evento, $link_whatsapp,
                    $imagem_cabecalho, $_SESSION['usuario_id']
                ]);
                $id = (int)$pdo->lastInsertId();
                registrar_log('evento_criado', 'eventos', $id, "Evento criado: $titulo");
            }

            /**
             * --- PROCESSAR PARTICIPANTES ---
             * Atenção ao formato de $_FILES quando o name é aninhado (participantes[index][foto])
             * O PHP monta como $_FILES['participantes']['name'][$index]['foto'] (e não ['name']['foto'][$index]).
             */
            if (isset($_POST['participantes']) && is_array($_POST['participantes'])) {
                // Apaga todos os antigos e recria ordenado
                $stmtDel = $pdo->prepare("DELETE FROM participantes_evento WHERE evento_id = ?");
                $stmtDel->execute([$id]);

                $stmtIns = $pdo->prepare("
                    INSERT INTO participantes_evento
                        (evento_id, nome, descricao, foto_url, instagram_url, linkedin_url, ordem)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $ordem = 0;
                foreach ($_POST['participantes'] as $index => $participante) {
                    // Ignorar registros sem nome
                    if (empty($participante['nome'])) {
                        continue;
                    }

                    $foto_url = '';

                    // Verifica se houve upload novo para este índice
                    $temUpload =
                        isset($_FILES['participantes']['error'][$index]['foto']) &&
                        $_FILES['participantes']['error'][$index]['foto'] === UPLOAD_ERR_OK &&
                        !empty($_FILES['participantes']['size'][$index]['foto']) &&
                        $_FILES['participantes']['size'][$index]['foto'] > 0;

                    if ($temUpload) {
                        // Normaliza para o formato aceito por fazer_upload()
                        $file_info = [
                            'name'     => $_FILES['participantes']['name'][$index]['foto'] ?? '',
                            'type'     => $_FILES['participantes']['type'][$index]['foto'] ?? '',
                            'tmp_name' => $_FILES['participantes']['tmp_name'][$index]['foto'] ?? '',
                            'error'    => $_FILES['participantes']['error'][$index]['foto'] ?? UPLOAD_ERR_NO_FILE,
                            'size'     => $_FILES['participantes']['size'][$index]['foto'] ?? 0,
                        ];

                        try {
                            $foto_url = fazer_upload($file_info, 'eventos');
                        } catch (Exception $e) {
                            error_log("Erro no upload do participante {$participante['nome']}: " . $e->getMessage());
                            $foto_url = '';
                        }
                    } elseif (!empty($participante['foto_atual'])) {
                        // Mantém a existente se não enviaram nova
                        $foto_url = $participante['foto_atual'];
                    }

                    // Inserir participante
                    $stmtIns->execute([
                        $id,
                        sanitizar($participante['nome']),
                        sanitizar($participante['descricao'] ?? ''),
                        $foto_url,
                        sanitizar($participante['instagram'] ?? '', 'url'),
                        sanitizar($participante['linkedin'] ?? '', 'url'),
                        $ordem++
                    ]);
                }
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Evento salvo com sucesso!',
                'data' => ['id' => $id, 'slug' => $slug]
            ]);
            break;

        case 'delete_evento':
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            // Soft delete
            $stmt = $pdo->prepare("UPDATE eventos SET ativo = 0 WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status' => 'sucesso',
                'success' => true,
                'message' => 'Evento excluído com sucesso!',
                'msg' => 'Evento excluído com sucesso!'
            ]);
            break;

        case 'obter':
            $id = (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ? AND ativo = 1");
            $stmt->execute([$id]);
            $evento = $stmt->fetch();

            if (!$evento) {
                throw new Exception('Evento não encontrado');
            }

            // Buscar participantes
            $stmt = $pdo->prepare("
                SELECT * FROM participantes_evento
                WHERE evento_id = ?
                ORDER BY ordem ASC
            ");
            $stmt->execute([$id]);
            $participantes = $stmt->fetchAll();

            $evento['participantes'] = $participantes;

            echo json_encode(['success' => true, 'data' => $evento]);
            break;

        case 'toggle_checkin':
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            // Buscar status atual
            $stmt = $pdo->prepare("SELECT checkin FROM inscricoes_eventos WHERE id = ?");
            $stmt->execute([$id]);
            $inscricao = $stmt->fetch();

            if (!$inscricao) {
                throw new Exception('Inscrição não encontrada');
            }

            // Alternar status
            $novo_status = $inscricao['checkin'] ? 0 : 1;
            $data_checkin = $novo_status ? date('Y-m-d H:i:s') : null;

            $stmt = $pdo->prepare("
                UPDATE inscricoes_eventos
                SET checkin = ?, data_checkin = ?
                WHERE id = ?
            ");
            $stmt->execute([$novo_status, $data_checkin, $id]);

            echo json_encode([
                'success' => true,
                'message' => $novo_status ? 'Check-in realizado!' : 'Check-in removido!',
                'checkin' => $novo_status
            ]);
            break;

        case 'marcar_presente':
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $stmt = $pdo->prepare("
                UPDATE inscricoes_eventos
                SET checkin = 1, data_checkin = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Marcado como presente!']);
            break;

        case 'excluir_inscricao':
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $stmt = $pdo->prepare("DELETE FROM inscricoes_eventos WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Inscrição excluída com sucesso!']);
            break;

        case 'exportar_csv':
            // Lógica de exportação de CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="inscricoes_' . date('Y-m-d_H-i-s') . '.csv"');

            // Buscar dados para exportação
            $stmt = $pdo->prepare("
                SELECT i.*, e.titulo as evento_titulo
                FROM inscricoes_eventos i
                JOIN eventos e ON i.evento_id = e.id
                ORDER BY i.data_inscricao DESC
            ");
            $stmt->execute();
            $inscricoes = $stmt->fetchAll();

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8

            // Cabeçalhos
            fputcsv($output, [
                'ID',
                'Evento',
                'Nome',
                'Email',
                'WhatsApp',
                'Cidade',
                'Check-in',
                'Referencia',
                'Data Inscricao',
                'Data Check-in'
            ], ';');

            // Dados
            foreach ($inscricoes as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['evento_titulo'],
                    $row['nome'],
                    $row['email'] ?? '',
                    $row['whatsapp'],
                    $row['cidade'],
                    $row['checkin'] ? 'Sim' : 'Nao',
                    $row['referencia'] ?? '',
                    formatar_data_br($row['data_inscricao']),
                    formatar_data_br($row['data_checkin'])
                ], ';');
            }

            fclose($output);
            exit();

        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erro na API de eventos: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
