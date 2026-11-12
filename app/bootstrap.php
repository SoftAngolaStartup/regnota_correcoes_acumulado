<?php
/**
 * ============================================================
 * app/bootstrap.php
 * Ponto de Arranque da Aplicação
 * ============================================================
 *
 * Inclua este ficheiro no topo de TODAS as páginas PHP.
 * Responsabilidades:
 *  - Definir APP_DIR (raiz do projecto)
 *  - Carregar configuração
 *  - Registar autoloader PSR-4
 *  - Iniciar sessão segura
 *  - Registar tratamento de erros
 */

// Raiz do projecto (pasta onde está app/, config/, public/, etc.)
if (!defined('APP_DIR')) {
    define('APP_DIR', dirname(__DIR__));
}

// Carregar configuração central
require_once APP_DIR . '/config/configuracao.php';

// ── Autoloader PSR-4 simples (sem Composer) ────────────────
// Namespace App\ → app/
// Namespace App\Models\ → app/Models/
// Namespace App\Controllers\ → app/Controllers/
// etc.
spl_autoload_register(function (string $classe): void {
    // Prefixo de namespace suportado
    $prefixo = 'App\\';
    $base    = APP_DIR . '/app/';

    if (strncmp($classe, $prefixo, strlen($prefixo)) !== 0) {
        return; // Não é do nosso namespace
    }

    // Converter namespace em caminho de ficheiro
    $relativo = substr($classe, strlen($prefixo));
    $ficheiro = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relativo) . '.php';

    if (file_exists($ficheiro)) {
        require_once $ficheiro;
    }
});

// ── Carregar classes base obrigatórias ─────────────────────
require_once APP_DIR . '/app/Helpers/Bd.php';
require_once APP_DIR . '/app/Helpers/Sessao.php';
require_once APP_DIR . '/app/Helpers/Url.php';
require_once APP_DIR . '/app/Helpers/Resposta.php';
require_once APP_DIR . '/app/Helpers/Auditoria.php';
require_once APP_DIR . '/app/Models/ModeloBase.php';
require_once APP_DIR . '/app/Models/Modelos.php';

// ── Iniciar sessão segura ──────────────────────────────────
\App\Helpers\Sessao::iniciar();

// ── Verificação de Manutenção ──────────────────────────────
if (MANUTENCAO_ATIVA) {
    $script = $_SERVER['SCRIPT_NAME'];
    // Exceções: página de manutenção, login e qualquer coisa na pasta 'assets'
    $isExcecao = (strpos($script, 'manutencao.php') !== false) || 
                 (strpos($script, 'login.php') !== false) ||
                 (strpos($script, '/assets/') !== false);

    if (!$isExcecao) {
        $isAdmin = (\App\Helpers\Sessao::autenticado() && \App\Helpers\Sessao::temPerfil('admin'));
        if (!$isAdmin) {
            header('Location: ' . \App\Helpers\Url::publico('manutencao.php'));
            exit;
        }
    }
}

// ── Tratamento global de erros não capturados ──────────────
set_exception_handler(function (\Throwable $e): void {
    $mensagem = '[' . date('Y-m-d H:i:s') . '] ERRO: ' . $e->getMessage()
        . ' em ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
        . $e->getTraceAsString() . PHP_EOL;

    // Gravar sempre em log
    @file_put_contents(LOG_DIR . '/erros_php.log', $mensagem, FILE_APPEND);

    if (APP_AMBIENTE === 'desenvolvimento') {
        echo '<pre style="background:#fee;padding:20px;border:2px solid red;font-family:monospace">';
        echo '<strong>Erro não capturado:</strong>' . "\n";
        echo htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        http_response_code(500);
        echo '<p>Ocorreu um erro interno. Por favor tente novamente mais tarde.</p>';
    }
    exit(1);
});
