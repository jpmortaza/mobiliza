<?php

/**
 * WhatsApp Link Validator
 * Verifica se links do WhatsApp estão ativos e válidos
 */
class WhatsAppLinkValidator
{
    /**
     * Padrões de URLs do WhatsApp
     */
    private const WHATSAPP_PATTERNS = [
        '/^https?:\/\/(www\.)?wa\.me\/[0-9]+/',
        '/^https?:\/\/(www\.)?api\.whatsapp\.com\/send/',
        '/^https?:\/\/(www\.)?chat\.whatsapp\.com\/[a-zA-Z0-9]+/',
    ];

    /**
     * Timeout para requisições em segundos
     */
    private int $timeout;

    /**
     * User Agent para as requisições
     */
    private string $userAgent;

    /**
     * Construtor
     *
     * @param int $timeout Timeout em segundos (padrão: 10)
     * @param string $userAgent User Agent personalizado
     */
    public function __construct(int $timeout = 10, string $userAgent = 'WhatsApp Link Validator/1.0')
    {
        $this->timeout = $timeout;
        $this->userAgent = $userAgent;
    }

    /**
     * Valida se a URL é um link do WhatsApp
     *
     * @param string $url URL para validar
     * @return bool
     */
    public function isValidWhatsAppUrl(string $url): bool
    {
        foreach (self::WHATSAPP_PATTERNS as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se o link do WhatsApp está ativo
     *
     * @param string $url URL do WhatsApp para verificar
     * @return array Array com informações sobre o status do link
     */
    public function checkLink(string $url): array
    {
        // Valida se é um link do WhatsApp
        if (!$this->isValidWhatsAppUrl($url)) {
            return [
                'valid' => false,
                'active' => false,
                'url' => $url,
                'error' => 'URL não é um link válido do WhatsApp',
                'http_code' => null,
                'response_time' => null,
            ];
        }

        // Faz a requisição HTTP
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
        ]);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // em milissegundos

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        // Determina se o link está ativo
        $isActive = ($httpCode >= 200 && $httpCode < 400) && empty($error);

        return [
            'valid' => true,
            'active' => $isActive,
            'url' => $url,
            'effective_url' => $effectiveUrl,
            'http_code' => $httpCode,
            'response_time' => $responseTime . 'ms',
            'error' => $error ?: null,
            'checked_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Verifica múltiplos links do WhatsApp
     *
     * @param array $urls Array de URLs para verificar
     * @return array Array com resultados de cada verificação
     */
    public function checkMultipleLinks(array $urls): array
    {
        $results = [];

        foreach ($urls as $url) {
            $results[] = $this->checkLink($url);
        }

        return $results;
    }

    /**
     * Extrai o número de telefone de um link wa.me
     *
     * @param string $url URL do WhatsApp
     * @return string|null Número de telefone ou null
     */
    public function extractPhoneNumber(string $url): ?string
    {
        if (preg_match('/wa\.me\/([0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/phone=([0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extrai o código do grupo de um link de convite
     *
     * @param string $url URL do grupo WhatsApp
     * @return string|null Código do grupo ou null
     */
    public function extractGroupCode(string $url): ?string
    {
        if (preg_match('/chat\.whatsapp\.com\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Retorna estatísticas de múltiplas verificações
     *
     * @param array $results Resultados das verificações
     * @return array Estatísticas
     */
    public function getStatistics(array $results): array
    {
        $total = count($results);
        $active = 0;
        $inactive = 0;
        $invalid = 0;

        foreach ($results as $result) {
            if (!$result['valid']) {
                $invalid++;
            } elseif ($result['active']) {
                $active++;
            } else {
                $inactive++;
            }
        }

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'invalid' => $invalid,
            'success_rate' => $total > 0 ? round(($active / $total) * 100, 2) . '%' : '0%',
        ];
    }
}
