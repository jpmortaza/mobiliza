# 🚀 Mobiliza+ V2 - Sistema de Mobilização Digital

> Plataforma completa para mobilização política e social com sistema de indicações, gamificação e integração WhatsApp

[![Status](https://img.shields.io/badge/status-in%20development-yellow)](https://github.com/jpmortaza/mobiliza)
[![Progress](https://img.shields.io/badge/progress-65%25-blue)](./PROGRESSO_IMPLEMENTACAO.md)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-777bb4)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/mysql-%3E%3D8.0-00758f)](https://www.mysql.com/)

---

## 📋 Sobre o Projeto

**Mobiliza+** é um sistema completo de mobilização digital que integra:

- 📝 **Formulários de Apoio** (moções/petições/pesquisas)
- 🎟️ **Formulários de Eventos** (com controle de vagas)
- 📱 **Grupos WhatsApp** (integração Evolution API)
- 💼 **CRM Completo** (enriquecimento de dados + atendimentos)
- 🎮 **Sistema de Gamificação** (10 níveis, pontos, ranking)
- 🔗 **Indicações Universais** (tracking automático de referências)
- ⏰ **Disparos de Mensagens** (agendamento e variações)

---

## ✨ Principais Funcionalidades

### 🎯 Sistema de Mobilizadores

- **10 níveis de gamificação** com progressão baseada em pontos
- **Ranking em tempo real** com Top 10 mobilizadores
- **Códigos únicos de referência** para tracking de indicações
- **Links personalizados** para cada mobilizador
- **Dashboard completo** com estatísticas de desempenho
- **Workflow de aprovação** para novos mobilizadores

### 🔗 Tracking de Indicações Universal

- **Detecção automática** de códigos `?ref=` nas URLs
- **Armazenamento persistente** (cookies + localStorage)
- **Injeção automática** em todos os formulários
- **Atribuição precisa** de conversões aos mobilizadores
- **API JavaScript** para integração fácil

### 📝 Formulários Flexíveis

- **4 tipos**: Apoio, Rejeição, Ambos, Pesquisa
- **Campos customizáveis**: até 3 campos extras por formulário
- **Barra de progresso** com meta de assinaturas
- **Resultados em tempo real** (apoio vs rejeição)
- **Botões personalizáveis** com textos customizados
- **Integração WhatsApp** com grupos

### 💼 CRM Integrado

- **Cadastro universal** de pessoas
- **4 origens rastreadas**: formulários, eventos, WhatsApp, manual
- **Enriquecimento de dados**: profissão, escolaridade, interesses
- **Histórico completo**: assinaturas, eventos, indicações, atendimentos
- **Níveis de engajamento** (0-10)
- **Timeline de atendimentos** com atualizações

### 📱 Integração WhatsApp (Evolution API)

- **Criar grupos** automaticamente
- **Enviar mensagens** (texto, imagem, áudio, vídeo)
- **Agendar mensagens** com variações de conteúdo
- **Webhooks** para atualização em tempo real
- **Rate limiting** configurável
- **Logs de envio** completos

---

## 🚀 Instalação Rápida

### Requisitos

- PHP >= 8.0
- MySQL >= 8.0
- Apache/Nginx com mod_rewrite
- Composer (opcional, mas recomendado)

### Passo a Passo

1. **Clone o repositório:**
```bash
git clone https://github.com/jpmortaza/mobiliza.git
cd mobiliza
```

2. **Configure o banco de dados:**
```bash
# Edite config.php com suas credenciais
nano config.php
```

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'mobiliza');
```

3. **Execute a instalação:**
```
Acesse: http://seudominio.com/install_v2.php
```

Isso irá:
- ✅ Criar todas as 15 tabelas do banco
- ✅ Inserir configurações padrão
- ✅ Criar usuário admin
- ✅ Criar diretórios necessários

4. **Login inicial:**
```
Email: admin@mobiliza.com
Senha: admin123
```

**⚠️ IMPORTANTE:** Altere a senha imediatamente após o primeiro login!

5. **Configure URLs amigáveis (.htaccess):**
```apache
RewriteEngine On
RewriteBase /

# Formulários de Apoio
RewriteRule ^apoio/([a-z0-9-]+)/?$ apoie/index.php?slug=$1 [L,QSA]

# Eventos
RewriteRule ^evento/([a-z0-9-]+)/?$ eventos/index.php?slug=$1 [L,QSA]

# Portal Mobilizador
RewriteRule ^mobilizador/([a-z0-9-]+)/?$ mobilizador/dashboard.php?link=$1 [L,QSA]
```

---

## 📊 Status de Implementação

### ✅ Concluído (65%)

- [x] **Database Schema Completo** (15 tabelas)
- [x] **Sistema de Pessoas** (lista + detalhes)
- [x] **Sistema de Mobilizadores** (gamificação completa)
- [x] **Tracking de Indicações** (JavaScript + backend)
- [x] **Formulários de Apoio** (interface admin)
- [x] **Funções Auxiliares** (segurança, upload, validação)

### 🚧 Em Progresso (35%)

- [ ] Formulários de Apoio (página pública)
- [ ] Formulários de Eventos
- [ ] Grupos WhatsApp e Evolution API
- [ ] Mensagens Agendadas com Variações
- [ ] CRM Completo (timeline)
- [ ] Portal do Mobilizador
- [ ] Relatórios e Analytics
- [ ] Sistema de Configurações

📖 **[Ver progresso detalhado](./PROGRESSO_IMPLEMENTACAO.md)**

---

## 🎮 Sistema de Gamificação

### Níveis e Pontuação

| Nível | Pontos | Badge |
|-------|--------|-------|
| 1 | 0-99 | 🥉 Bronze |
| 2 | 100-499 | 🥈 Prata |
| 3 | 500-999 | 🥇 Ouro |
| 4 | 1.000-2.499 | 💎 Diamante |
| 5 | 2.500-4.999 | 💠 Platina |
| 6 | 5.000-7.499 | 🔮 Cristal |
| 7 | 7.500-9.999 | ⭐ Estrela |
| 8 | 10.000-14.999 | 🌟 Super Estrela |
| 9 | 15.000-24.999 | 💫 Mega Estrela |
| 10 | 25.000+ | 👑 Lendário |

### Como Ganhar Pontos

- 🔗 **+10 pontos** por indicação cadastrada
- ✍️ **+5 pontos** por assinatura gerada
- 🎟️ **+7 pontos** por inscrição em evento gerada

---

## 🔧 Configuração

### Evolution API (WhatsApp)

1. Acesse: `/admin/configuracoes_v2.php`
2. Aba "Evolution API"
3. Configure:
   - URL da API
   - API Key
   - Instância padrão
   - Webhook URL
   - Rate limit

### SMTP (Email)

1. Acesse: `/admin/configuracoes_v2.php`
2. Aba "Email"
3. Configure:
   - Host SMTP
   - Porta
   - Usuário
   - Senha

---

## 📱 Como Usar

### Criar um Formulário de Apoio

1. Acesse `/admin/apoie/form_v2.php`
2. Preencha:
   - Título e descrição
   - Upload de imagem
   - Escolha o tipo (apoio, rejeição, ambos, pesquisa)
   - Configure meta e resultados
   - Personalize botões
   - Adicione campos extras (até 3)
   - Configure WhatsApp
3. Salve e copie o link público

### Cadastrar um Mobilizador

1. Acesse `/admin/mobilizadores/index.php`
2. Clique em "Novo Mobilizador"
3. Selecione uma pessoa ou crie nova
4. Sistema gera código único automaticamente
5. Aprove o mobilizador
6. Mobilizador recebe seu link personalizado

### Usar Sistema de Indicações

**No código HTML da página pública:**

```html
<!-- Inclua o script de tracking -->
<script src="/js/referral-tracking.js"></script>

<!-- Seus formulários -->
<form method="POST" action="/apoie/processa_assinatura.php">
    <input type="text" name="nome" required>
    <input type="text" name="whatsapp" required>
    <!-- Campo ref_code será injetado automaticamente -->
    <button type="submit">Apoiar</button>
</form>
```

**Compartilhar links com referência:**

```
https://seusite.com/apoio/minha-causa?ref=ABC123
https://seusite.com/evento/meu-evento?ref=ABC123
```

O código `ABC123` será:
- Salvo em cookie (30 dias)
- Salvo em localStorage
- Injetado automaticamente em formulários
- Atribuído ao mobilizador na conversão

---

## 🗂️ Estrutura de Arquivos

```
mobiliza/
├── install_v2.php              # Instalador do sistema
├── config.php                  # Configurações e funções
├── index.php                   # Homepage
│
├── js/
│   └── referral-tracking.js    # Sistema de tracking
│
├── admin/                      # Painel Administrativo
│   ├── pessoas/
│   │   ├── index.php           # Lista de pessoas
│   │   ├── detalhes.php        # Perfil completo
│   │   └── form.php            # Criar/editar
│   │
│   ├── mobilizadores/
│   │   ├── index.php           # Dashboard mobilizadores
│   │   ├── api.php             # Ações (aprovar, etc)
│   │   └── detalhes.php        # Perfil mobilizador
│   │
│   ├── apoie/
│   │   ├── index.php           # Lista formulários
│   │   ├── form_v2.php         # Criar/editar (COMPLETO)
│   │   └── assinaturas.php     # Ver assinaturas
│   │
│   ├── eventos/
│   │   ├── index.php
│   │   └── form.php
│   │
│   ├── grupos/
│   │   ├── index.php
│   │   └── form.php
│   │
│   ├── mensagens/
│   │   ├── index.php
│   │   └── form.php
│   │
│   └── crm/
│       ├── index.php
│       └── atendimento.php
│
├── apoie/                      # Área Pública Apoio
│   ├── index.php               # Página do formulário
│   ├── processa_assinatura.php # Processar
│   └── obrigado.php            # Sucesso
│
├── eventos/                    # Área Pública Eventos
│   ├── index.php
│   └── processa_inscricao.php
│
├── mobilizador/                # Portal Mobilizador
│   ├── login.php
│   └── dashboard.php
│
├── api/                        # APIs
│   ├── evolution.php           # Wrapper Evolution API
│   └── webhook.php             # Receber webhooks
│
├── cron/                       # Jobs agendados
│   └── processar_mensagens.php
│
└── uploads/                    # Arquivos enviados
    ├── eventos/
    ├── apoie/
    ├── usuarios/
    ├── grupos/
    └── mensagens/
```

---

## 🗄️ Banco de Dados

### Tabelas Principais

**15 tabelas:**

1. `usuarios_sistema` - Operadores (admin, operador, mobilizador)
2. `pessoas` - Cadastro universal
3. `mobilizadores` - Mobilizadores com gamificação
4. `grupos_mobilizadores` - Equipes
5. `formularios_apoio` - Formulários de apoio/petições
6. `assinaturas` - Assinaturas
7. `formularios_eventos` - Eventos
8. `inscricoes_eventos` - Inscrições
9. `grupos_whatsapp` - Grupos do WhatsApp
10. `mensagens_agendadas` - Mensagens programadas
11. `logs_envio_mensagem` - Logs de envio
12. `atendimentos_crm` - Atendimentos
13. `atualizacoes_atendimento` - Timeline
14. `configuracoes_sistema` - Configurações
15. `logs_sistema` - Auditoria

### Diagrama ER

```
┌─────────────────┐
│ usuarios_sistema│
└────────┬────────┘
         │ criado_por
         │
┌────────▼────────┐      ┌─────────────────┐
│ formularios_apoio├──────► assinaturas     │
└─────────────────┘      └────────┬────────┘
                                   │ pessoa_id
┌─────────────────┐      ┌────────▼────────┐      ┌─────────────────┐
│ mobilizadores   ◄──────┤ pessoas         ├──────► atendimentos_crm│
└────────┬────────┘      └────────┬────────┘      └─────────────────┘
         │                        │
         │ coordenador_id         │ indicado_por
         │                        │
         └────────────────────────┘
```

---

## 🔒 Segurança

### Implementado

- ✅ **Prepared Statements** em todas as queries
- ✅ **Password hashing** (bcrypt)
- ✅ **CSRF protection** em formulários
- ✅ **XSS prevention** (htmlspecialchars)
- ✅ **SQL injection protection** (PDO)
- ✅ **File upload validation** (tipo, tamanho, MIME)
- ✅ **Session security** (httponly, samesite)
- ✅ **Security headers** (X-Frame-Options, CSP, etc)
- ✅ **Logs de auditoria** completos

### Recomendações

- 🔐 Use HTTPS em produção
- 🔑 Altere senhas padrão
- 🗝️ Configure 2FA (futuro)
- 📝 Backups automáticos
- 🔒 IP whitelist (admin)

---

## 🧪 Testes

### Testar Sistema de Tracking

```javascript
// Console do navegador
MobilizaReferral.saveCode('TEST123');
console.log(MobilizaReferral.getCurrentCode()); // 'TEST123'

// Ou adicione ?ref=TEST123 na URL e verifique
// que o cookie foi criado
```

### Testar Gamificação

1. Crie um mobilizador
2. Crie uma assinatura com `origem_referencia = codigo_mobilizador`
3. Verifique que:
   - `total_indicacoes_diretas` incrementou
   - `pontos` foi atualizado
   - `nivel` recalculado se necessário

---

## 📈 Roadmap

### Versão 2.0 (Atual - Em Desenvolvimento)

- [x] Database schema completo
- [x] Sistema de pessoas
- [x] Mobilizadores com gamificação
- [x] Tracking de indicações
- [x] Formulários de apoio (admin)
- [ ] Formulários de apoio (público)
- [ ] Formulários de eventos
- [ ] Evolution API
- [ ] Mensagens agendadas
- [ ] Portal do mobilizador

### Versão 2.1 (Planejado)

- [ ] CRM completo com timeline
- [ ] Relatórios avançados
- [ ] Dashboards visuais
- [ ] Exportação de dados
- [ ] Integrações (Zapier, etc)

### Versão 2.2 (Futuro)

- [ ] App mobile
- [ ] Notificações push
- [ ] Chat integrado
- [ ] IA para insights
- [ ] API pública RESTful

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

---

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

---

## 👥 Autores

- **JP Mortaza** - *Trabalho Inicial* - [@jpmortaza](https://github.com/jpmortaza)

---

## 🙏 Agradecimentos

- Evolution API pela integração WhatsApp
- Comunidade PHP pela documentação
- Todos os contribuidores

---

## 📞 Suporte

- 📧 Email: contato@mobiliza.com
- 🐛 Issues: [GitHub Issues](https://github.com/jpmortaza/mobiliza/issues)
- 📖 Docs: [Progresso de Implementação](./PROGRESSO_IMPLEMENTACAO.md)

---

<p align="center">
  Feito com ❤️ para mobilização social e política
</p>
