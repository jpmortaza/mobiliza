<?php
/**
 * MOBILIZA+ - Sistema de Cache Inteligente
 *
 * Implementa cache em arquivo para otimizar performance
 * e reduzir consultas ao banco de dados
 */

class MobilizaCache {

    private static $cache_dir = __DIR__ . '/cache/';
    private static $default_ttl = 3600; // 1 hora em segundos

    /**
     * Inicializa o sistema de cache
     */
    public static function init() {
        if (!file_exists(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
            // Proteger o diretório
            file_put_contents(self::$cache_dir . '.htaccess', 'Deny from all');
        }
    }

    /**
     * Armazena um valor no cache
     */
    public static function set($key, $value, $ttl = null) {
        self::init();

        $ttl = $ttl ?? self::$default_ttl;
        $expiration = time() + $ttl;

        $cache_data = [
            'expiration' => $expiration,
            'value' => $value
        ];

        $cache_file = self::getCacheFilePath($key);
        return file_put_contents($cache_file, serialize($cache_data)) !== false;
    }

    /**
     * Recupera um valor do cache
     */
    public static function get($key, $default = null) {
        $cache_file = self::getCacheFilePath($key);

        if (!file_exists($cache_file)) {
            return $default;
        }

        $cache_data = unserialize(file_get_contents($cache_file));

        // Verificar se expirou
        if ($cache_data['expiration'] < time()) {
            self::delete($key);
            return $default;
        }

        return $cache_data['value'];
    }

    /**
     * Verifica se uma chave existe no cache e está válida
     */
    public static function has($key) {
        $cache_file = self::getCacheFilePath($key);

        if (!file_exists($cache_file)) {
            return false;
        }

        $cache_data = unserialize(file_get_contents($cache_file));
        return $cache_data['expiration'] >= time();
    }

    /**
     * Remove um item do cache
     */
    public static function delete($key) {
        $cache_file = self::getCacheFilePath($key);

        if (file_exists($cache_file)) {
            return unlink($cache_file);
        }

        return true;
    }

    /**
     * Limpa todo o cache ou por padrão de chave
     */
    public static function clear($pattern = null) {
        self::init();

        $files = glob(self::$cache_dir . '*.cache');

        if ($pattern) {
            $pattern_hash = md5($pattern);
            $files = array_filter($files, function($file) use ($pattern_hash) {
                return strpos(basename($file), $pattern_hash) !== false;
            });
        }

        $deleted = 0;
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Limpa cache expirado
     */
    public static function clearExpired() {
        self::init();

        $files = glob(self::$cache_dir . '*.cache');
        $deleted = 0;

        foreach ($files as $file) {
            $cache_data = unserialize(file_get_contents($file));
            if ($cache_data['expiration'] < time()) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Cache com callback (remember pattern)
     */
    public static function remember($key, $ttl, $callback) {
        if (self::has($key)) {
            return self::get($key);
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * Gera o caminho do arquivo de cache
     */
    private static function getCacheFilePath($key) {
        $hash = md5($key);
        return self::$cache_dir . $hash . '.cache';
    }

    /**
     * Retorna estatísticas do cache
     */
    public static function stats() {
        self::init();

        $files = glob(self::$cache_dir . '*.cache');
        $total = count($files);
        $size = 0;
        $expired = 0;

        foreach ($files as $file) {
            $size += filesize($file);
            $cache_data = unserialize(file_get_contents($file));
            if ($cache_data['expiration'] < time()) {
                $expired++;
            }
        }

        return [
            'total_items' => $total,
            'expired_items' => $expired,
            'active_items' => $total - $expired,
            'total_size' => $size,
            'size_formatted' => self::formatBytes($size)
        ];
    }

    /**
     * Formata bytes para leitura humana
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

/**
 * Funções auxiliares para facilitar o uso do cache
 */
function cache_set($key, $value, $ttl = null) {
    return MobilizaCache::set($key, $value, $ttl);
}

function cache_get($key, $default = null) {
    return MobilizaCache::get($key, $default);
}

function cache_remember($key, $ttl, $callback) {
    return MobilizaCache::remember($key, $ttl, $callback);
}

function cache_forget($key) {
    return MobilizaCache::delete($key);
}

function cache_flush() {
    return MobilizaCache::clear();
}
?>
