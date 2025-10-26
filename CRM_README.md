# 📞 CRM Mobiliza+ - Documentação Completa

## Visão Geral

O **CRM Mobiliza+** é um sistema completo de gestão de relacionamento com contatos, desenvolvido especificamente para organizações de mobilização social e política. Ele consolida todos os dados de eventos, petições e grupos em um único lugar, permitindo gerenciar o relacionamento com cada pessoa de forma profissional.

---

## 🎯 Principais Funcionalidades

### 1. **Consolidação Inteligente de Dados**
- Unifica contatos de eventos, petições e grupos WhatsApp
- Elimina duplicatas automaticamente (por WhatsApp)
- Mantém histórico completo de todas as interações
- Score de engajamento automático (0-100)

### 2. **Gestão de Atendentes**
- Cadastro de múltiplos atendentes
- Atribuição automática ou manual de contatos
- Metas individuais (ligações/dia, conversões/mês)
- Rankings e estatísticas de performance
- Controle de disponibilidade

### 3. **Registro de Ligações**
- Cronômetro integrado para medir duração
- Múltiplos status (completada, não atendeu, ocupado, etc.)
- Registro de resultado e conversão
- Agendamento de retornos
- Histórico completo de todas as ligações

### 4. **Enriquecimento de Dados**
- CPF, Data de Nascimento, Gênero
- Profissão, Escolaridade, Renda
- Endereço completo (CEP, logradouro, bairro)
- Redes sociais (Instagram, Facebook, LinkedIn, Twitter)
- Interesses e áreas de voluntariado
- Tags personalizadas

### 5. **Sistema de Tarefas**
- Criação de follow-ups
- Agendamento de retornos
- Priorização (urgente, alta, média, baixa)
- Notificações de vencimento

### 6. **Estatísticas e Relatórios**
- Dashboard com métricas em tempo real
- Análise de desempenho por atendente
- Taxa de conversão
- Ranking de atendentes
- Exportação de dados

---

## 📦 Instalação

### Passo 1: Executar o Instalador

Acesse o instalador do CRM:
```
http://seusite.com/crm_install.php
```

Isso criará todas as tabelas necessárias:
- `crm_contatos` - Contatos consolidados
- `crm_historico` - Histórico de interações
- `crm_ligacoes` - Registro de ligações
- `crm_atendentes` - Gestão de atendentes
- `crm_grupos_whatsapp` - Grupos WhatsApp
- `crm_grupos_participantes` - Participação em grupos
- `crm_tarefas` - Tarefas e follow-ups
- `crm_configuracoes` - Configurações do CRM

### Passo 2: Sincronizar Contatos

Após a instalação, sincronize os contatos existentes:
```
Admin > CRM > Sincronizar
```

Isso importará todos os contatos de:
- Inscrições em eventos
- Assinaturas de petições
- Participantes de grupos

### Passo 3: Cadastrar Atendentes

```
Admin > CRM > Atendentes > Cadastrar Atendente
```

Configure:
- Usuário que será atendente
- Meta de ligações por dia
- Meta de conversões por mês

---

## 🚀 Como Usar

### 1. Dashboard do CRM

Acesse: `/admin/crm/`

O dashboard mostra:
- Total de contatos
- Ligações do dia
- Taxa de conversão
- Tarefas pendentes
- Ranking de atendentes
- Distribuição por status

### 2. Gerenciar Contatos

Acesse: `/admin/crm/contatos.php`

**Filtros disponíveis:**
- Busca por nome, WhatsApp, email ou cidade
- Status do contato
- Prioridade
- Atendente responsável
- Cidade
- Score mínimo

**Visualização:**
- Score de engajamento visual
- Estatísticas (eventos, petições, grupos)
- Status e atendente
- Ações rápidas (ver, ligar, editar)

### 3. Perfil do Contato

Acesse clicando em um contato

**Informações exibidas:**
- Dados pessoais completos
- Score de engajamento
- Histórico de todas as ligações
- Timeline de interações
- Eventos participados
- Petições assinadas
- Grupos WhatsApp
- Tarefas pendentes

**Ações disponíveis:**
- Registrar ligação
- Atualizar status
- Enriquecer dados
- Editar informações

### 4. Registrar Ligação

Acesse: `/admin/crm/registrar_ligacao.php?contato_id=123`

**Recursos:**
- ⏱️ **Cronômetro integrado** - Inicia/pausa/para automaticamente
- 📋 **Dados da ligação** - Tipo, status, duração
- 💬 **Detalhes da conversa** - Objetivo, resumo, observações
- 🎯 **Conversão** - Marcar se converteu em ação
- 📅 **Agendamento** - Agendar retorno automático
- 🔄 **Atualizar status** - Mudar status do contato

**Status de ligação:**
- Completada
- Não atendeu
- Ocupado
- Caixa postal
- Número inválido
- Desligou

**Resultado:**
- Muito positivo
- Positivo
- Neutro
- Negativo
- Callback solicitado

### 5. Gestão de Atendentes

Acesse: `/admin/crm/atendentes.php` (apenas admins)

**Funcionalidades:**
- Cadastrar novos atendentes
- Definir metas individuais
- Ativar/desativar atendentes
- Marcar como disponível/indisponível
- Ver estatísticas de performance
- Ranking de produtividade

**Métricas por atendente:**
- Total de contatos atribuídos
- Ligações realizadas
- Conversões obtidas
- Taxa de conversão
- Tempo médio de ligação
- Performance (Excelente/Bom/Regular/Abaixo)

---

## 📊 Estrutura de Dados

### Score de Engajamento

O score é calculado automaticamente:
- **+10 pontos** por evento participado
- **+5 pontos** por petição assinada
- **+15 pontos** por grupo WhatsApp
- **Máximo:** 100 pontos

**Classificação:**
- 70-100: Alto engajamento (verde)
- 40-69: Médio engajamento (laranja)
- 0-39: Baixo engajamento (cinza)

### Status do Contato

- **Novo** - Recém adicionado ao CRM
- **Aguardando Contato** - Atribuído a atendente, aguardando primeira ligação
- **Em Contato** - Conversas em andamento
- **Contatado** - Já foi contactado com sucesso
- **Interessado** - Demonstrou interesse na causa
- **Muito Interessado** - Alto potencial
- **Voluntário** - Cadastrado como voluntário
- **Não Interessado** - Não tem interesse
- **Não Responde** - Tentativas sem sucesso
- **Número Inválido** - WhatsApp não funciona

### Prioridade

- **Urgente** - Requer atenção imediata (borda vermelha)
- **Alta** - Importante contatar em breve (borda amarela)
- **Média** - Prioridade normal (borda azul)
- **Baixa** - Pode aguardar (borda cinza)

---

## 🔄 Fluxo de Trabalho Recomendado

### Para Atendentes

1. **Acessar Dashboard**
   - Ver tarefas pendentes
   - Verificar retornos agendados

2. **Filtrar Contatos**
   - "Aguardando Contato" + Alta Prioridade
   - Ordenado por score de engajamento

3. **Realizar Ligações**
   - Abrir perfil do contato
   - Estudar histórico
   - Registrar ligação com cronômetro
   - Preencher resultado completo

4. **Agendar Retornos**
   - Se necessário, agendar nova ligação
   - Criar tarefas de follow-up

5. **Atualizar Status**
   - Mover para status apropriado
   - Adicionar observações relevantes

### Para Administradores

1. **Monitorar Performance**
   - Acompanhar dashboard diário
   - Verificar ranking de atendentes
   - Analisar taxa de conversão

2. **Gerenciar Atendentes**
   - Ajustar metas conforme necessidade
   - Redistribuir contatos se necessário
   - Reconhecer melhores performances

3. **Sincronizar Dados**
   - Executar sincronização semanal
   - Verificar novos contatos
   - Limpar duplicatas

4. **Exportar Relatórios**
   - Gerar relatórios mensais
   - Analisar tendências
   - Compartilhar com equipe

---

## 📈 Métricas Importantes

### Taxa de Conversão

```
Taxa = (Ligações com Conversão / Total de Ligações Completadas) × 100
```

**Benchmarks:**
- **Excelente:** > 25%
- **Bom:** 15-25%
- **Regular:** 10-15%
- **Abaixo:** < 10%

### Produtividade do Atendente

- **Ligações/dia:** Meta recomendada 30-50
- **Conversões/mês:** Meta recomendada 15-30
- **Tempo médio:** 3-8 minutos por ligação

---

## 🔌 Integrações

### API do CRM

```php
require_once 'crm_core.php';
$crm = new MobilizaCRM();

// Criar/atualizar contato
$crm->criarOuAtualizarContato($dados, 'origem');

// Registrar ligação
$ligacao_id = $crm->registrarLigacao($dados);

// Buscar contatos
$contatos = $crm->buscarContatos($filtros, $limite, $offset);

// Buscar contato completo
$contato = $crm->buscarContato($id);

// Atribuir a atendente
$crm->atribuirContato($contato_id, $atendente_id);

// Atualizar status
$crm->atualizarStatusContato($contato_id, $novo_status);

// Enriquecer dados
$crm->enriquecerContato($contato_id, $dados);

// Estatísticas
$stats = $crm->estatisticasGerais();
$ranking = $crm->rankingAtendentes(30);
```

### Sincronização Automática

Adicione ao cron para sincronização automática diária:

```bash
# Sincronizar contatos às 3h da manhã
0 3 * * * php /caminho/mobiliza/admin/crm/sincronizar.php
```

---

## 🎨 Personalização

### Campos Personalizados

Para adicionar campos ao contato, modifique:

1. `crm_install.php` - Adicione coluna na tabela
2. `crm_core.php` - Inclua no array de campos permitidos
3. `admin/crm/contato.php` - Adicione campo no formulário

### Status Personalizados

Edite o ENUM em `crm_install.php`:

```sql
status_contato ENUM(
    'novo',
    'seu_status_aqui',
    ...
) DEFAULT 'novo'
```

---

## 📞 Recursos Avançados

### 1. Segmentação Inteligente

Filtre contatos por múltiplos critérios:
- Score de engajamento
- Número de interações
- Última data de contato
- Cidade/região
- Status e prioridade

### 2. Histórico Completo

Cada contato tem registro de:
- Todas as ligações (com duração e resultado)
- Eventos participados (com check-in)
- Petições assinadas
- Grupos WhatsApp ativos
- Mudanças de status
- Atualizações de dados

### 3. Automações

- Cálculo automático de score
- Sincronização de dados
- Atualização de estatísticas
- Notificações de retorno

### 4. Exportação

Exporte dados completos em CSV:
- Lista de contatos
- Histórico de ligações
- Relatório por atendente
- Performance geral

---

## 🔒 Segurança e Privacidade

### Dados Protegidos

- Todas as senhas são hash
eadas
- Sessões seguras com tokens
- Prepared statements contra SQL Injection
- Proteção CSRF em formulários
- Logs de auditoria

### LGPD Compliance

O CRM armazena apenas dados necessários:
- Dados de contato são consentidos (inscrição em evento/petição)
- Possibilidade de exportar dados do contato
- Possibilidade de excluir contato (soft delete)

---

## 📚 FAQ

**Q: Posso importar contatos de uma planilha?**
A: Não diretamente ainda, mas você pode inserir via SQL ou adaptar o script de sincronização.

**Q: Quantos atendentes posso ter?**
A: Ilimitados. O sistema é escalável.

**Q: Como faço backup dos dados?**
A: Use o sistema de backup do Mobiliza+ ou exporte via phpMyAdmin.

**Q: Posso integrar com telefonia VoIP?**
A: Sim, via customização. O campo `arquivo_gravacao` permite armazenar gravações.

**Q: Como resetar o score de um contato?**
A: Execute: `UPDATE crm_contatos SET score_engajamento = 0 WHERE id = X`

**Q: Posso ter múltiplos níveis de atendentes?**
A: Sim, customize o campo `especialidades` para criar hierarquias.

---

## 🆘 Suporte

### Problemas Comuns

**CRM não aparece no menu:**
- Execute `crm_install.php` primeiro
- Verifique permissões do banco de dados

**Sincronização não funciona:**
- Verifique se há contatos em eventos/petições
- Veja logs de erro do PHP

**Score não atualiza:**
- Execute: `UPDATE crm_contatos SET score_engajamento = (total_eventos * 10) + (total_peticoes * 5) + (total_grupos * 15)`

**Estatísticas incorretas:**
- Re-execute sincronização
- Limpe cache: `DELETE FROM cache WHERE chave LIKE 'crm_%'`

---

## 🚀 Roadmap Futuro

- [ ] App mobile para atendentes
- [ ] Integração com WhatsApp Business API
- [ ] Envio automático de mensagens
- [ ] Análise de sentimento em conversas
- [ ] Funil de vendas visual
- [ ] Gamificação para atendentes
- [ ] Integração com telefonia VoIP
- [ ] IA para sugestão de próximos passos

---

## 📄 Licença

Este CRM faz parte do projeto Mobiliza+ e segue a mesma licença do projeto principal.

**Versão:** 1.0.0
**Data:** 2025-10-26
**Autor:** Claude AI Assistant

---

## 🎉 Conclusão

O CRM Mobiliza+ transforma a forma como você gerencia seus contatos, permitindo:
- ✅ Relacionamento profissional e organizado
- ✅ Acompanhamento detalhado de cada pessoa
- ✅ Métricas claras de desempenho
- ✅ Conversão efetiva de contatos em ações
- ✅ Gestão de equipe de atendentes

**Comece agora e leve sua mobilização para o próximo nível!** 🚀
