<?php
/**
 * ============================================================
 * app/Helpers/Sessao.php
 * Gestão de Sessão, Autenticação, CSRF e Flash Messages
 * ============================================================
 */

namespace App\Helpers;

class Sessao
{
    /**
     * Inicia a sessão com configurações de segurança recomendadas.
     * Chamado uma vez pelo bootstrap.php.
     */
    public static function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return; // Já iniciada
        }

        // Configurações de segurança
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        // Sessão expira ao fechar o navegador
        ini_set('session.cookie_lifetime', 0);
        // Define o tempo máximo de vida da sessão no servidor
        ini_set('session.gc_maxlifetime', SESSAO_DURACAO);

        // Em HTTPS activar cookie seguro
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', 1);
        }

        session_start();

        // Regenerar ID na primeira vez (anti session fixation)
        if (!isset($_SESSION['_iniciada'])) {
            session_regenerate_id(true);
            $_SESSION['_iniciada']      = true;
            $_SESSION['_ultima_activ']  = time();
        }

        // Verificar expiração da sessão
        if (isset($_SESSION['_ultima_activ'])) {
            if (time() - $_SESSION['_ultima_activ'] > SESSAO_DURACAO) {
                self::destruir();
                return;
            }
            $_SESSION['_ultima_activ'] = time();
        }
    }

    // ── Autenticação ────────────────────────────────────────

    /** Verifica se há um utilizador autenticado */
    public static function autenticado(): bool
    {
        return !empty($_SESSION['utilizador_id']);
    }

    /** Redireciona para login se não autenticado */
    public static function exigirLogin(string $redirecionar = ''): void
    {
        if (!self::autenticado()) {
            $url = Url::base('public/login.php');
            if ($redirecionar) {
                $url .= '?rd=' . urlencode($redirecionar);
            }
            header('Location: ' . $url);
            exit;
        }
    }

    /** Redireciona se não tiver o perfil exigido */
    public static function exigirPerfil(string|array $perfis): void
    {
        self::exigirLogin();
        $perfilActual = $_SESSION['utilizador_perfil'] ?? '';
        $lista        = is_array($perfis) ? $perfis : [$perfis];

        if (!in_array($perfilActual, $lista, true)) {
            self::flash('erro', 'Não tem permissão para aceder a esta área.');
            header('Location: ' . Url::base('public/login.php'));
            exit;
        }
    }

    /** Inicia sessão após login bem-sucedido */
    public static function iniciarLogin(array $utilizador): void
    {
        session_regenerate_id(true);
        $_SESSION['utilizador_id']      = $utilizador['id'];
        $_SESSION['utilizador_nome']    = $utilizador['nome'];
        $_SESSION['utilizador_email']   = $utilizador['email'];
        $_SESSION['utilizador_perfil']  = $utilizador['perfil'];
        $_SESSION['_ultima_activ']      = time();
        $_SESSION['_iniciada']          = true;
    }

    /** Dados do utilizador autenticado */
    public static function utilizador(): array
    {
        return [
            'id'     => $_SESSION['utilizador_id']     ?? 0,
            'nome'   => $_SESSION['utilizador_nome']   ?? '',
            'email'  => $_SESSION['utilizador_email']  ?? '',
            'perfil' => $_SESSION['utilizador_perfil'] ?? '',
        ];
    }

    /** Perfil do utilizador autenticado */
    public static function perfil(): string
    {
        return $_SESSION['utilizador_perfil'] ?? '';
    }

    /** Verifica se o utilizador tem um determinado perfil */
    public static function temPerfil(string|array $perfis): bool
    {
        $lista = is_array($perfis) ? $perfis : [$perfis];
        return in_array(self::perfil(), $lista, true);
    }

    /** Termina a sessão completamente */
    public static function destruir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // ── CSRF ────────────────────────────────────────────────

    /** Gera ou obtém o token CSRF da sessão */
    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** Verifica o token CSRF (comparação segura) */
    public static function verificarCsrf(string $token): bool
    {
        return !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    /** Retorna campo HTML hidden com token CSRF */
    public static function campoCsrf(): string
    {
        return '<input type="hidden" name="_token" value="'
            . self::tokenCsrf() . '">';
    }

    // ── Flash Messages ──────────────────────────────────────

    /** Define uma mensagem flash para exibir na próxima requisição */
    public static function flash(string $tipo, string $mensagem): void
    {
        $_SESSION['_flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    /** Consome e retorna a mensagem flash (ou null se não houver) */
    public static function obterFlash(): ?array
    {
        if (!isset($_SESSION['_flash'])) {
            return null;
        }
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $flash;
    }
}
