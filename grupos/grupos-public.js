/**
 * MOBILIZA+ - Módulo Grupos (Público)
 * Arquivo: grupos/grupos-public.js
 * Descrição: Lógica JavaScript para as páginas de cadastro de participantes e mobilizadores.
 */

document.addEventListener('DOMContentLoaded', function() {
    const isCadastroMobilizador = document.getElementById('compartilhadorForm');
    const isCadastroParticipante = document.getElementById('cadastroForm');

    if (isCadastroMobilizador) {
        setupCadastroMobilizador();
    }

    if (isCadastroParticipante) {
        setupCadastroParticipante();
    }
});

function setupCadastroMobilizador() {
    const form = document.getElementById('compartilhadorForm');
    const linkPersonalizadoInput = document.getElementById('link_personalizado');
    const linkPreview = document.getElementById('linkPreview');
    const linkPreviewText = document.getElementById('linkPreviewText');
    const whatsappInput = document.getElementById('whatsapp');
    const btnSubmit = document.getElementById('btnSubmit');
    const loading = document.getElementById('loading');
    const resultado = document.getElementById('resultado');

    linkPersonalizadoInput.addEventListener('input', function() {
        const link = this.value.trim();
        if (link) {
            // Acessa o URL dinamicamente
            const baseUrl = window.location.origin + window.location.pathname.replace('cadastro.php', 'index.php');
            linkPreviewText.textContent = `${baseUrl}?ref=${link}`;
            linkPreview.style.display = 'block';
        } else {
            linkPreview.style.display = 'none';
        }
    });

    linkPersonalizadoInput.addEventListener('input', function() {
        let value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
        value = value.replace(/-+/g, '-');
        value = value.replace(/^-+|-+$/g, '');
        this.value = value;
    });

    whatsappInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        loading.classList.remove('hidden');
        resultado.classList.add('hidden');
        btnSubmit.disabled = true;
        
        const formData = new FormData(this);

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            loading.classList.add('hidden');
            resultado.classList.remove('hidden');
            
            if (data.status === 'sucesso') {
                resultado.innerHTML = `
                    <div class="sucesso">
                        <h3>✅ Solicitação Enviada!</h3>
                        <p>${data.msg}</p>
                        <p><strong>Seu link:</strong><br>
                        <code style="background: #f0f0f0; padding: 5px; border-radius: 4px;">${data.link_completo}</code></p>
                    </div>
                `;
                this.reset();
            } else {
                resultado.innerHTML = `
                    <div class="erro">
                        <h3>❌ Erro</h3>
                        <p>${data.msg}</p>
                    </div>
                `;
            }
        } catch (error) {
            loading.classList.add('hidden');
            resultado.classList.remove('hidden');
            resultado.innerHTML = `
                <div class="erro">
                    <h3>❌ Erro</h3>
                    <p>Erro de conexão. Tente novamente.</p>
                </div>
            `;
        } finally {
            btnSubmit.disabled = false;
        }
    });
}

function setupCadastroParticipante() {
    const form = document.getElementById('cadastroForm');
    const cidadeInput = document.getElementById('cidade');
    const sugestoesContainer = document.getElementById('cidadesSugestoes');
    const btnSubmit = document.getElementById('btnSubmit');
    const loading = document.getElementById('loading');
    const resultado = document.getElementById('resultado');
    const grupoContainer = document.getElementById('grupoLinkContainer');
    const whatsappInput = document.getElementById('whatsapp');

    let cidades = [];

    // Função para buscar cidades dinamicamente da API
    async function fetchCidades() {
        try {
            const response = await fetch('<?php echo SITE_URL; ?>/admin/grupos/api.php?action=cidades');
            const data = await response.json();
            if (data.status === 'sucesso' && data.dados) {
                cidades = data.dados;
            } else {
                console.error("Erro ao buscar cidades:", data.msg);
            }
        } catch (error) {
            console.error("Erro de rede ao buscar cidades:", error);
        }
    }

    fetchCidades();

    function normalizeText(text) {
        return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function filtrarCidades(termo) {
        if (termo.length < 2) return [];
        
        const termoNormalizado = normalizeText(termo);
        return cidades.filter(cidade => 
            normalizeText(cidade).includes(termoNormalizado)
        ).slice(0, 8);
    }

    function mostrarSugestoes(cidadesFiltradas) {
        sugestoesContainer.innerHTML = '';
        
        if (cidadesFiltradas.length === 0) {
            sugestoesContainer.style.display = 'none';
            return;
        }

        cidadesFiltradas.forEach(cidade => {
            const item = document.createElement('div');
            item.className = 'sugestao-item';
            item.textContent = cidade;
            item.addEventListener('click', function() {
                cidadeInput.value = cidade;
                sugestoesContainer.style.display = 'none';
            });
            sugestoesContainer.appendChild(item);
        });

        sugestoesContainer.style.display = 'block';
    }

    cidadeInput.addEventListener('input', function() {
        const termo = this.value.trim();
        const cidadesFiltradas = filtrarCidades(termo);
        mostrarSugestoes(cidadesFiltradas);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.cidade-container')) {
            sugestoesContainer.style.display = 'none';
        }
    });
    
    whatsappInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 11) {
          value = value.slice(0, 11);
        }
        if (value.length >= 11) {
          value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (value.length >= 7) {
          value = value.replace(/(\d{2})(\d{4,5})(\d{0,4})/, '($1) $2-$3');
        } else if (value.length >= 3) {
          value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        }
        this.value = value;
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        loading.classList.remove('hidden');
        resultado.classList.add('hidden');
        grupoContainer.style.display = 'none';
        btnSubmit.disabled = true;
        
        const formData = new FormData(this);
        const whatsappLimpo = whatsappInput.value.replace(/\D/g, '');
        formData.set('whatsapp', whatsappLimpo);
        
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            loading.classList.add('hidden');
            
            if (data.status === 'sucesso') {
                this.style.display = 'none';
                grupoContainer.style.display = 'block';
                const grupoBtn = document.getElementById('grupoLinkBtn');
                grupoBtn.href = data.link;
                window.open(data.link, '_blank');
            } else {
                resultado.classList.remove('hidden');
                resultado.innerHTML = `
                    <div class="erro">
                        <h3>❌ Erro</h3>
                        <p>${data.msg}</p>
                    </div>
                `;
            }
        } catch (error) {
            loading.classList.add('hidden');
            resultado.classList.remove('hidden');
            resultado.innerHTML = `
                <div class="erro">
                    <h3>❌ Erro</h3>
                    <p>Erro de conexão. Tente novamente.</p>
                </div>
            `;
        } finally {
            btnSubmit.disabled = false;
        }
    });
}
