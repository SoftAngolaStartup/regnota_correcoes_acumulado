<?php
/**
 * ============================================================
 * app/Helpers/Url.php
 * Geração de URLs absolutas e relativas
 * ============================================================
 */

namespace App\Helpers;

class Url
{
    /**
     * Retorna a URL base do projecto.
     * Usa APP_URL se definido, caso contrário auto-detecta via DOCUMENT_ROOT.
     */
    public static function base(string $caminho = ''): string
    {
        $configurada = rtrim(APP_URL, '/');

        if ($configurada) {
            return $configurada . ($caminho ? '/' . ltrim($caminho, '/') : '');
        }

        // Auto-detecção via DOCUMENT_ROOT
        $esquema  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $docRoot  = rtrim(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '', DIRECTORY_SEPARATOR);
        $raiz     = rtrim(APP_DIR, DIRECTORY_SEPARATOR);

        $subDir = '';
        if ($docRoot && strpos($raiz, $docRoot) === 0) {
            $subDir = str_replace(DIRECTORY_SEPARATOR, '/', substr($raiz, strlen($docRoot)));
        }

        $base = $esquema . '://' . $host . rtrim($subDir, '/');
        return $base . ($caminho ? '/' . ltrim($caminho, '/') : '');
    }

    /** URL do portal público (estudantes) */
    public static function publico(string $caminho = ''): string
    {
        return self::base('public/' . ltrim($caminho, '/'));
    }

    /** URL do painel do admin */
    public static function admin(string $caminho = ''): string
    {
        return self::base('public/admin/' . ltrim($caminho, '/'));
    }

    /** URL do painel do coordenador */
    public static function coordenador(string $caminho = ''): string
    {
        return self::base('public/coordenador/' . ltrim($caminho, '/'));
    }

    /** URL do painel do professor */
    public static function professor(string $caminho = ''): string
    {
        return self::base('public/professor/' . ltrim($caminho, '/'));
    }

    /** URL de assets (CSS, JS, imagens) com cache busting */
    public static function asset(string $caminho = ''): string
    {
        $url = self::base('public/assets/' . ltrim($caminho, '/'));
        
        // Adicionar cache busting via timestamp do ficheiro
        $ficheiroAbsoluto = APP_DIR . '/public/assets/' . ltrim($caminho, '/');
        $timestamp = file_exists($ficheiroAbsoluto) ? filemtime($ficheiroAbsoluto) : time();
        
        return $url . '?v=' . $timestamp;
    }

    /**
     * Redireciona para uma URL e encerra a execução.
     */
    public static function redirecionar(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
