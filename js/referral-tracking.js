/**
 * MOBILIZA+ V2 - Sistema de Tracking de Referência
 *
 * Este script captura e armazena códigos de referência de mobilizadores
 * para atribuição de indicações, assinaturas e inscrições
 */

(function() {
    'use strict';

    const COOKIE_NAME = 'mobiliza_ref';
    const COOKIE_DAYS = 30;
    const LOCAL_STORAGE_KEY = 'mobiliza_ref_code';

    /**
     * Inicializar tracking ao carregar a página
     */
    function initReferralTracking() {
        // Detectar código na URL
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref');

        if (refCode && refCode.length > 0) {
            // Salvar referência
            saveReferralCode(refCode);

            // Opcional: remover da URL para não compartilhar código
            // removeRefFromURL();
        }

        // Debug: mostrar código atual
        const currentRef = getCurrentReferralCode();
        if (currentRef) {
            console.log('[Mobiliza+] Código de referência ativo:', currentRef);
        }
    }

    /**
     * Salvar código de referência
     */
    function saveReferralCode(code) {
        // Limpar código
        const cleanCode = code.trim().toUpperCase();

        // Salvar em cookie (prioridade)
        setCookie(COOKIE_NAME, cleanCode, COOKIE_DAYS);

        // Salvar em localStorage (backup)
        try {
            localStorage.setItem(LOCAL_STORAGE_KEY, cleanCode);
        } catch (e) {
            console.warn('[Mobiliza+] Erro ao salvar em localStorage:', e);
        }

        console.log('[Mobiliza+] Código de referência salvo:', cleanCode);
    }

    /**
     * Obter código de referência atual
     */
    function getCurrentReferralCode() {
        // Prioridade 1: URL (caso esteja visitando agora)
        const urlParams = new URLSearchParams(window.location.search);
        const urlRef = urlParams.get('ref');
        if (urlRef) return urlRef.trim().toUpperCase();

        // Prioridade 2: Cookie
        const cookieRef = getCookie(COOKIE_NAME);
        if (cookieRef) return cookieRef;

        // Prioridade 3: localStorage
        try {
            const storageRef = localStorage.getItem(LOCAL_STORAGE_KEY);
            if (storageRef) return storageRef;
        } catch (e) {
            console.warn('[Mobiliza+] Erro ao ler localStorage:', e);
        }

        return null;
    }

    /**
     * Injetar código de referência em formulários
     */
    function injectReferralCodeInForms() {
        const refCode = getCurrentReferralCode();
        if (!refCode) return;

        // Buscar todos os formulários
        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            // Verificar se já tem campo de referência
            let refInput = form.querySelector('input[name="ref_code"]');

            if (!refInput) {
                // Criar campo oculto
                refInput = document.createElement('input');
                refInput.type = 'hidden';
                refInput.name = 'ref_code';
                form.appendChild(refInput);
            }

            // Definir valor
            refInput.value = refCode;
        });

        console.log('[Mobiliza+] Código de referência injetado em', forms.length, 'formulário(s)');
    }

    /**
     * Adicionar ref a todos os links (opcional)
     */
    function addRefToLinks(selector = 'a[href*="/apoio/"], a[href*="/evento/"], a[href*="/eventos/"]') {
        const refCode = getCurrentReferralCode();
        if (!refCode) return;

        const links = document.querySelectorAll(selector);

        links.forEach(link => {
            const url = new URL(link.href, window.location.origin);

            // Se não tem ref na URL, adicionar
            if (!url.searchParams.has('ref')) {
                url.searchParams.set('ref', refCode);
                link.href = url.toString();
            }
        });
    }

    /**
     * Remover ref da URL (sem recarregar página)
     */
    function removeRefFromURL() {
        const url = new URL(window.location);
        if (url.searchParams.has('ref')) {
            url.searchParams.delete('ref');
            window.history.replaceState({}, document.title, url.toString());
        }
    }

    /**
     * Gerar link compartilhável com referência
     */
    function generateShareLink(baseUrl, customCode = null) {
        const refCode = customCode || getCurrentReferralCode();
        if (!refCode) return baseUrl;

        const url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('ref', refCode);

        return url.toString();
    }

    /**
     * Copiar link com referência
     */
    function copyShareLink(baseUrl, customCode = null) {
        const link = generateShareLink(baseUrl, customCode);

        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(() => {
                console.log('[Mobiliza+] Link copiado:', link);
                return true;
            }).catch(err => {
                console.error('[Mobiliza+] Erro ao copiar:', err);
                return false;
            });
        } else {
            // Fallback para navegadores antigos
            const tempInput = document.createElement('input');
            tempInput.value = link;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            return true;
        }
    }

    /**
     * Cookie helpers
     */
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    /**
     * API pública
     */
    window.MobilizaReferral = {
        init: initReferralTracking,
        getCurrentCode: getCurrentReferralCode,
        saveCode: saveReferralCode,
        injectInForms: injectReferralCodeInForms,
        generateShareLink: generateShareLink,
        copyShareLink: copyShareLink,
        addRefToLinks: addRefToLinks
    };

    // Auto-inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initReferralTracking();
            // Aguardar um pouco para garantir que forms estão no DOM
            setTimeout(injectReferralCodeInForms, 100);
        });
    } else {
        initReferralTracking();
        setTimeout(injectReferralCodeInForms, 100);
    }

    // Reinjetar em formulários adicionados dinamicamente
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.tagName === 'FORM') {
                    injectReferralCodeInForms();
                }
            });
        });
    });

    // Observar mudanças no DOM
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

})();
