<?php
/**
 * public/coordenador/relatorios.php
 * Relatórios PDF – Coordenador (restrito ao seu curso)
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao,Url,Sanitizar,Etiqueta,Formatar};
use App\Models\{OcorrenciaModelo,CoordenadorModelo,SemestreModelo,TurmaModelo,DisciplinaModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirPerfil(['coordenador','super_admin']);

$uid     = Sessao::utilizador()['id'];
$coord   = (new CoordenadorModelo())->obterPorUsuario($uid);
if (!$coord && \App\Helpers\Sessao::temPerfil('super_admin')) { \App\Helpers\Url::redirecionar(\App\Helpers\Url::admin('index.php')); }
$cursoId = $coord['curso_id'] ?? 0;
$om = new OcorrenciaModelo(); $sm = new SemestreModelo(); $tm = new TurmaModelo(); $dm = new DisciplinaModelo();

$filtros = array_filter([
    'curso_id'       => $cursoId, // sempre restrito
    'estudante_nome' => Sanitizar::texto($_GET['estudante_nome'] ?? ''),
    'semestre_id'    => (int)($_GET['semestre_id'] ?? 0) ?: null,
    'tipo_avaliacao' => $_GET['tipo_avaliacao'] ?? '',
    'estado'         => $_GET['estado'] ?? '',
]);
$ocorrencias = $om->listarTodas($filtros);

if (isset($_GET['pdf'])) {
    ?><!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Relatório – <?= APP_NOME ?></title>
<style>@page{margin:16mm 12mm}body{font-family:Arial,sans-serif;font-size:10.5px;color:#111}.rh{border-bottom:3px solid #0b3d6e;padding-bottom:10px;margin-bottom:14px;display:flex;justify-content:space-between}.rh h1{font-size:14px;color:#0b3d6e;margin:0 0 2px}.rh p{font-size:9px;color:#666;margin:0}table{width:100%;border-collapse:collapse}thead th{background:#0b3d6e;color:#fff;padding:6px 8px;font-size:9px;text-align:left}tbody td{padding:5px 8px;border-bottom:1px solid #e5e7eb;font-size:9.5px}tbody tr:nth-child(even) td{background:#f9fbff}.ticket{font-family:monospace;font-weight:700;font-size:9px}.badge{display:inline-block;padding:1px 6px;border-radius:8px;font-size:8px;font-weight:700}.b-resolvida{background:#d1fae5;color:#065f46}.b-submetida{background:#ede9fe;color:#6366f1}.b-cancelada{background:#fee2e2;color:#991b1b}.b-em_analise{background:#fef3c7;color:#92400e}.b-encaminhada_professor{background:#dbeafe;color:#1e40af}.b-respondida_professor{background:#ede9fe;color:#5b21b6}.b-em_revisao_coordenacao{background:#ffedd5;color:#9a3412}.b-encerrada{background:#f3f4f6;color:#374151}.print-btn{position:fixed;top:12px;right:12px;background:#0b3d6e;color:#fff;border:none;padding:8px 16px;border-radius:7px;cursor:pointer;font-size:13px;font-weight:700}@media print{.print-btn{display:none}body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}</style></head><body>
<button class="print-btn" onclick="window.print()">🖨 Imprimir / PDF</button>
<div class="rh"><div><h1>Relatório de Ocorrências – <?= Sanitizar::html($coord['curso_nome']??'') ?></h1><p><?= APP_NOME ?> · Gerado por <?= Sanitizar::html(Sessao::utilizador()['nome']) ?> em <?= date('d/m/Y H:i') ?></p></div><div style="font-size:9px;color:#666;text-align:right">Total: <?= count($ocorrencias) ?> ocorrência(s)</div></div>
<table><thead><tr><th>Ticket</th><th>Estudante</th><th>Turma</th><th>Disciplina</th><th>Professor</th><th>Tipo</th><th>Data</th><th>Estado</th></tr></thead><tbody>
<?php if(empty($ocorrencias)): ?><tr><td colspan="8" style="text-align:center;padding:16px;color:#999">Nenhum registo.</td></tr>
<?php else: foreach($ocorrencias as $o): ?>
<tr><td class="ticket"><?= Sanitizar::html($o['ticket']) ?></td><td style="font-weight:600"><?= Sanitizar::html($o['estudante_nome']) ?></td><td>Turma <?= Sanitizar::html($o['turma_codigo']) ?></td><td><?= Sanitizar::html($o['disciplina_nome']) ?></td><td style="color:#666"><?= Sanitizar::html($o['professor_nome']) ?></td><td><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></td><td><?= Formatar::data($o['criado_em']) ?></td><td><span class="badge b-<?= $o['estado'] ?>"><?= Etiqueta::estado($o['estado']) ?></span></td></tr>
<?php endforeach; endif; ?></tbody></table></body></html>
    <?php exit;
}

$semestres   = $sm->listarTodos();
$menuLateral = ['Principal'=>[['id'=>'dashboard','titulo'=>'Dashboard','url'=>Url::coordenador('index.php'),'icone'=>'bi-speedometer2'],['id'=>'ocorrencias','titulo'=>'Ocorrências','url'=>Url::coordenador('ocorrencias.php'),'icone'=>'bi-exclamation-triangle']],'Curso'=>[['id'=>'disciplinas','titulo'=>'Disciplinas','url'=>Url::coordenador('disciplinas.php'),'icone'=>'bi-book'],['id'=>'professores','titulo'=>'Professores','url'=>Url::coordenador('professores.php'),'icone'=>'bi-person-badge'],['id'=>'turmas','titulo'=>'Turmas','url'=>Url::coordenador('turmas.php'),'icone'=>'bi-people']],'Relatórios'=>[['id'=>'relatorios','titulo'=>'Exportar PDF','url'=>Url::coordenador('relatorios.php'),'icone'=>'bi-file-earmark-pdf']]];
$tituloPagina='Relatórios PDF – '.($coord['curso_nome']??''); $paginaActiva='relatorios';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card">
            <div class="glass-header">
                <span class="fw-bold"><i class="bi bi-sliders"></i> Filtros</span>
            </div>
            <div class="card-conteudo-corpo">
                <form method="GET" id="fRel">
                    <div class="mb-3">
                        <label class="rotulo-campo">Estudante</label>
                        <input type="text" name="estudante_nome" class="campo-input" placeholder="Nome..." value="<?= Sanitizar::html($_GET['estudante_nome']??'') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="rotulo-campo">Semestre</label>
                        <select name="semestre_id" class="campo-input">
                            <option value="">Todos</option>
                            <?php foreach($semestres as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($_GET['semestre_id']??'')==$s['id']?'selected':'' ?>><?= Sanitizar::html($s['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="rotulo-campo">Tipo</label>
                        <select name="tipo_avaliacao" class="campo-input">
                            <option value="">Todos</option>
                            <?php foreach(Etiqueta::todosTipos() as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($_GET['tipo_avaliacao']??'')===$k?'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="rotulo-campo">Estado</label>
                        <select name="estado" class="campo-input">
                            <option value="">Todos</option>
                            <?php foreach(Etiqueta::todosEstados() as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($_GET['estado']??'')===$k?'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn-base btn-primario justify-content-center">
                            <i class="bi bi-eye me-1"></i> Pré-visualizar
                        </button>
                        <button type="button" class="btn-base btn-perigo justify-content-center" onclick="var p=new URLSearchParams(new FormData(document.getElementById('fRel')));p.set('pdf','1');window.open('relatorios.php?'+p,'_blank')">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Gerar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="glass-header">
                <span class="fw-bold"><i class="bi bi-table"></i> Pré-visualização</span>
                <span class="badge bg-secondary"><?= count($ocorrencias) ?></span>
            </div>
            <div style="overflow-x:auto;max-height:580px;overflow-y:auto">
                <table class="tabela-sistema mb-0">
                    <thead style="position:sticky;top:0;z-index:1">
                        <tr>
                            <th>Ticket</th>
                            <th>Estudante</th>
                            <th>Disciplina</th>
                            <th>Tipo</th>
                            <th>Data</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($ocorrencias)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="sem-dados">
                                        <i class="bi bi-inbox"></i>
                                        <p>Sem registos.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: foreach($ocorrencias as $o): ?>
                            <tr>
                                <td><span class="ticket-badge" style="font-size:10.5px"><?= Sanitizar::html($o['ticket']) ?></span></td>
                                <td class="td-nome" style="font-size:12.5px"><?= Sanitizar::html($o['estudante_nome']) ?></td>
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
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
