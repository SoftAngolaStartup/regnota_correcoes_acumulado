<?php
/**
 * public/admin/relatorios.php
 * Geração de Relatórios PDF com Filtros Avançados
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Formatar};
use App\Models\{OcorrenciaModelo, CursoModelo, SemestreModelo, TurmaModelo, DisciplinaModelo, ProfessorModelo, AnoLetivoModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$om  = new OcorrenciaModelo();
$cm  = new CursoModelo();
$sm  = new SemestreModelo();
$tm  = new TurmaModelo();
$dm  = new DisciplinaModelo();
$pm  = new ProfessorModelo();
$alm = new AnoLetivoModelo();

// Filtros
$filtros = array_filter([
    'ticket'         => Sanitizar::texto($_GET['ticket'] ?? ''),
    'estudante_nome' => Sanitizar::texto($_GET['estudante_nome'] ?? ''),
    'curso_id'       => (int)($_GET['curso_id'] ?? 0) ?: null,
    'semestre_id'    => (int)($_GET['semestre_id'] ?? 0) ?: null,
    'turma_id'       => (int)($_GET['turma_id'] ?? 0) ?: null,
    'disciplina_id'  => (int)($_GET['disciplina_id'] ?? 0) ?: null,
    'professor_id'   => (int)($_GET['professor_id'] ?? 0) ?: null,
    'tipo_avaliacao' => $_GET['tipo_avaliacao'] ?? '',
    'estado'         => $_GET['estado'] ?? '',
    'data_inicio'    => $_GET['data_inicio'] ?? '',
    'data_fim'       => $_GET['data_fim'] ?? '',
]);
$ocorrencias = $om->listarTodas($filtros);

// Gerar PDF (página de impressão)
if (isset($_GET['pdf'])) {
    $descFiltros = [];
    if (!empty($filtros['estado']))         $descFiltros[] = 'Estado: ' . Etiqueta::estado($filtros['estado']);
    if (!empty($filtros['estudante_nome'])) $descFiltros[] = 'Estudante: ' . $filtros['estudante_nome'];
    if (!empty($filtros['data_inicio']))    $descFiltros[] = 'De: ' . Formatar::data($filtros['data_inicio']);
    if (!empty($filtros['data_fim']))       $descFiltros[] = 'Até: ' . Formatar::data($filtros['data_fim']);
    ?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">
<title>Relatório – <?= APP_NOME ?></title>
<style>
@page{margin:16mm 12mm}*{box-sizing:border-box}
body{font-family:Arial,sans-serif;font-size:10.5px;color:#111}
.rh{border-bottom:3px solid #0b3d6e;padding-bottom:10px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:flex-start}
.rh-logo{background:#0b3d6e;color:#fff;font-size:20px;font-weight:900;width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:8px}
.rh-title h1{font-size:15px;color:#0b3d6e;margin:0 0 2px}.rh-title p{font-size:9px;color:#666;margin:0}
.rh-meta{font-size:9px;color:#666;text-align:right}
.filtros-bar{background:#f0f4fa;padding:6px 10px;border-radius:5px;margin-bottom:10px;font-size:9px}
.resumo{display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap}
.resumo-item{background:#f7f9fd;border:1px solid #dde5f0;border-radius:6px;padding:7px 12px;font-size:9px}
.resumo-item strong{font-size:15px;display:block;color:#0b3d6e}
table{width:100%;border-collapse:collapse;margin-bottom:14px}
thead th{background:#0b3d6e;color:#fff;padding:6px 8px;font-size:9px;text-align:left}
tbody td{padding:5px 8px;border-bottom:1px solid #e5e7eb;font-size:9.5px;vertical-align:top}
tbody tr:nth-child(even) td{background:#f9fbff}
.ticket{font-family:monospace;font-weight:700;font-size:9px}
.badge{display:inline-block;padding:1px 6px;border-radius:8px;font-size:8px;font-weight:700}
.b-submetida{background:#ede9fe;color:#6366f1}.b-em_analise{background:#fef3c7;color:#92400e}
.b-resolvida{background:#d1fae5;color:#065f46}.b-cancelada{background:#fee2e2;color:#991b1b}
.b-encerrada{background:#f3f4f6;color:#374151}.b-encaminhada_professor{background:#dbeafe;color:#1e40af}
.b-respondida_professor{background:#ede9fe;color:#5b21b6}.b-em_revisao_coordenacao{background:#ffedd5;color:#9a3412}
.rf{margin-top:14px;border-top:1px solid #ddd;padding-top:6px;font-size:8px;color:#999;display:flex;justify-content:space-between}
.print-btn{position:fixed;top:12px;right:12px;background:#0b3d6e;color:#fff;border:none;padding:8px 16px;border-radius:7px;cursor:pointer;font-size:13px;font-weight:700;z-index:999}
@media print{.print-btn{display:none}body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style></head><body>
<button class="print-btn" onclick="window.print()">🖨 Imprimir / PDF</button>
<div class="rh">
  <div style="display:flex;gap:12px;align-items:center">
    <div class="rh-logo">R</div>
    <div class="rh-title"><h1>Relatório de Ocorrências de Notas</h1><p><?= APP_TITULO ?></p></div>
  </div>
  <div class="rh-meta">Gerado: <?= date('d/m/Y H:i') ?><br>Por: <?= Sanitizar::html(Sessao::utilizador()['nome']) ?></div>
</div>
<?php if ($descFiltros): ?><div class="filtros-bar"><strong>Filtros:</strong> <?= implode(' › ', array_map('htmlspecialchars', $descFiltros)) ?></div><?php endif; ?>
<?php $byEstado = array_count_values(array_column($ocorrencias, 'estado')); ?>
<div class="resumo">
  <div class="resumo-item"><strong><?= count($ocorrencias) ?></strong>Total</div>
  <?php foreach (Etiqueta::todosEstados() as $k => $v): ?>
  <?php if (!empty($byEstado[$k])): ?><div class="resumo-item"><strong><?= $byEstado[$k] ?></strong><?= $v ?></div><?php endif; ?>
  <?php endforeach; ?>
</div>
<table><thead><tr><th>Ticket</th><th>Estudante</th><th>Nº</th><th>Curso/Turma</th><th>Disciplina</th><th>Prof.</th><th>Tipo</th><th>Data</th><th>Estado</th></tr></thead><tbody>
<?php if (empty($ocorrencias)): ?><tr><td colspan="9" style="text-align:center;padding:16px;color:#999">Nenhum registo.</td></tr>
<?php else: foreach ($ocorrencias as $o): ?>
<tr>
  <td class="ticket"><?= Sanitizar::html($o['ticket']) ?></td>
  <td style="font-weight:600"><?= Sanitizar::html($o['estudante_nome']) ?></td>
  <td style="color:#666"><?= Sanitizar::html($o['estudante_numero']) ?></td>
  <td><?= Sanitizar::html($o['curso_nome']) ?><br><small style="color:#666">Turma <?= Sanitizar::html($o['turma_codigo']) ?></small></td>
  <td><?= Sanitizar::html($o['disciplina_nome']) ?></td>
  <td style="color:#666"><?= Sanitizar::html($o['professor_nome']) ?></td>
  <td><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></td>
  <td style="white-space:nowrap"><?= Formatar::data($o['criado_em']) ?></td>
  <td><span class="badge b-<?= $o['estado'] ?>"><?= Etiqueta::estado($o['estado']) ?></span></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
<div class="rf"><span><?= APP_NOME ?> v<?= APP_VERSAO ?></span><span>Total: <?= count($ocorrencias) ?> registo(s)</span></div>
</body></html>
<?php exit; }

// Página de configuração
$cursos    = $cm->listarTodos(true);
$semestres = $sm->listarTodos();
$turmas    = $tm->listarDoAnoAtivo();
$profs     = $pm->listarTodos(true);
$discs     = $dm->listarTodas(['ativo'=>1]);
$menuLateral  = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Relatórios PDF'; $paginaActiva = 'relatorios';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="glass-card">
      <div class="glass-header"><span class="card-conteudo-titulo"><i class="bi bi-sliders"></i> Filtros</span></div>
      <div class="card-conteudo-corpo" style="padding: 20px;">
        <form method="GET" id="formRel">
          <?php foreach ([
            ['estudante_nome','Estudante','text','Nome...'],
            ['ticket','Ticket','text','RN-2026-...'],
          ] as [$n,$l,$t,$p]): ?>
          <div class="mb-3"><label class="rotulo-campo"><?= $l ?></label><input type="<?= $t ?>" name="<?= $n ?>" class="campo-input" placeholder="<?= $p ?>" value="<?= Sanitizar::html($_GET[$n] ?? '') ?>"></div>
          <?php endforeach; ?>
          <div class="mb-3"><label class="rotulo-campo">Curso</label><select name="curso_id" class="campo-input"><option value="">Todos</option><?php foreach($cursos as $c): ?><option value="<?= $c['id'] ?>" <?= ($_GET['curso_id']??'')==$c['id']?'selected':'' ?>><?= Sanitizar::html($c['nome']) ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="rotulo-campo">Semestre</label><select name="semestre_id" class="campo-input"><option value="">Todos</option><?php foreach($semestres as $s): ?><option value="<?= $s['id'] ?>" <?= ($_GET['semestre_id']??'')==$s['id']?'selected':'' ?>><?= Sanitizar::html($s['nome']) ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="rotulo-campo">Tipo Avaliação</label><select name="tipo_avaliacao" class="campo-input"><option value="">Todos</option><?php foreach(Etiqueta::todosTipos() as $k=>$v): ?><option value="<?= $k ?>" <?= ($_GET['tipo_avaliacao']??'')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="rotulo-campo">Estado</label><select name="estado" class="campo-input"><option value="">Todos</option><?php foreach(Etiqueta::todosEstados() as $k=>$v): ?><option value="<?= $k ?>" <?= ($_GET['estado']??'')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="rotulo-campo">Turma</label><select name="turma_id" class="campo-input"><option value="">Todas</option><?php foreach($turmas as $t): ?><option value="<?= $t['id'] ?>" <?= ($_GET['turma_id']??'')==$t['id']?'selected':'' ?>><?= Sanitizar::html($t['codigo']) ?> (<?= Sanitizar::html($t['curso_nome']) ?>)</option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="rotulo-campo">Disciplina</label><select name="disciplina_id" class="campo-input"><option value="">Todas</option><?php foreach($discs as $d): ?><option value="<?= $d['id'] ?>" <?= ($_GET['disciplina_id']??'')==$d['id']?'selected':'' ?>><?= Sanitizar::html($d['nome']) ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="rotulo-campo">Professor</label><select name="professor_id" class="campo-input"><option value="">Todos</option><?php foreach($profs as $p): ?><option value="<?= $p['id'] ?>" <?= ($_GET['professor_id']??'')==$p['id']?'selected':'' ?>><?= Sanitizar::html($p['nome']) ?></option><?php endforeach; ?></select></div>
          <div class="row g-2 mb-4">
            <div class="col-6"><label class="rotulo-campo">De</label><input type="date" name="data_inicio" class="campo-input" value="<?= Sanitizar::html($_GET['data_inicio']??'') ?>"></div>
            <div class="col-6"><label class="rotulo-campo">Até</label><input type="date" name="data_fim" class="campo-input" value="<?= Sanitizar::html($_GET['data_fim']??'') ?>"></div>
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn-base btn-fantasma w-100 justify-content-center"><i class="bi bi-eye me-1"></i>Pré-visualizar</button>
            <button type="button" class="btn-base btn-perigo w-100 justify-content-center" onclick="abrirPdf()"><i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="glass-card">
      <div class="glass-header">
        <span class="card-conteudo-titulo"><i class="bi bi-table"></i> Pré-visualização
          <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($ocorrencias) ?></span>
        </span>
      </div>
      <div style="overflow-x:auto;max-height:620px;overflow-y:auto; padding: 15px;">
        <table class="tabela-sistema">
          <thead style="position:sticky;top:0;z-index:1">
            <tr><th>Ticket</th><th>Estudante</th><th>Turma</th><th>Disciplina</th><th>Tipo</th><th>Data</th><th>Estado</th></tr>
          </thead>
          <tbody>
            <?php if (empty($ocorrencias)): ?>
            <tr><td colspan="7"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Sem registos com os filtros actuais.</p></div></td></tr>
            <?php else: foreach ($ocorrencias as $o): ?>
            <tr>
              <td><span class="ticket-badge" style="font-size:10.5px"><?= Sanitizar::html($o['ticket']) ?></span></td>
              <td class="td-nome" style="font-size:12.5px"><?= Sanitizar::html($o['estudante_nome']) ?></td>
              <td class="td-muted">Turma <?= Sanitizar::html($o['turma_codigo']) ?></td>
              <td style="font-size:12.5px"><?= Sanitizar::html($o['disciplina_nome']) ?></td>
              <td><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></span></td>
              <td class="td-muted"><?= Formatar::data($o['criado_em']) ?></td>
              <td><span class="badge-estado <?= Etiqueta::badgeEstado($o['estado']) ?>" style="font-size:10.5px"><?= Etiqueta::estado($o['estado']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
function abrirPdf() {
    // Coleta os dados do formulário
    const form = document.getElementById('formRel');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    // Adiciona o parâmetro de PDF
    params.set('pdf', '1');
    
    // Abre a URL na nova aba
    const url = 'relatorios.php?' + params.toString();
    const win = window.open(url, '_blank');
    
    // Foco na janela nova caso ela tenha sido aberta
    if (win) {
        win.focus();
    } else {
        alert('O navegador bloqueou a abertura da janela de impressão. Permita pop-ups para este site.');
    }
}
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
