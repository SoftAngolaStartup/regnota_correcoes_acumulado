<?php
/**
 * public/coordenador/ocorrencias.php
 * Gestão de Ocorrências do Curso – Coordenador
 * Restrito ao curso do coordenador autenticado
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Formatar, Auditoria};
use App\Models\{OcorrenciaModelo, HistoricoModelo, RespostaProfessorModelo,
               CoordenadorModelo, TurmaModelo, SemestreModelo, DisciplinaModelo, ProfessorModelo};
use App\Middleware\Autenticacao;

Autenticacao::exigirPerfil(['coordenador','super_admin']);

$uid      = Sessao::utilizador()['id'];
$coord    = (new CoordenadorModelo())->obterPorUsuario($uid);
if (!$coord && \App\Helpers\Sessao::temPerfil('super_admin')) { \App\Helpers\Url::redirecionar(\App\Helpers\Url::admin('index.php')); }
$cursoId  = $coord['curso_id'] ?? 0;

$om  = new OcorrenciaModelo();
$hm  = new HistoricoModelo();
$rpm = new RespostaProfessorModelo();
$sm  = new SemestreModelo();
$dm  = new DisciplinaModelo();
$pm  = new ProfessorModelo();
$tm  = new TurmaModelo();

// ── POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro','Token inválido.'); header('Location: ocorrencias.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);

    if ($acao === 'mudar_estado' && $id) {
        $novoEstado = $_POST['estado'] ?? '';
        $obs        = Sanitizar::texto($_POST['observacao_oficial'] ?? '');
        $nota       = Sanitizar::texto($_POST['nota_interna'] ?? '');
        $ocorr      = $om->obterPorId($id);
        // Garantir que a ocorrência pertence ao curso do coordenador
        if ($ocorr && $ocorr['curso_id'] == $cursoId && array_key_exists($novoEstado, Etiqueta::todosEstados())) {
            $anterior = $ocorr['estado'];
            $om->actualizarEstado($id, $novoEstado, $obs ?: null, $nota ?: null, $uid);
            $hm->registar($id, $anterior, $novoEstado, $obs ?: $nota ?: null, $uid);
            Auditoria::registar('coord_mudar_estado','ocorrencias',$id,
                json_encode(['anterior'=>$anterior,'novo'=>$novoEstado]), $uid);
            Sessao::flash('sucesso','Estado actualizado para "'.Etiqueta::estado($novoEstado).'".');
        }
    } elseif ($acao === 'encaminhar' && $id) {
        $profId = (int)($_POST['professor_id'] ?? 0);
        $ocorr  = $om->obterPorId($id);
        if ($ocorr && $ocorr['curso_id'] == $cursoId && $profId) {
            $anterior = $ocorr['estado'];
            $om->encaminharProfessor($id, $profId, $uid);
            $hm->registar($id, $anterior, 'encaminhada_professor', 'Encaminhada pelo coordenador', $uid);
            Sessao::flash('sucesso','Ocorrência encaminhada ao professor.');
        }
    } elseif ($acao === 'encaminhar_multiplos') {
        $ids     = $_POST['selecionados'] ?? [];
        $profId  = (int)($_POST['professor_id'] ?? 0);
        if (!empty($ids) && $profId) {
            $om->encaminharMultiplos($ids, $profId, 'encaminhada_professor', $uid);
            Sessao::flash('sucesso', count($ids) . ' ocorrências encaminhadas com sucesso.');
        }
    }
    header('Location: ocorrencias.php'); exit;
}

// ── Filtros ───────────────────────────────────────────────
$filtros = array_filter([
    'curso_id'       => $cursoId, // Sempre filtrar pelo curso do coordenador
    'estudante_nome' => Sanitizar::texto($_GET['estudante_nome'] ?? ''),
    'turma_id'       => (int)($_GET['turma_id'] ?? 0) ?: null,
    'semestre_id'    => (int)($_GET['semestre_id'] ?? 0) ?: null,
    'disciplina_id'  => (int)($_GET['disciplina_id'] ?? 0) ?: null,
    'professor_id'   => (int)($_GET['professor_id'] ?? 0) ?: null,
    'tipo_avaliacao' => $_GET['tipo_avaliacao'] ?? '',
    'estado'         => $_GET['estado'] ?? '',
]);

$ocorrencias = $om->listarTodas($filtros);
$semestres   = $sm->listarTodos();
$disciplinas = $dm->listarTodas(['curso_id' => $cursoId, 'ativo' => 1]);
$professores = $pm->listarTodos(true);
$turmas      = $tm->listarTodas(['curso_id' => $cursoId]);

$verId    = (int)($_GET['ver'] ?? 0);
$verOcorr = $verId ? $om->obterPorId($verId) : null;
// Garantir que o coordenador só vê ocorrências do seu curso
if ($verOcorr && $verOcorr['curso_id'] != $cursoId) { $verOcorr = null; }
$verHistorico = $verId && $verOcorr ? $hm->listarPorOcorrencia($verId) : [];
$verRespostas = $verId && $verOcorr ? $rpm->listarPorOcorrencia($verId) : [];

$menuLateral = [
    'Principal'   => [
        ['id'=>'dashboard',  'titulo'=>'Dashboard',   'url'=>Url::coordenador('index.php'),       'icone'=>'bi-speedometer2'],
        ['id'=>'ocorrencias','titulo'=>'Ocorrências', 'url'=>Url::coordenador('ocorrencias.php'), 'icone'=>'bi-exclamation-triangle'],
    ],
    'Curso'       => [
        ['id'=>'disciplinas','titulo'=>'Disciplinas', 'url'=>Url::coordenador('disciplinas.php'), 'icone'=>'bi-book'],
        ['id'=>'professores','titulo'=>'Professores', 'url'=>Url::coordenador('professores.php'), 'icone'=>'bi-person-badge'],
        ['id'=>'turmas',     'titulo'=>'Turmas',      'url'=>Url::coordenador('turmas.php'),      'icone'=>'bi-people'],
    ],
    'Relatórios'  => [
        ['id'=>'relatorios','titulo'=>'Exportar PDF', 'url'=>Url::coordenador('relatorios.php'),'icone'=>'bi-file-earmark-pdf'],
    ],
];
$tituloPagina = 'Ocorrências – ' . ($coord['curso_nome'] ?? '');
$paginaActiva = 'ocorrencias';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>

<!-- Filtros -->
<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="fw-bold"><i class="bi bi-funnel"></i> Filtros de Pesquisa</span>
  </div>
  <div class="card-conteudo-corpo">
    <form method="GET">
      <div class="row g-3">
        <div class="col-md-3"><label class="rotulo-campo">Estudante</label><input type="text" name="estudante_nome" class="campo-input" placeholder="Nome..." value="<?= Sanitizar::html($_GET['estudante_nome']??'') ?>"></div>
        <div class="col-md-3"><label class="rotulo-campo">Semestre</label><select name="semestre_id" class="campo-input"><option value="">Todos</option><?php foreach($semestres as $s): ?><option value="<?= $s['id'] ?>" <?= ($_GET['semestre_id']??'')==$s['id']?'selected':'' ?>><?= Sanitizar::html($s['nome']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="rotulo-campo">Estado</label><select name="estado" class="campo-input"><option value="">Todos</option><?php foreach(Etiqueta::todosEstados() as $k=>$v): ?><option value="<?= $k ?>" <?= ($_GET['estado']??'')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="rotulo-campo">Tipo</label><select name="tipo_avaliacao" class="campo-input"><option value="">Todos</option><?php foreach(Etiqueta::todosTipos() as $k=>$v): ?><option value="<?= $k ?>" <?= ($_GET['tipo_avaliacao']??'')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="rotulo-campo">Turma</label><select name="turma_id" class="campo-input"><option value="">Todas</option><?php foreach($turmas as $t): ?><option value="<?= $t['id'] ?>" <?= ($_GET['turma_id']??'')==$t['id']?'selected':'' ?>><?= Sanitizar::html($t['codigo']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="rotulo-campo">Disciplina</label><select name="disciplina_id" class="campo-input"><option value="">Todas</option><?php foreach($disciplinas as $d): ?><option value="<?= $d['id'] ?>" <?= ($_GET['disciplina_id']??'')==$d['id']?'selected':'' ?>><?= Sanitizar::html($d['nome']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="rotulo-campo">Professor</label><select name="professor_id" class="campo-input"><option value="">Todos</option><?php foreach($professores as $p): ?><option value="<?= $p['id'] ?>" <?= ($_GET['professor_id']??'')==$p['id']?'selected':'' ?>><?= Sanitizar::html($p['nome']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" class="btn-base btn-primario w-100 justify-content-center"><i class="bi bi-search"></i> Filtrar</button>
            <?php if(count(array_filter(array_diff_key($filtros,['curso_id'=>1])))): ?><a href="ocorrencias.php" class="btn-base btn-fantasma"><i class="bi bi-x"></i></a><?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<form method="POST" id="formBatch">
  <?= Sessao::campoCsrf() ?>
  <div class="glass-card mb-4">
    <div class="glass-header">
      <span class="fw-bold"><i class="bi bi-list-ul"></i> Ocorrências</span>
      <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-secondary"><?= count($ocorrencias) ?></span>
        <button type="button" class="btn-base btn-destaque btn-sm" onclick="abrirEncaminharMultiplos()">
          <i class="bi bi-arrow-right-circle"></i> Encaminhar Seleccionados
        </button>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="tabela-sistema mb-0">
        <thead><tr><th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th><th>Ticket</th><th>Estudante</th><th>Turma / Semestre</th><th>Disciplina</th><th>Tipo</th><th>Data</th><th>Estado</th><th style="text-align:center">Acções</th></tr></thead>
        <tbody>
          <?php if (empty($ocorrencias)): ?>
          <tr><td colspan="9"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhuma ocorrência encontrada.</p></div></td></tr>
          <?php else: foreach ($ocorrencias as $o): ?>
          <tr>
            <td><input type="checkbox" name="selecionados[]" value="<?= $o['id'] ?>" class="chk-ocorrencia"></td>
            <td><a href="?ver=<?= $o['id'] ?>" class="ticket-badge text-decoration-none"><?= Sanitizar::html($o['ticket']) ?></a></td>
            <td><div class="td-nome"><?= Sanitizar::html($o['estudante_nome']) ?></div><div class="td-muted"><?= Sanitizar::html($o['estudante_numero']) ?></div></td>
            <td><div style="font-size:12.5px;font-weight:600">Turma <?= Sanitizar::html($o['turma_codigo']) ?></div><div class="td-muted"><?= Sanitizar::html($o['semestre_nome']) ?></div></td>
            <td style="font-size:13px"><?= Sanitizar::html($o['disciplina_nome']) ?></td>
            <td><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></span></td>
            <td class="td-muted"><?= Formatar::tempoRelativo($o['criado_em']) ?></td>
            <td><span class="badge-estado <?= Etiqueta::badgeEstado($o['estado']) ?>" style="font-size:11px"><?= Etiqueta::estado($o['estado']) ?></span></td>
            <td>
              <div class="d-flex gap-1 justify-content-center">
                <a href="?ver=<?= $o['id'] ?>" class="btn-icone" title="Ver detalhes"><i class="bi bi-eye"></i></a>
                <button class="btn-icone aviso" type="button" title="Encaminhar ao Professor" onclick="abrirEncaminhar(<?= $o['id'] ?>)"><i class="bi bi-arrow-right-circle"></i></button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<!-- Modal Detalhe -->
<?php if ($verOcorr): ?>
<div class="modal fade show" id="modalDetalhe" tabindex="-1" style="display:block" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="ticket-badge"><?= Sanitizar::html($verOcorr['ticket']) ?></span>
          <span class="badge-estado <?= Etiqueta::badgeEstado($verOcorr['estado']) ?>"><i class="bi <?= Etiqueta::iconeEstado($verOcorr['estado']) ?></i> <?= Etiqueta::estado($verOcorr['estado']) ?></span>
        </div>
        <a href="ocorrencias.php" class="btn-close btn-close-white"></a>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-3">
          <div class="col-sm-6"><small class="td-muted d-block">Estudante</small><strong><?= Sanitizar::html($verOcorr['estudante_nome']) ?></strong></div>
          <div class="col-sm-6"><small class="td-muted d-block">Nº Estudante</small><?= Sanitizar::html($verOcorr['estudante_numero']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Turma</small>Turma <?= Sanitizar::html($verOcorr['turma_codigo']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Disciplina</small><?= Sanitizar::html($verOcorr['disciplina_nome']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Professor</small><?= Sanitizar::html($verOcorr['professor_nome']) ?></div>
          <div class="col-12"><small class="td-muted d-block">Descrição</small><div class="bloco-nota"><?= nl2br(Sanitizar::html($verOcorr['descricao'])) ?></div></div>
          <?php if ($verOcorr['observacao_oficial']): ?><div class="col-12"><small class="td-muted d-block">Observação Oficial</small><div class="bloco-oficial"><?= nl2br(Sanitizar::html($verOcorr['observacao_oficial'])) ?></div></div><?php endif; ?>
        </div>
        <!-- Respostas do professor -->
        <?php if (!empty($verRespostas)): ?>
        <h6 class="fw-bold mt-3 mb-2" style="color:var(--primario)"><i class="bi bi-chat-left-quote me-2"></i>Respostas do Professor</h6>
        <?php foreach ($verRespostas as $r): ?>
        <div class="bloco-resposta">
          <?= nl2br(Sanitizar::html($r['resposta'])) ?>
          <div class="td-muted mt-1" style="font-size:11px"><?= Formatar::dataHora($r['criado_em']) ?></div>
        </div>
        <?php endforeach; endif; ?>
        <!-- Alterar estado -->
        <hr>
        <h6 class="fw-bold mb-3" style="color:var(--primario)"><i class="bi bi-arrow-repeat me-2"></i>Actualizar Estado</h6>
        <form method="POST">
          <?= Sessao::campoCsrf() ?>
          <input type="hidden" name="_acao" value="mudar_estado">
          <input type="hidden" name="id"    value="<?= $verOcorr['id'] ?>">
          <div class="row g-2">
            <div class="col-sm-4"><label class="rotulo-campo">Novo Estado</label><select name="estado" class="campo-input" style="font-size:13px"><?php foreach(Etiqueta::todosEstados() as $k=>$v): ?><option value="<?= $k ?>" <?= $verOcorr['estado']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-4"><label class="rotulo-campo">Obs. Oficial</label><input type="text" name="observacao_oficial" class="campo-input" style="font-size:13px" value="<?= Sanitizar::html($verOcorr['observacao_oficial']??'') ?>"></div>
            <div class="col-sm-4"><label class="rotulo-campo">Nota Interna</label><input type="text" name="nota_interna" class="campo-input" style="font-size:13px" value="<?= Sanitizar::html($verOcorr['observacao_oficial']??'') ?>"></div>
            <div class="col-12"><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Guardar</button></div>
          </div>
        </form>
        <!-- Histórico -->
        <?php if (!empty($verHistorico)): ?>
        <hr><h6 class="fw-bold mb-2" style="color:var(--primario)"><i class="bi bi-clock-history me-2"></i>Histórico</h6>
        <div class="timeline">
          <?php foreach ($verHistorico as $h): ?>
          <div class="timeline-item">
            <div class="timeline-ponto">
              <i class="bi <?= Etiqueta::iconeEstado($h['estado_novo']) ?>" style="font-size:11px"></i>
            </div>
            <div class="timeline-corpo">
              <div style="font-size:12px;font-weight:600">
                <?php if($h['estado_anterior']): ?><span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_anterior']) ?>" style="font-size:10px"><?= Etiqueta::estado($h['estado_anterior']) ?></span> <i class="bi bi-arrow-right" style="font-size:10px;color:var(--texto-muted)"></i> <?php endif; ?>
                <span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_novo']) ?>" style="font-size:10px"><?= Etiqueta::estado($h['estado_novo']) ?></span>
              </div>
              <div class="td-muted" style="font-size:11px"><?= Sanitizar::html($h['usuario_nome']??'Sistema') ?> · <?= Formatar::dataHora($h['criado_em']) ?></div>
              <?php if($h['observacao']): ?><div style="font-size:12px;font-style:italic;color:var(--texto-sec)"><?= Sanitizar::html($h['observacao']) ?></div><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<!-- Modal Encaminhar -->
<div class="modal fade" id="modalEnc" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Encaminhar ao Professor</h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <form method="POST"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" value="encaminhar"><input type="hidden" name="id" id="encId" value="">
    <div class="modal-body p-4"><label class="rotulo-campo">Professor</label><select name="professor_id" class="campo-input" required><option value="">-- Selecionar --</option><?php foreach($professores as $p): ?><option value="<?= $p['id'] ?>"><?= Sanitizar::html($p['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning"><i class="bi bi-send me-1"></i>Encaminhar</button></div>
  </form>
</div></div></div>
<script>function abrirEncaminhar(id){document.getElementById('encId').value=id;new bootstrap.Modal(document.getElementById('modalEnc')).show();}
function toggleSelectAll(source) {
    checkboxes = document.getElementsByClassName('chk-ocorrencia');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}
function abrirEncaminharMultiplos() {
    var selected = [];
    document.querySelectorAll('.chk-ocorrencia:checked').forEach(chk => {
        selected.push(chk.value);
    });
    if(selected.length === 0) {
        alert('Por favor, seleccione pelo menos uma ocorrência.');
        return;
    }
    var container = document.getElementById('multiIds');
    container.innerHTML = '';
    selected.forEach(id => {
        container.innerHTML += `<input type="hidden" name="selecionados[]" value="${id}">`;
    });
    new bootstrap.Modal(document.getElementById('modalEncMulti')).show();
}
</script>

<!-- Modal Encaminhar Múltiplos -->
<div class="modal fade" id="modalEncMulti" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Encaminhar Seleccionados</h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <form method="POST"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" value="encaminhar_multiplos"><div id="multiIds"></div>
    <div class="modal-body p-4"><label class="rotulo-campo">Professor</label><select name="professor_id" class="campo-input" required><option value="">-- Selecionar --</option><?php foreach($professores as $p): ?><option value="<?= $p['id'] ?> ?>"><?= Sanitizar::html($p['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning"><i class="bi bi-send me-1"></i>Encaminhar</button></div>
  </form>
</div></div></div>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>


<!-- Modal Detalhe -->
<?php if ($verOcorr): ?>
<div class="modal fade show" id="modalDetalhe" tabindex="-1" style="display:block" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="ticket-badge"><?= Sanitizar::html($verOcorr['ticket']) ?></span>
          <span class="badge-estado <?= Etiqueta::badgeEstado($verOcorr['estado']) ?>"><i class="bi <?= Etiqueta::iconeEstado($verOcorr['estado']) ?>"></i> <?= Etiqueta::estado($verOcorr['estado']) ?></span>
        </div>
        <a href="ocorrencias.php" class="btn-close btn-close-white"></a>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-3">
          <div class="col-sm-6"><small class="td-muted d-block">Estudante</small><strong><?= Sanitizar::html($verOcorr['estudante_nome']) ?></strong></div>
          <div class="col-sm-6"><small class="td-muted d-block">Nº Estudante</small><?= Sanitizar::html($verOcorr['estudante_numero']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Turma</small>Turma <?= Sanitizar::html($verOcorr['turma_codigo']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Disciplina</small><?= Sanitizar::html($verOcorr['disciplina_nome']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Professor</small><?= Sanitizar::html($verOcorr['professor_nome']) ?></div>
          <div class="col-12"><small class="td-muted d-block">Descrição</small><div class="bloco-nota"><?= nl2br(Sanitizar::html($verOcorr['descricao'])) ?></div></div>
          <?php if ($verOcorr['observacao_oficial']): ?><div class="col-12"><small class="td-muted d-block">Observação Oficial</small><div class="bloco-oficial"><?= nl2br(Sanitizar::html($verOcorr['observacao_oficial'])) ?></div></div><?php endif; ?>
        </div>
        <!-- Respostas do professor -->
        <?php if (!empty($verRespostas)): ?>
        <h6 class="fw-bold mt-3 mb-2" style="color:var(--primario)"><i class="bi bi-chat-left-quote me-2"></i>Respostas do Professor</h6>
        <?php foreach ($verRespostas as $r): ?>
        <div class="bloco-resposta">
          <div class="fw-bold mb-1"><?= Sanitizar::html($r['professor_nome']) ?></div>
          <div><?= nl2br(Sanitizar::html($r['resposta'])) ?></div>
          <div class="td-muted mt-1"><?= Formatar::dataHora($r['criado_em']) ?></div>
        </div>
        <?php endforeach; endif; ?>
        <!-- Alterar estado -->
        <hr>
        <h6 class="fw-bold mb-3" style="color:var(--primario)"><i class="bi bi-arrow-repeat me-2"></i>Actualizar Estado</h6>
        <form method="POST">
          <?= Sessao::campoCsrf() ?>
          <input type="hidden" name="_acao" value="mudar_estado">
          <input type="hidden" name="id"    value="<?= $verOcorr['id'] ?>">
          <div class="row g-2">
            <div class="col-sm-4"><label class="rotulo-campo">Novo Estado</label><select name="estado" class="campo-input" style="font-size:13px"><?php foreach(Etiqueta::todosEstados() as $k=>$v): ?><option value="<?= $k ?>" <?= $verOcorr['estado']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-4"><label class="rotulo-campo">Obs. Oficial</label><input type="text" name="observacao_oficial" class="campo-input" style="font-size:13px" value="<?= Sanitizar::html($verOcorr['observacao_oficial']??'') ?>"></div>
            <div class="col-sm-4"><label class="rotulo-campo">Nota Interna</label><input type="text" name="nota_interna" class="campo-input" style="font-size:13px" value="<?= Sanitizar::html($verOcorr['nota_interna']??'') ?>"></div>
            <div class="col-12"><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Guardar</button></div>
          </div>
        </form>
        <!-- Histórico -->
        <?php if (!empty($verHistorico)): ?>
        <hr><h6 class="fw-bold mb-2" style="color:var(--primario)"><i class="bi bi-clock-history me-2"></i>Histórico</h6>
        <div class="timeline">
          <?php foreach ($verHistorico as $h): ?>
          <div class="timeline-item">
            <div class="timeline-ponto"><i class="bi <?= Etiqueta::iconeEstado($h['estado_novo']) ?>" style="font-size:11px"></i></div>
            <div class="timeline-corpo">
              <div style="font-size:12px;font-weight:600">
                <?php if($h['estado_anterior']): ?><span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_anterior']) ?>" style="font-size:10px"><?= Etiqueta::estado($h['estado_anterior']) ?></span> <i class="bi bi-arrow-right" style="font-size:10px;color:var(--texto-muted)"></i> <?php endif; ?>
                <span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_novo']) ?>" style="font-size:10px"><?= Etiqueta::estado($h['estado_novo']) ?></span>
              </div>
              <div class="td-muted" style="font-size:11px"><?= Sanitizar::html($h['usuario_nome']??'Sistema') ?> · <?= Formatar::dataHora($h['criado_em']) ?></div>
              <?php if($h['observacao']): ?><div style="font-size:12px;font-style:italic;color:var(--texto-sec)"><?= Sanitizar::html($h['observacao']) ?></div><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<!-- Modal Encaminhar -->
<div class="modal fade" id="modalEnc" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Encaminhar ao Professor</h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <form method="POST"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" value="encaminhar"><input type="hidden" name="id" id="encId" value="">
    <div class="modal-body p-4"><label class="rotulo-campo">Professor</label><select name="professor_id" class="campo-input" required><option value="">-- Selecionar --</option><?php foreach($professores as $p): ?><option value="<?= $p['id'] ?>"><?= Sanitizar::html($p['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning"><i class="bi bi-send me-1"></i>Encaminhar</button></div>
  </form>
</div></div></div>
<script>function abrirEncaminhar(id){document.getElementById('encId').value=id;new bootstrap.Modal(document.getElementById('modalEnc')).show();}</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
