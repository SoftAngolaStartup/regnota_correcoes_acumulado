<?php
/**
 * public/admin/auditoria.php
 * Registo de Auditoria do Sistema
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Formatar};
use App\Models\AuditoriaModelo;
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$am  = new AuditoriaModelo();
$logs = $am->listarRecentes(100);
$menuLateral  = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Auditoria'; $paginaActiva = 'auditoria';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-funnel"></i> Filtros</span>
  </div>
  <div class="card-conteudo-corpo">
    <div class="row g-3">
        <div class="col-sm-6 col-md-4">
            <input type="text" class="campo-input" placeholder="Pesquisar logs...">
        </div>
        <div class="col-sm-6 col-md-2">
            <button class="btn-base btn-primario w-100">Filtrar</button>
        </div>
    </div>
  </div>
</div>

<div class="glass-card">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-journal-text"></i> Registo de Auditoria
      <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($logs) ?> (últimos 100)</span>
    </span>
  </div>
  <div class="card-conteudo-corpo" style="padding: 0;">
    <div style="overflow-x:auto;max-height:700px;overflow-y:auto">
      <table class="tabela-sistema">
        <thead style="position:sticky;top:0;z-index:1">
          <tr><th>Data/Hora</th><th>Utilizador</th><th>Acção</th><th>Tabela</th><th>ID</th><th>IP</th><th>Detalhe</th></tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
          <tr><td colspan="7"><div class="sem-dados"><i class="bi bi-journal"></i><p>Nenhum registo de auditoria.</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($logs as $l): ?>
          <tr>
            <td class="td-muted" style="white-space:nowrap"><?= Formatar::dataHora($l['criado_em']) ?></td>
            <td><?= $l['usuario_nome'] ? Sanitizar::html($l['usuario_nome']) : '<em class="td-muted">Sistema</em>' ?></td>
            <td><code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:11px"><?= Sanitizar::html($l['acao']) ?></code></td>
            <td class="td-muted"><?= $l['tabela'] ? Sanitizar::html($l['tabela']) : '—' ?></td>
            <td class="td-muted"><?= $l['registo_id'] ?? '—' ?></td>
            <td class="td-muted" style="font-size:11px"><?= Sanitizar::html($l['ip'] ?? '—') ?></td>
            <td class="td-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Sanitizar::html($l['detalhe'] ?? '') ?>">
              <?= $l['detalhe'] ? Sanitizar::html(mb_substr($l['detalhe'], 0, 60)) . (mb_strlen($l['detalhe']) > 60 ? '...' : '') : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
