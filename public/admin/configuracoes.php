<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar};
use App\Middleware\Autenticacao;

Autenticacao::exigirAdmin();

$tituloPagina = 'Configurações';
$paginaActiva = 'configuracoes';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-6">
        <div class="glass-card">
            <div class="glass-header">
                <h2 class="text-sm font-bold text-gray-900">Configurações do Sistema</h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="rotulo-campo">Nome da Aplicação</label>
                        <input type="text" class="campo-input" value="<?= APP_NOME ?>">
                    </div>
                    <button type="submit" class="btn-base btn-primario">Guardar Alterações</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="glass-card">
        <div class="glass-header">
            <h2 class="text-sm font-bold text-gray-900">Ações Rápidas</h2>
        </div>
        <div class="p-6 space-y-2">
            <a href="#" class="block text-sm text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-blue-50">Limpar Cache</a>
            <a href="#" class="block text-sm text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-blue-50">Sincronizar Banco</a>
        </div>
    </div>
</div>

<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
