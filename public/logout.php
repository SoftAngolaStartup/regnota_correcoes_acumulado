<?php
/**
 * public/logout.php
 * Encerrar Sessão
 */
require_once __DIR__ . '/../app/bootstrap.php';
use App\Helpers\Sessao;
use App\Helpers\Url;
use App\Helpers\Auditoria;

if (Sessao::autenticado()) {
    Auditoria::registar('logout', 'usuarios', Sessao::utilizador()['id'], 'Sessão terminada');
}
Sessao::destruir();
Url::redirecionar(Url::publico('login.php'));
