<?php
/**
 * MOBILIZA+ - Módulo Grupos (Admin)
 * Arquivo: admin/grupos/index.php
 * Descrição: Painel de administração para gerenciar cadastros, grupos e mobilizadores.
 */

$titulo_pagina = 'Gestão de Grupos e Mobilizadores';
// Inclui o header padrão do admin, que já faz a verificação de login.
require_once __DIR__ . '/../header.php';

try {
    $pdo = conectar_db();
    
    // Buscar todas as cidades com grupos para os filtros
    $stmt = $pdo->query("SELECT id, cidade FROM cidades_grupos ORDER BY cidade ASC");
    $cidades_grupos = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Erro ao buscar dados para o painel de grupos: " . $e->getMessage());
    $cidades_grupos = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="margin: 0;"><?php echo $titulo_pagina; ?></h2>
        <p class="text-secondary">Gerencie os cadastros, links de grupos e mobilizadores.</p>
    </div>
</div>

<div class="stats-grid mb-4" id="statsGrid">
    <div class="stat-card">
        <div class="stat-value" id="totalCadastros">-</div>
        <div class="stat-label">Total de Cadastros</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="cadastrosHoje">-</div>
        <div class="stat-label">Cadastros Hoje</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="totalCompartilhadores">-</div>
        <div class="stat-label">Mobilizadores Aprovados</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="totalIndicacoes">-</div>
        <div class="stat-label">Total de Indicações</div>
    </div>
</div>

<!-- Navegação por Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tab-cadastros">
            <i class="fas fa-users"></i> Cadastros
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-cidades">
            <i class="fas fa-city"></i> Cadastros por Cidade
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-grupos">
            <i class="fas fa-link"></i> Links de Grupos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-compartilhadores">
            <i class="fas fa-user-friends"></i> Mobilizadores
        </a>
    </li>
</ul>

<!-- Conteúdo das Tabs -->
<div class="tab-content">
    
    <!-- Tab: Cadastros -->
    <div class="tab-pane fade show active" id="tab-cadastros">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 style="margin: 0;">👥 Cadastros Realizados</h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary" onclick="downloadCsv('cadastros')">
                        <i class="fas fa-download"></i> Baixar CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <input type="text" id="searchCadastros" class="form-control" placeholder="🔍 Buscar por nome, WhatsApp ou cidade...">
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>WhatsApp</th>
                                <th>Cidade</th>
                                <th>Voluntário</th>
                                <th>Data</th>
                                <th>Indicação</th>
                            </tr>
                        </thead>
                        <tbody id="cadastrosBody">
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner"></div><p>Carregando cadastros recentes...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Cadastros por Cidade -->
    <div class="tab-pane fade" id="tab-cidades">
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">📊 Cadastros por Cidade</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <input type="text" id="searchCidades" class="form-control" placeholder="🔍 Buscar cidade...">
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Posição</th>
                                <th>Cidade</th>
                                <th>Total de Cadastros</th>
                            </tr>
                        </thead>
                        <tbody id="cidadesBody">
                             <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="spinner"></div><p>Carregando dados...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Links de Grupos -->
    <div class="tab-pane fade" id="tab-grupos">
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">🔗 Gerenciar Links dos Grupos</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <input type="text" id="searchGrupos" class="form-control" placeholder="🔍 Buscar cidade...">
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cidade</th>
                                <th>Link do WhatsApp</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="gruposBody">
                             <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="spinner"></div><p>Carregando...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Mobilizadores -->
    <div class="tab-pane fade" id="tab-compartilhadores">
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">📢 Gerenciar Mobilizadores</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Cidade</th>
                                <th>Link</th>
                                <th>Status</th>
                                <th>Indicações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="compartilhadoresBody">
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="spinner"></div><p>Carregando...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    let currentSection = 'tab-cadastros';
    let cidadesData = [];

    async function carregarEstatisticas() {
        try {
            const response = await fetch('api.php?action=estatisticas');
            const data = await response.json();
            if (data.status === 'sucesso') {
                document.getElementById('totalCadastros').textContent = data.dados.total_cadastros;
                document.getElementById('cadastrosHoje').textContent = data.dados.cadastros_hoje;
                document.getElementById('totalCompartilhadores').textContent = data.dados.total_compartilhadores;
                document.getElementById('totalIndicacoes').textContent = data.dados.total_indicacoes;
            }
        } catch (error) {
            showToast('Erro ao carregar estatísticas.', 'danger');
            console.error('Erro:', error);
        }
    }

    async function carregarCadastros() {
        const search = document.getElementById('searchCadastros').value;
        const tbody = document.getElementById('cadastrosBody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner"></div><p>Buscando cadastros...</p></td></tr>';
        
        try {
            const response = await fetch(`api.php?action=cadastros&search=${encodeURIComponent(search)}`);
            const data = await response.json();
            
            if (data.status === 'sucesso') {
                if (data.dados.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5">Nenhum cadastro encontrado.</td></tr>';
                } else {
                    tbody.innerHTML = data.dados.map(item => `
                        <tr>
                            <td><strong>${item.nome}</strong></td>
                            <td>${item.whatsapp}</td>
                            <td>${item.cidade}</td>
                            <td>${item.voluntario}</td>
                            <td>${item.data_cadastro_formatada}</td>
                            <td>${item.indicado_por || '-'}</td>
                        </tr>
                    `).join('');
                }
            }
        } catch (error) {
            showToast('Erro ao carregar cadastros.', 'danger');
            console.error('Erro:', error);
        }
    }
    
    function downloadCsv(tipo) {
        let url = '';
        if (tipo === 'cadastros') {
            const search = document.getElementById('searchCadastros').value;
            url = `api.php?action=exportar_cadastros_csv&search=${encodeURIComponent(search)}`;
        }
        window.location.href = url;
    }

    async function carregarGrupos() {
        const search = document.getElementById('searchGrupos').value;
        const tbody = document.getElementById('gruposBody');
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5"><div class="spinner"></div><p>Carregando...</p></td></tr>';
        
        try {
            const response = await fetch(`api.php?action=grupos&search=${encodeURIComponent(search)}`);
            const data = await response.json();
            
            if (data.status === 'sucesso') {
                if (data.dados.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5">Nenhum grupo encontrado.</td></tr>';
                } else {
                    tbody.innerHTML = data.dados.map(item => `
                        <tr>
                            <td><strong>${item.cidade}</strong></td>
                            <td>
                                <input type="text" value="${item.link_whatsapp}" id="link_${item.id}" class="form-control">
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="atualizarLink(${item.id})">
                                    <i class="fas fa-save"></i> Salvar
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        } catch (error) {
            showToast('Erro ao carregar grupos.', 'danger');
            console.error('Erro:', error);
        }
    }
    
    async function carregarCompartilhadores() {
        const tbody = document.getElementById('compartilhadoresBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="spinner"></div><p>Carregando...</p></td></tr>';
        
        try {
            const response = await fetch('api.php?action=compartilhadores');
            const data = await response.json();
            
            if (data.status === 'sucesso') {
                if (data.dados.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Nenhum mobilizador encontrado.</td></tr>';
                } else {
                    tbody.innerHTML = data.dados.map(item => {
                        let statusBadge = '', acoes = '';
                        if (item.status === 'pendente') {
                            statusBadge = '<span class="badge badge-warning">⏳ Pendente</span>';
                            acoes = `<button class="btn btn-sm btn-success" onclick="aprovarCompartilhador(${item.id})">✅ Aprovar</button><button class="btn btn-sm btn-danger" onclick="rejeitarCompartilhador(${item.id})">❌ Rejeitar</button>`;
                        } else if (item.status === 'aprovado') {
                            statusBadge = `<span class="badge badge-success">✅ Aprovado</span>`;
                            acoes = `<button class="btn btn-sm btn-danger" onclick="rejeitarCompartilhador(${item.id})">❌ Rejeitar</button>`;
                        } else {
                            statusBadge = `<span class="badge badge-danger">❌ Rejeitado</span>`;
                            acoes = `<button class="btn btn-sm btn-success" onclick="aprovarCompartilhador(${item.id})">✅ Aprovar</button>`;
                        }
                        return `
                            <tr>
                                <td><strong>${item.nome}</strong></td>
                                <td>${item.email}</td>
                                <td>${item.cidade}</td>
                                <td><a href="/grupos/index.php?ref=${item.link_personalizado}" target="_blank"><code>${item.link_personalizado}</code></a></td>
                                <td>${statusBadge}</td>
                                <td><strong>${item.total_indicacoes}</strong></td>
                                <td><div class="d-flex gap-1">${acoes}</div></td>
                            </tr>
                        `;
                    }).join('');
                }
            }
        } catch (error) {
            showToast('Erro ao carregar mobilizadores.', 'danger');
            console.error('Erro:', error);
        }
    }

    async function carregarCadastrosPorCidade() {
        const tbody = document.getElementById('cidadesBody');
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5"><div class="spinner"></div><p>Carregando dados...</p></td></tr>';
        
        try {
            const response = await fetch('api.php?action=cadastros_por_cidade');
            const data = await response.json();
            
            if (data.status === 'sucesso') {
                cidadesData = data.dados; // Salva os dados para o filtro
                renderCidades(data.dados);
            }
        } catch (error) {
            showToast('Erro ao carregar dados por cidade.', 'danger');
            console.error('Erro:', error);
        }
    }

    function renderCidades(dados) {
        const tbody = document.getElementById('cidadesBody');
        if (dados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5">Nenhuma cidade com cadastros.</td></tr>';
        } else {
            tbody.innerHTML = dados.map((item, index) => `
                <tr>
                    <td class="posicao-cidade">${index + 1}º</td>
                    <td><strong>${item.cidade}</strong></td>
                    <td><strong>${item.total}</strong></td>
                </tr>
            `).join('');
        }
    }

    async function atualizarLink(id) {
        const novoLink = document.getElementById(`link_${id}`).value;
        const formData = new FormData();
        formData.append('action', 'atualizar_link');
        formData.append('id', id);
        formData.append('link_whatsapp', novoLink);
        
        try {
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            showToast(data.msg, data.status === 'sucesso' ? 'success' : 'danger');
        } catch (error) {
            showToast('Erro ao atualizar link.', 'danger');
            console.error('Erro:', error);
        }
    }

    async function aprovarCompartilhador(id) {
        // Substituir o `confirm()`
        if (!window.confirm('Aprovar este mobilizador?')) return;
        
        const formData = new FormData();
        formData.append('action', 'aprovar');
        formData.append('id', id);
        
        try {
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            showToast(data.msg, data.status === 'sucesso' ? 'success' : 'danger');
            carregarCompartilhadores();
        } catch (error) {
            showToast('Erro ao aprovar mobilizador.', 'danger');
            console.error('Erro:', error);
        }
    }
    
    async function rejeitarCompartilhador(id) {
        // Substituir o `confirm()`
        if (!window.confirm('Rejeitar este mobilizador?')) return;
        
        const formData = new FormData();
        formData.append('action', 'rejeitar');
        formData.append('id', id);
        
        try {
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const data = await response.json();
            showToast(data.msg, data.status === 'sucesso' ? 'success' : 'danger');
            carregarCompartilhadores();
        } catch (error) {
            showToast('Erro ao rejeitar mobilizador.', 'danger');
            console.error('Erro:', error);
        }
    }

    document.getElementById('searchCadastros').addEventListener('input', function() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => carregarCadastros(), 500);
    });
    
    document.getElementById('searchGrupos').addEventListener('input', function() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => carregarGrupos(), 500);
    });
    
    document.getElementById('searchCidades').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredData = cidadesData.filter(item => 
            item.cidade.toLowerCase().includes(searchTerm)
        );
        renderCidades(filteredData);
    });
    
    document.addEventListener('DOMContentLoaded', () => {
        carregarEstatisticas();
        carregarCadastros();
        
        // Ativar tabs
        const tabLinks = document.querySelectorAll('.nav-tabs a');
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.nav-tabs a').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
                
                this.classList.add('active');
                document.querySelector(this.getAttribute('href')).classList.add('show', 'active');

                // Carregar dados da aba selecionada
                const sectionId = this.getAttribute('href');
                if (sectionId === '#tab-cadastros') carregarCadastros();
                else if (sectionId === '#tab-cidades') carregarCadastrosPorCidade();
                else if (sectionId === '#tab-grupos') carregarGrupos();
                else if (sectionId === '#tab-compartilhadores') carregarCompartilhadores();
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
