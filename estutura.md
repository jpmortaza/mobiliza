mobiliza/
├── install.php                      # Arquivo de instalação
├── config.php                       # Configurações do banco
├── estilo_global.css                 # Estilos globais do sistema
├── 
├── admin/                           # Painel Administrativo Unificado
│   ├── index.php                    # Login do admin
│   ├── dashboard.php                # Dashboard principal
│   ├── header.php                   # Header do admin
│   ├── footer.php                   # Footer do admin
│   ├── logout.php                   # Logout
│   ├── processa_login.php           # Processamento do login
│   ├── 
│   ├── eventos/                     # Gestão de Eventos
│   │   ├── index.php                # Lista de eventos
│   │   ├── form.php                 # Criar/Editar evento
│   │   ├── inscricoes.php           # Ver inscrições
│   │   └── api.php                  # API para eventos
│   ├── 
│   ├── apoie/                       # Gestão de Apoie (Petições)
│   │   ├── index.php                # Lista de petições
│   │   ├── form.php                 # Criar/Editar petição
│   │   ├── assinaturas.php          # Ver assinaturas
│   │   └── api.php                  # API para petições
│   ├── 
│   ├── usuarios/                    # Gestão de Usuários
│   │   ├── index.php                # Lista usuários
│   │   ├── form.php                 # Criar/Editar usuário
│   │   └── api.php                  # API para usuários
│   └── 
│   └── crm/                         # CRM Unificado
│       ├── index.php                # Dashboard do CRM
│       ├── contatos.php             # Lista de contatos
│       └── api.php                  # API do CRM
│
├── eventos/                         # Área Pública de Eventos
│   ├── index.php                    # Página do evento público
│   ├── processa_inscricao.php       # Processar inscrição
│   ├── obrigado.php                 # Página de obrigado
│   └── checkin/                     # Sistema de Check-in
│       ├── index.php                # Interface de check-in
│       └── api.php                  # API do check-in
│
├── apoie/                           # Área Pública de Apoie
│   ├── index.php                    # Página da petição pública
│   ├── processa_assinatura.php      # Processar assinatura
│   └── obrigado.php                 # Página de obrigado
│
├── uploads/                         # Arquivos enviados
│   ├── eventos/                     # Imagens de eventos
│   └── apoie/                       # Imagens de petições
│
├── api/                            # APIs públicas
│   ├── webhook.php                  # Para integrações externas
│   └── public.php                   # API pública (futura)
│
└── .htaccess                       # Configurações Apache



