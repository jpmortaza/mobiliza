# Verificador de Links do WhatsApp

Um verificador robusto em PHP para validar e testar links do WhatsApp.

## Funcionalidades

- ✅ Valida se uma URL é um link válido do WhatsApp
- ✅ Verifica se o link está ativo (faz requisição HTTP)
- ✅ Suporta diferentes formatos de links do WhatsApp:
  - `wa.me` (links diretos)
  - `api.whatsapp.com/send` (API do WhatsApp)
  - `chat.whatsapp.com` (links de grupos)
- ✅ Extrai números de telefone dos links
- ✅ Extrai códigos de grupos
- ✅ Verifica múltiplos links de uma vez
- ✅ Fornece estatísticas das verificações
- ✅ Mede tempo de resposta

## Requisitos

- PHP 7.4 ou superior
- Extensão cURL habilitada

## Instalação

Basta incluir o arquivo `WhatsAppLinkValidator.php` no seu projeto:

```php
require_once 'WhatsAppLinkValidator.php';
```

## Uso Básico

### Verificar um único link

```php
<?php
require_once 'WhatsAppLinkValidator.php';

$validator = new WhatsAppLinkValidator();

$result = $validator->checkLink('https://wa.me/5511999999999');

if ($result['active']) {
    echo "Link está ativo!";
} else {
    echo "Link não está ativo.";
}
```

### Verificar múltiplos links

```php
$links = [
    'https://wa.me/5511999999999',
    'https://wa.me/5521988888888',
    'https://chat.whatsapp.com/AbCdEfGhIjKl',
];

$results = $validator->checkMultipleLinks($links);

foreach ($results as $result) {
    echo "URL: {$result['url']}\n";
    echo "Ativo: " . ($result['active'] ? 'Sim' : 'Não') . "\n";
    echo "HTTP Code: {$result['http_code']}\n\n";
}
```

### Validar formato sem fazer requisição

```php
$url = 'https://wa.me/5511999999999';

if ($validator->isValidWhatsAppUrl($url)) {
    echo "URL válida do WhatsApp";
} else {
    echo "URL inválida";
}
```

### Extrair informações dos links

```php
// Extrair número de telefone
$phone = $validator->extractPhoneNumber('https://wa.me/5511999999999');
echo "Telefone: " . $phone; // Saída: 5511999999999

// Extrair código do grupo
$groupCode = $validator->extractGroupCode('https://chat.whatsapp.com/AbCdEfGhIjKl');
echo "Código: " . $groupCode; // Saída: AbCdEfGhIjKl
```

### Obter estatísticas

```php
$results = $validator->checkMultipleLinks($links);
$stats = $validator->getStatistics($results);

echo "Total: {$stats['total']}\n";
echo "Ativos: {$stats['active']}\n";
echo "Inativos: {$stats['inactive']}\n";
echo "Taxa de sucesso: {$stats['success_rate']}\n";
```

## Configuração

### Timeout personalizado

```php
// Timeout de 15 segundos
$validator = new WhatsAppLinkValidator(timeout: 15);
```

### User Agent personalizado

```php
$validator = new WhatsAppLinkValidator(
    timeout: 10,
    userAgent: 'MeuBot/1.0'
);
```

## Resposta da Verificação

O método `checkLink()` retorna um array com as seguintes informações:

```php
[
    'valid' => true,              // Se é um link válido do WhatsApp
    'active' => true,             // Se o link está ativo
    'url' => 'https://...',       // URL original
    'effective_url' => 'https://...',  // URL final após redirecionamentos
    'http_code' => 200,           // Código HTTP da resposta
    'response_time' => '150ms',   // Tempo de resposta
    'error' => null,              // Mensagem de erro (se houver)
    'checked_at' => '2025-10-26 10:30:00', // Data/hora da verificação
]
```

## Exemplo Completo

Execute o arquivo `example.php` para ver todos os exemplos em ação:

```bash
php example.php
```

## Formatos de Links Suportados

- `https://wa.me/5511999999999`
- `https://wa.me/5511999999999?text=Olá`
- `https://api.whatsapp.com/send?phone=5511999999999`
- `https://api.whatsapp.com/send?phone=5511999999999&text=Olá`
- `https://chat.whatsapp.com/AbCdEfGhIjKlMnOpQrSt`

## Observações

- A verificação de links de grupos pode retornar diferentes resultados dependendo se o grupo ainda está ativo ou foi deletado
- Links do tipo `wa.me` sempre retornam como ativos se o formato estiver correto, pois são links genéricos do WhatsApp
- O tempo de resposta pode variar dependendo da sua conexão e da localização dos servidores do WhatsApp
- Certifique-se de que a extensão cURL está habilitada no seu PHP

## Licença

Este projeto está disponível para uso livre.
