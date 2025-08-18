<?php
/**
 * MOBILIZA+ - Módulo Grupos (Público)
 * Arquivo: grupos/ranking.php
 * Descrição: Exibe o ranking público dos mobilizadores.
 */
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Indicações</title>
    <link rel="shortcut icon" href="brasil.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="grupos-styles.css">
</head>
<body>
    <div class="ranking-container">
        <div class="ranking-header">
            <h1>🏆 Ranking de Mobilizadores</h1>
            <p>Veja quem mais está ajudando a expandir nossa rede!</p>
        </div>
        
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-number" id="totalCompartilhadores">-</div>
                <div class="stat-label">Mobilizadores Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalIndicacoes">-</div>
                <div class="stat-label">Total de Indicações</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="indicacoesMes">-</div>
                <div class="stat-label">Indicações este Mês</div>
            </div>
        </div>
        
        <div class="ranking-table">
            <table>
                <thead>
                    <tr>
                        <th>Posição</th>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>Total</th>
                        <th>Este Mês</th>
                        <th>Esta Semana</th>
                    </tr>
                </thead>
                <tbody id="rankingBody">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <div class="spinner"></div>
                            <p>Carregando ranking...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="links-adicionais" style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="link-ranking">← Voltar para cadastro</a>
            <a href="cadastro.php" class="link-compartilhador">📢 Quero ser Mobilizador</a>
        </div>
    </div>

    <script>
        // Carregar dados do ranking
        fetch('<?php echo SITE_URL; ?>/admin/grupos/api.php?action=ranking')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'sucesso') {
                    document.getElementById('totalCompartilhadores').textContent = data.estatisticas.total_compartilhadores;
                    document.getElementById('totalIndicacoes').textContent = data.estatisticas.total_indicacoes;
                    document.getElementById('indicacoesMes').textContent = data.estatisticas.indicacoes_mes;
                    
                    const tbody = document.getElementById('rankingBody');
                    if (data.ranking.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;">Nenhum mobilizador encontrado.</td></tr>';
                    } else {
                        tbody.innerHTML = data.ranking.map((item, index) => {
                            let medal = '';
                            if (index === 0) medal = '🥇';
                            else if (index === 1) medal = '🥈';
                            else if (index === 2) medal = '🥉';
                            
                            return `
                                <tr>
                                    <td class="posicao">${medal} ${index + 1}º</td>
                                    <td><strong>${item.nome}</strong></td>
                                    <td>${item.cidade}</td>
                                    <td><strong>${item.total_indicacoes}</strong></td>
                                    <td>${item.indicacoes_mes}</td>
                                    <td>${item.indicacoes_semana}</td>
                                </tr>
                            `;
                        }).join('');
                    }
                } else {
                    document.getElementById('rankingBody').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #dc3545;">Erro ao carregar dados.</td></tr>';
                }
            })
            .catch(error => {
                document.getElementById('rankingBody').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #dc3545;">Erro de conexão.</td></tr>';
            });
    </script>
</body>
</html>
