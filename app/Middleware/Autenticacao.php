<?php
/**
 * ============================================================
 * app/Middleware/Autenticacao.php
 * Middleware de Autenticação e Autorização
 * ============================================================
 *
 * Protege rotas exigindo sessão válida e perfil adequado.
 * Regista tentativas de acesso não autorizado na auditoria.
 */

namespace App\Middleware;

use App\Helpers\Sessao;
use App\Helpers\Url;
use App\Helpers\Auditoria;

class Autenticacao
{
    /**
     * Exige utilizador autenticado.
     * Redireciona para login se a sessão não for válida.
     */
    public static function exigir(): void
    {
        if (!Sessao::autenticado()) {
            Auditoria::registar('acesso_nao_autorizado', null, null,
                'Tentativa de acesso sem sessão: ' . ($_SERVER['REQUEST_URI'] ?? ''));
            Url::redirecionar(Url::publico('login.php'));
        }
    }

    /**
     * Exige perfil(is) específico(s).
     * Se autenticado mas sem perfil adequado, redireciona para o painel correcto.
     *
     * @param string|array $perfis Perfil(is) permitido(s)
     */
    public static function exigirPerfil(string|array $perfis): void
    {
        self::exigir();

        $lista        = is_array($perfis) ? $perfis : [$perfis];
        $perfilActual = Sessao::perfil();

        if (!in_array($perfilActual, $lista, true)) {
            Auditoria::registar('acesso_negado', null, null,
                "Perfil '$perfilActual' tentou aceder a área restrita: " . ($_SERVER['REQUEST_URI'] ?? ''));
            Sessao::flash('erro', 'Não tem permissão para aceder a esta área.');
            self::redirecionarPorPerfil($perfilActual);
        }

        // Primeiro login: forçar alteração da senha em todas as áreas protegidas
        self::verificarPrimeiroLogin();
    }

    /**
     * Exige que seja Super Administrador.
     */
    public static function exigirAdmin(): void
    {
        self::exigirPerfil('super_admin');
    }

    /**
     * Exige que seja Coordenador.
     */
    public static function exigirCoordenador(): void
    {
        self::exigirPerfil(['super_admin', 'coordenador']);
    }

    /**
     * Exige que seja Professor.
     */
    public static function exigirProfessor(): void
    {
        self::exigirPerfil('professor');
    }

    /**
     * Verifica se o utilizador autenticado deve alterar a senha
     * (primeiro login). Redireciona se necessário.
     */
    public static function verificarPrimeiroLogin(): void
    {
        if (!Sessao::autenticado() || empty($_SESSION['primeiro_login'])) return;

        $caminho = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (basename($caminho) !== 'alterar-senha.php') {
            Url::redirecionar(Url::publico('alterar-senha.php'));
        }
    }

    /**
     * Redireciona para o painel correspondente ao perfil do utilizador.
     */
    public static function redirecionarPorPerfil(string $perfil = ''): never
    {
        if (!$perfil) {
            $perfil = Sessao::perfil();
        }
        $destino = match($perfil) {
            'super_admin'  => Url::admin('index.php'),
            'coordenador'  => Url::coordenador('index.php'),
            'professor'    => Url::professor('index.php'),
            default        => Url::publico('login.php'),
        };
        Url::redirecionar($destino);
    }
}
