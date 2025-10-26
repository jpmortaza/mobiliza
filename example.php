<?php

require_once 'WhatsAppLinkValidator.php';

// Inicializa o validador
$validator = new WhatsAppLinkValidator(timeout: 10);

echo "=== VERIFICADOR DE LINKS DO WHATSAPP ===\n\n";

// Exemplo 1: Verificar um único link
echo "1. Verificando um único link:\n";
echo "--------------------------------\n";

$link = "https://wa.me/5511999999999";
$result = $validator->checkLink($link);

echo "URL: " . $result['url'] . "\n";
echo "Válido: " . ($result['valid'] ? 'Sim' : 'Não') . "\n";
echo "Ativo: " . ($result['active'] ? 'Sim' : 'Não') . "\n";
echo "HTTP Code: " . $result['http_code'] . "\n";
echo "Tempo de resposta: " . $result['response_time'] . "\n";

if ($result['error']) {
    echo "Erro: " . $result['error'] . "\n";
}

// Extrair número de telefone
$phone = $validator->extractPhoneNumber($link);
if ($phone) {
    echo "Número extraído: " . $phone . "\n";
}

echo "\n";

// Exemplo 2: Verificar múltiplos links
echo "2. Verificando múltiplos links:\n";
echo "--------------------------------\n";

$links = [
    "https://wa.me/5511999999999",
    "https://wa.me/5521988888888",
    "https://api.whatsapp.com/send?phone=5511977777777",
    "https://chat.whatsapp.com/AbCdEfGhIjKlMnOpQrSt",
    "https://www.google.com", // Link inválido para teste
];

$results = $validator->checkMultipleLinks($links);

foreach ($results as $index => $result) {
    echo "\nLink #" . ($index + 1) . ":\n";
    echo "  URL: " . $result['url'] . "\n";
    echo "  Válido: " . ($result['valid'] ? 'Sim' : 'Não') . "\n";
    echo "  Ativo: " . ($result['active'] ? 'Sim' : 'Não') . "\n";

    if ($result['valid']) {
        echo "  HTTP Code: " . $result['http_code'] . "\n";
        echo "  Tempo: " . $result['response_time'] . "\n";
    } else {
        echo "  Erro: " . $result['error'] . "\n";
    }
}

echo "\n";

// Exemplo 3: Estatísticas
echo "3. Estatísticas das verificações:\n";
echo "--------------------------------\n";

$stats = $validator->getStatistics($results);
echo "Total de links: " . $stats['total'] . "\n";
echo "Links ativos: " . $stats['active'] . "\n";
echo "Links inativos: " . $stats['inactive'] . "\n";
echo "Links inválidos: " . $stats['invalid'] . "\n";
echo "Taxa de sucesso: " . $stats['success_rate'] . "\n";

echo "\n";

// Exemplo 4: Verificar link de grupo
echo "4. Verificando link de grupo:\n";
echo "--------------------------------\n";

$groupLink = "https://chat.whatsapp.com/AbCdEfGhIjKlMnOpQrSt";
$groupResult = $validator->checkLink($groupLink);

echo "URL: " . $groupResult['url'] . "\n";
echo "Ativo: " . ($groupResult['active'] ? 'Sim' : 'Não') . "\n";

$groupCode = $validator->extractGroupCode($groupLink);
if ($groupCode) {
    echo "Código do grupo: " . $groupCode . "\n";
}

echo "\n";

// Exemplo 5: Validar formato sem fazer requisição
echo "5. Validar apenas o formato (sem requisição HTTP):\n";
echo "--------------------------------\n";

$urlsToValidate = [
    "https://wa.me/5511999999999",
    "https://chat.whatsapp.com/invite123",
    "https://www.facebook.com",
    "https://api.whatsapp.com/send?phone=5511988888888",
];

foreach ($urlsToValidate as $url) {
    $isValid = $validator->isValidWhatsAppUrl($url);
    echo $url . " - " . ($isValid ? "✓ Válido" : "✗ Inválido") . "\n";
}

echo "\n=== FIM ===\n";
