<?php
/**
 * ============================================================
 * config/configuracao.php
 * Configuração Central do Regnotas v3.0
 * ============================================================
 *
 * ATENÇÃO: Edite este ficheiro antes do deploy.
 * Nunca exponha este ficheiro publicamente.
 */

// ── Configuração Local (Overrides) ──────────────────────────
// Use este ficheiro para substituir configurações em produção.
// Ex: Crie 'config/config_local.php' com define('BD_SENHA', 'senha_real');
if (file_exists(APP_DIR . '/config/config_local.php')) {
    require_once APP_DIR . '/config/config_local.php';
}

// ── Base de Dados (Defaults) ──────────────────────────────────────────
if (!defined('BD_HOST'))    define('BD_HOST',     'localhost');
if (!defined('BD_PORTA'))   define('BD_PORTA',    '3306');
if (!defined('BD_NOME'))    define('BD_NOME',     'regnotas');
if (!defined('BD_USUARIO')) define('BD_USUARIO',  'root');
if (!defined('BD_SENHA'))   define('BD_SENHA',    '');
if (!defined('BD_CHARSET')) define('BD_CHARSET',  'utf8mb4');

// ── Aplicação ──────────────────────────────────────────────
define('APP_NOME',    'Regnotas');
define('APP_TITULO',  'Sistema de Gestão de Ocorrências de Notas');
define('APP_VERSAO',  '3.0.0');

// URL base SEM barra no final.
if (!defined('APP_URL')) define('APP_URL', '');

// ── Ambiente ───────────────────────────────────────────────
// Por defeito: desenvolvimento só em localhost/CLI; qualquer outro host é produção.
if (!defined('APP_AMBIENTE')) {
    $__host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]);
    define('APP_AMBIENTE', (PHP_SAPI === 'cli' || in_array($__host, ['localhost', '127.0.0.1', '::1'], true))
        ? 'desenvolvimento' : 'producao');
    unset($__host);
}

// ── Segurança ──────────────────────────────────────────────
if (!defined('APP_SEGREDO')) define('APP_SEGREDO', 'regnotas-chave-secreta-unica-mude-antes-do-deploy-2024');

if (!defined('SESSAO_DURACAO')) define('SESSAO_DURACAO', 14400);
if (!defined('MAX_TENTATIVAS_LOGIN')) define('MAX_TENTATIVAS_LOGIN', 5);
if (!defined('BLOQUEIO_MINUTOS')) define('BLOQUEIO_MINUTOS', 15);

// ── Manutenção ──────────────────────────────────────────
if (!defined('MANUTENCAO_ATIVA')) define('MANUTENCAO_ATIVA', false);

// ── Senhas Automáticas ─────────────────────────────────────
if (!defined('SENHA_INICIAL')) define('SENHA_INICIAL', 'RegNota@123');


// ── Paths Internos (não alterar) ───────────────────────────
// APP_DIR pode já ter sido definido no bootstrap.php
if (!defined('APP_DIR')) {
    define('APP_DIR', dirname(__DIR__));
}
define('VIEWS_DIR',    APP_DIR . '/views');
define('STORAGE_DIR',  APP_DIR . '/storage');
define('LOG_DIR',      STORAGE_DIR . '/logs');
define('UPLOAD_PASTA', STORAGE_DIR . '/uploads');

// ── Upload de Ficheiros ────────────────────────────────────
define('UPLOAD_DIR',    APP_DIR . '/storage/uploads/');
define('UPLOAD_MAX_MB', 5);
define('UPLOAD_TIPOS',  ['jpg', 'jpeg', 'png', 'webp']);

// ── Configuração do ambiente ───────────────────────────────
if (APP_AMBIENTE === 'desenvolvimento') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_DIR . '/erros_php.log');
}
