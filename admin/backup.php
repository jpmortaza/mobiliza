<?php
// admin/backup.php

require_once '../config.php';

// Verificar se está logado
if (!verificar_login()) {
    header('Location: index.php');
    exit();
}

$type = $_GET['type'] ?? '';

try {
    switch ($type) {
        case 'database':
            // Backup do banco de dados
            $pdo = conectar_db();
            
            // Nome do arquivo
            $filename = 'backup_mobiliza_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Headers para download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            
            // Obter todas as tabelas
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            // Cabeçalho do SQL
            echo "-- Backup Mobiliza+\n";
            echo "-- Data: " . date('Y-m-d H:i:s') . "\n";
            echo "-- Versão do MySQL: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";
            echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            echo "SET time_zone = \"+00:00\";\n\n";
            
            // Para cada tabela
            foreach ($tables as $table) {
                echo "\n-- --------------------------------------------------------\n\n";
                echo "-- Estrutura da tabela `$table`\n\n";
                
                // DROP TABLE
                echo "DROP TABLE IF EXISTS `$table`;\n";
                
                // CREATE TABLE
                $result = $pdo->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch(PDO::FETCH_NUM);
                echo $row[1] . ";\n\n";
                
                // Dados da tabela
                $result = $pdo->query("SELECT * FROM `$table`");
                $numFields = $result->columnCount();
                
                if ($result->rowCount() > 0) {
                    echo "-- Dados da tabela `$table`\n\n";
                    
                    while ($row = $result->fetch(PDO::FETCH_NUM)) {
                        $values = [];
                        for ($i = 0; $i < $numFields; $i++) {
                            if ($row[$i] === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = $pdo->quote($row[$i]);
                            }
                        }
                        echo "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
                    }
                    echo "\n";
                }
            }
            
            exit();
            break;
            
        case 'files':
            // Backup de arquivos (uploads)
            $uploadDir = '../uploads/';
            
            if (!file_exists($uploadDir)) {
                throw new Exception('Diretório de uploads não encontrado');
            }
            
            // Nome do arquivo ZIP
            $filename = 'backup_arquivos_mobiliza_' . date('Y-m-d_H-i-s') . '.zip';
            $zipPath = sys_get_temp_dir() . '/' . $filename;
            
            // Criar ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Erro ao criar arquivo ZIP');
            }
            
            // Função recursiva para adicionar arquivos
            $addDirToZip = function($dir, $zipPath = '') use (&$addDirToZip, $zip) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        $filePath = $dir . '/' . $file;
                        $zipFilePath = $zipPath . $file;
                        
                        if (is_dir($filePath)) {
                            $zip->addEmptyDir($zipFilePath);
                            $addDirToZip($filePath, $zipFilePath . '/');
                        } else {
                            $zip->addFile($filePath, $zipFilePath);
                        }
                    }
                }
            };
            
            // Adicionar pasta uploads
            $addDirToZip($uploadDir, 'uploads/');
            
            $zip->close();
            
            // Enviar arquivo
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($zipPath));
            
            readfile($zipPath);
            unlink($zipPath); // Remover arquivo temporário
            
            exit();
            break;
            
        default:
            throw new Exception('Tipo de backup inválido');
    }
    
} catch (Exception $e) {
    error_log("Erro no backup: " . $e->getMessage());
    header('Location: configuracoes.php?erro=' . urlencode('Erro ao gerar backup: ' . $e->getMessage()) . '#backup');
    exit();
}
?>