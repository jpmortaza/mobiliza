</div>
        </main>
    </div>
    
    <script>
        // Função para toggle do menu mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Fechar menu ao clicar fora (mobile)
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Ajustar layout no resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });
        
        // Confirmação antes de sair
        document.addEventListener('beforeunload', function(e) {
            // Verificar se há mudanças não salvas
            const forms = document.querySelectorAll('form');
            let hasUnsavedChanges = false;
            
            forms.forEach(form => {
                if (form.getAttribute('data-changed') === 'true') {
                    hasUnsavedChanges = true;
                }
            });
            
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'Você tem alterações não salvas. Tem certeza que deseja sair?';
            }
        });
        
        // Marcar formulários como alterados
        document.querySelectorAll('form input, form textarea, form select').forEach(field => {
            const originalValue = field.value;
            
            field.addEventListener('input', function() {
                const form = this.closest('form');
                if (this.value !== originalValue) {
                    form.setAttribute('data-changed', 'true');
                }
            });
        });
        
        // Auto-save para formulários importantes (implementar conforme necessário)
        function autoSave(formId) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Salvar no localStorage como backup
            localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
        }
        
        // Restaurar auto-save
        function restoreAutoSave(formId) {
            const savedData = localStorage.getItem(`autosave_${formId}`);
            if (!savedData) return;
            
            try {
                const data = JSON.parse(savedData);
                const form = document.getElementById(formId);
                
                Object.keys(data).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field && field.value === '') {
                        field.value = data[key];
                    }
                });
            } catch (e) {
                console.log('Erro ao restaurar auto-save:', e);
            }
        }
        
        // Limpar auto-save após sucesso
        function clearAutoSave(formId) {
            localStorage.removeItem(`autosave_${formId}`);
        }
        
        // Notificações Toast
        function showToast(message, type = 'info') {
            // Criar container se não existir
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 300px;
                `;
                document.body.appendChild(container);
            }
            
            // Criar toast
            const toast = document.createElement('div');
            toast.className = `alert alert-${type}`;
            toast.style.cssText = `
                margin-bottom: 10px;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            `;
            toast.textContent = message;
            
            // Adicionar estilos de animação se não existirem
            if (!document.getElementById('toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOut {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            container.appendChild(toast);
            
            // Remover após 5 segundos
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 5000);
            
            // Remover ao clicar
            toast.addEventListener('click', () => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            });
        }
        
        // Função para requisições AJAX padronizadas
        async function apiRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                },
            };
            
            const config = { ...defaultOptions, ...options };
            
            try {
                const response = await fetch(url, config);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Erro na requisição');
                }
                
                return data;
            } catch (error) {
                console.error('Erro na API:', error);
                showToast(error.message || 'Erro na comunicação com o servidor', 'danger');
                throw error;
            }
        }
        
        // Máscaras para campos
        function applyMasks() {
            // Máscara para telefone
            document.querySelectorAll('input[data-mask="phone"]').forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                        if (value.length === 14) {
                            e.target.value = value;
                        } else if (value.length <= 10) {
                            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                            e.target.value = value;
                        }
                    }
                });
            });
            
            // Máscara para CPF
            document.querySelectorAll('input[data-mask="cpf"]').forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    e.target.value = value;
                });
            });
        }
        
        // Aplicar máscaras quando a página carregar
        document.addEventListener('DOMContentLoaded', applyMasks);
        
        // Função para preview de imagens
        function setupImagePreviews() {
            document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let preview = input.parentElement.querySelector('.image-preview');
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.className = 'image-preview';
                            preview.style.cssText = 'margin-top: 10px;';
                            input.parentElement.appendChild(preview);
                        }
                        
                        preview.innerHTML = `
                            <img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        `;
                    };
                    reader.readAsDataURL(file);
                });
            });
        }
        
        // Configurar previews quando a página carregar
        document.addEventListener('DOMContentLoaded', setupImagePreviews);
        
        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+S para salvar formulário
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const form = document.querySelector('form');
                if (form) {
                    form.submit();
                }
            }
            
            // Ctrl+E para editar
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                const editBtn = document.querySelector('.btn-edit, [data-action="edit"]');
                if (editBtn) {
                    editBtn.click();
                }
            }
            
            // ESC para cancelar
            if (e.key === 'Escape') {
                const cancelBtn = document.querySelector('.btn-cancel, [data-action="cancel"]');
                if (cancelBtn) {
                    cancelBtn.click();
                }
            }
        });
    </script>
</body>
</html>