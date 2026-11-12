<?php
/**
 * public/admin/ocorrencias.php
 * Gestão Completa de Ocorrências – CRUD + Ciclo de Vida
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Url, Etiqueta, Formatar, Sanitizar};
use App\Models\{OcorrenciaModelo, HistoricoModelo, RespostaProfessorModelo,
               CursoModelo, TurmaModelo, SemestreModelo, DisciplinaModelo, ProfessorModelo};
use App\Middleware\Autenticacao;
use App\Helpers\Auditoria;

Autenticacao::exigirAdmin();

$om  = new OcorrenciaModelo();
$hm  = new HistoricoModelo();
$rpm = new RespostaProfessorModelo();
$cm  = new CursoModelo();
$tm  = new TurmaModelo();
$sm  = new SemestreModelo();
$dm  = new DisciplinaModelo();
$pm  = new ProfessorModelo();
$uid = Sessao::utilizador()['id'];

// ── Processar POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro', 'Token inválido.'); header('Location: ocorrencias.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';

    if ($acao === 'mudar_estado') {
        $id         = (int)($_POST['id'] ?? 0);
        $novoEstado = $_POST['estado'] ?? '';
        $obs        = Sanitizar::texto($_POST['observacao_oficial'] ?? '');
        $nota       = Sanitizar::texto($_POST['nota_interna'] ?? '');
        $anterior   = $om->obterPorId($id)['estado'] ?? null;

        if ($id && array_key_exists($novoEstado, Etiqueta::todosEstados())) {
            $om->actualizarEstado($id, $novoEstado, $obs ?: null, $nota ?: null, $uid);
            $hm->registar($id, $anterior, $novoEstado, $obs ?: $nota ?: null, $uid);
            Auditoria::registar('mudar_estado_ocorrencia', 'ocorrencias', $id,
                json_encode(['anterior'=>$anterior, 'novo'=>$novoEstado]), $uid);
            Sessao::flash('sucesso', 'Estado actualizado para "' . Etiqueta::estado($novoEstado) . '".');
        }
    } elseif ($acao === 'encaminhar') {
        $id      = (int)($_POST['id'] ?? 0);
        $profId  = (int)($_POST['professor_id'] ?? 0);
        $anterior= $om->obterPorId($id)['estado'] ?? null;
        if ($id && $profId) {
            $om->encaminharProfessor($id, $profId, $uid);
            $hm->registar($id, $anterior, 'encaminhada_professor',
                'Encaminhada ao professor ID '.$profId, $uid);
            Sessao::flash('sucesso', 'Ocorrência encaminhada ao professor com sucesso.');
        }
    } elseif ($acao === 'encaminhar_multiplos') {
        $ids     = $_POST['selecionados'] ?? [];
        $profId  = (int)($_POST['professor_id'] ?? 0);
        if (!empty($ids) && $profId) {
            $om->encaminharMultiplos($ids, $profId, 'encaminhada_professor', $uid);
            Sessao::flash('sucesso', count($ids) . ' ocorrências encaminhadas com sucesso.');
        }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $om->eliminar($id);
            Auditoria::registar('eliminar_ocorrencia', 'ocorrencias', $id, null, $uid);
            Sessao::flash('sucesso', 'Ocorrência eliminada.');
        }
    }

    // Reconstruir query string de filtros para preservar no redirect
    $qs = http_build_query(array_filter([
        'estado'      => $_POST['f_estado'] ?? '',
        'curso_id'    => $_POST['f_curso'] ?? '',
        'semestre_id' => $_POST['f_sem'] ?? '',
    ]));
    header('Location: ocorrencias.php?' . $qs); exit;
}

// ── Filtros e listagem ────────────────────────────────────
$filtros = array_filter([
    'ticket'           => Sanitizar::texto($_GET['ticket'] ?? ''),
    'estudante_nome'   => Sanitizar::texto($_GET['estudante_nome'] ?? ''),
    'estudante_numero' => Sanitizar::texto($_GET['estudante_numero'] ?? ''),
    'curso_id'         => (int)($_GET['curso_id'] ?? 0) ?: null,
    'turma_id'         => (int)($_GET['turma_id'] ?? 0) ?: null,
    'semestre_id'      => (int)($_GET['semestre_id'] ?? 0) ?: null,
    'disciplina_id'    => (int)($_GET['disciplina_id'] ?? 0) ?: null,
    'professor_id'     => (int)($_GET['professor_id'] ?? 0) ?: null,
    'tipo_avaliacao'   => $_GET['tipo_avaliacao'] ?? '',
    'estado'           => $_GET['estado'] ?? '',
    'data_inicio'      => $_GET['data_inicio'] ?? '',
    'data_fim'         => $_GET['data_fim'] ?? '',
]);

$ocorrencias = $om->listarTodas($filtros);
$total       = count($ocorrencias);

// Dados para selects de filtro
$cursos    = $cm->listarTodos(true);
$semestres = $sm->listarTodos();
$turmas    = $tm->listarDoAnoAtivo();
$discs     = $dm->listarTodas(['ativo'=>1]);
$profs     = $pm->listarTodos(true);

// Detalhe de ocorrência (modal de ver)
$verId     = (int)($_GET['ver'] ?? 0);
$verOcorr  = $verId ? $om->obterPorId($verId) : null;
$verHistorico = $verId ? $hm->listarPorOcorrencia($verId) : [];
$verRespostas = $verId ? $rpm->listarPorOcorrencia($verId) : [];

// Contadores por estado
$contadores = [];
foreach ($ocorrencias as $o) {
    $contadores[$o['estado']] = ($contadores[$o['estado']] ?? 0) + 1;
}

$tituloPagina = 'Gestão de Ocorrências';
$paginaActiva = 'ocorrencias';

// Menu lateral do painel admin
$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
require_once __DIR__ . '/../../views/shared/cabecalho_painel.php';
?>

<!-- Barra de estados rápidos -->
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 flex-wrap">
    <a href="ocorrencias.php" class="badge-estado <?= empty($_GET['estado']) ? 'badge-encaminhada' : 'badge-secundario' ?> text-decoration-none">
      Todas: <strong><?= $total ?></strong>
    </a>
    <?php foreach (Etiqueta::todosEstados() as $k => $v): ?>
    <?php if (!empty($contadores[$k])): ?>
    <a href="?estado=<?= $k ?>&<?= http_build_query(array_diff_key($filtros,['estado'=>1])) ?>"
       class="badge-estado <?= Etiqueta::badgeEstado($k) ?> text-decoration-none"
       title="<?= $v ?>">
      <?= $v ?>: <strong><?= $contadores[$k] ?></strong>
    </a>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="d-flex gap-2">
    <?php if (!empty($filtros)): ?>
    <a href="ocorrencias.php" class="btn-base btn-fantasma btn-sm"><i class="bi bi-x"></i> Limpar</a>
    <?php endif; ?>
    <a href="relatorios.php?<?= http_build_query($filtros) ?>" class="btn-base btn-perigo" style="padding:7px 14px;font-size:12.5px">
      <i class="bi bi-file-earmark-pdf"></i> PDF
    </a>
  </div>
</div>

<!-- Barra de filtros -->
<div class="barra-filtros mb-4">
  <form method="GET">
    <div class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="rotulo-campo">Ticket / Nº Estudante</label>
        <input type="text" name="ticket" class="campo-input" style="padding:7px 11px;font-size:13px"
               placeholder="RN-2026-..." value="<?= Sanitizar::html($_GET['ticket'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Nome Estudante</label>
        <input type="text" name="estudante_nome" class="campo-input" style="padding:7px 11px;font-size:13px"
               placeholder="Nome..." value="<?= Sanitizar::html($_GET['estudante_nome'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Curso</label>
        <select name="curso_id" class="campo-input" style="padding:7px 11px;font-size:13px">
          <option value="">Todos</option>
          <?php foreach ($cursos as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($_GET['curso_id']??'') == $c['id'] ? 'selected' : '' ?>>
            <?= Sanitizar::html($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Semestre</label>
        <select name="semestre_id" class="campo-input" style="padding:7px 11px;font-size:13px">
          <option value="">Todos</option>
          <?php foreach ($semestres as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($_GET['semestre_id']??'') == $s['id'] ? 'selected' : '' ?>>
            <?= Sanitizar::html($s['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Estado</label>
        <select name="estado" class="campo-input" style="padding:7px 11px;font-size:13px">
          <option value="">Todos</option>
          <?php foreach (Etiqueta::todosEstados() as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($_GET['estado']??'') === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="rotulo-campo">Tipo</label>
        <select name="tipo_avaliacao" class="campo-input" style="padding:7px 11px;font-size:13px">
          <option value="">Todos</option>
          <?php foreach (Etiqueta::todosTipos() as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($_GET['tipo_avaliacao']??'') === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="rotulo-campo">&nbsp;</label>
      </div>
    </div>
    <div class="row g-2 mt-2 align-items-end">
      <div class="col-md-2">
        <label class="rotulo-campo">Turma</label>
        <select name="turma_id" class="campo-input" style="padding:7px 11px;font-size:13px">
          <option value="">Todas</option>
          <?php foreach ($turmas as $t): ?>
          <option value="<?= $t['id'] ?>" <?= ($_GET['turma_id']??'') == $t['id'] ? 'selected' : '' ?>>
            Turma <?= Sanitizar::html($t['codigo']) ?> (<?= Sanitizar::html($t['curso_nome']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Disciplina</label>
        <select name="disciplina_id" class="campo-input" style="padding:7px 11px;font-size:13px">
          <option value="">Todas</option>
          <?php foreach ($discs as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($_GET['disciplina_id']??'') == $d['id'] ? 'selected' : '' ?>>
            <?= Sanitizar::html($d['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Professor</label>
        <select name="professor_id" class="campo-input" style="padding:7px 11px;font-size:13px">
          <option value="">Todos</option>
          <?php foreach ($profs as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ($_GET['professor_id']??'') == $p['id'] ? 'selected' : '' ?>>
            <?= Sanitizar::html($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Data Início</label>
        <input type="date" name="data_inicio" class="campo-input" style="padding:7px 11px;font-size:13px" value="<?= Sanitizar::html($_GET['data_inicio'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">Data Fim</label>
        <input type="date" name="data_fim" class="campo-input" style="padding:7px 11px;font-size:13px" value="<?= Sanitizar::html($_GET['data_fim'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="rotulo-campo">&nbsp;</label>
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="bi bi-search"></i> Filtrar
        </button>
      </div>
    </div>
  </form>
</div>

<!-- Tabela de ocorrências -->
<form method="POST" id="formBatch">
  <?= Sessao::campoCsrf() ?>
<div class="glass-card">
    <div class="glass-header d-flex align-items-center justify-content-between">
      <span class="card-conteudo-titulo">
        <i class="bi bi-list-ul"></i> Ocorrências
        <span class="badge bg-secondary ms-1" style="font-size:11px"><?= $total ?></span>
      </span>
      <div class="d-flex gap-2">
        <button type="button" class="btn-base btn-destaque btn-sm" onclick="abrirEncaminharMultiplos()">
          <i class="bi bi-arrow-right-circle"></i> Encaminhar Seleccionados
        </button>
      </div>
    </div>
    <div style="overflow-x:auto; padding: 15px;">
      <table class="tabela-sistema">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
            <th>Ticket</th><th>Estudante</th><th>Turma</th>
            <th>Disciplina / Prof.</th><th>Tipo</th><th>Submetida</th>
            <th>Estado</th><th style="text-align:center">Acções</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ocorrencias)): ?>
          <tr><td colspan="9">
            <div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhuma ocorrência encontrada.</p></div>
          </td></tr>
          <?php else: ?>
          <?php foreach ($ocorrencias as $o): ?>
          <tr>
            <td><input type="checkbox" name="selecionados[]" value="<?= $o['id'] ?>" class="chk-ocorrencia"></td>
            <td>
              <a href="?ver=<?= $o['id'] ?>&<?= http_build_query($filtros) ?>"
                 class="ticket-badge text-decoration-none"><?= Sanitizar::html($o['ticket']) ?></a>
            </td>
            <td>
              <div class="td-nome"><?= Sanitizar::html($o['estudante_nome']) ?></div>
              <div class="td-muted"><?= Sanitizar::html($o['estudante_numero']) ?></div>
            </td>
            <td>
              <div style="font-size:13px;font-weight:600"><?= Sanitizar::html($o['curso_nome']) ?></div>
              <div class="td-muted">Turma <?= Sanitizar::html($o['turma_codigo']) ?> &middot; <?= Etiqueta::anoNivel((int)$o['ano_nivel']) ?></div>
            </td>
            <td>
              <div style="font-size:13px;font-weight:600"><?= Sanitizar::html($o['disciplina_nome']) ?></div>
              <div class="td-muted"><?= Sanitizar::html($o['professor_nome']) ?></div>
            </td>
            <td><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></span></td>
            <td class="td-muted"><?= Formatar::tempoRelativo($o['criado_em']) ?></td>
            <td>
              <!-- Selector de estado inline -->
              <form method="POST" style="margin:0">
                <?= Sessao::campoCsrf() ?>
                <input type="hidden" name="_acao" value="mudar_estado">
                <input type="hidden" name="id"    value="<?= $o['id'] ?>">
                <?php foreach (array_keys($filtros) as $fk): ?>
                <input type="hidden" name="f_<?= $fk ?>" value="<?= Sanitizar::html($filtros[$fk]) ?>">
                <?php endforeach; ?>
                <select name="estado" class="campo-input" style="padding:4px 24px 4px 8px;font-size:11.5px;font-weight:700;border-radius:20px;min-width:160px;cursor:pointer"
                        onchange="if(confirm('Mudar estado?')) this.form.submit()">
                  <?php foreach (Etiqueta::todosEstados() as $k => $v): ?>
                  <option value="<?= $k ?>" <?= $o['estado'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td>
              <div class="d-flex gap-1 justify-content-center">
                <a href="?ver=<?= $o['id'] ?>" class="btn-icone" title="Ver detalhes"><i class="bi bi-eye"></i></a>
                <button class="btn-icone aviso" title="Encaminhar ao Professor"
                        onclick="abrirEncaminhar(<?= $o['id'] ?>)"><i class="bi bi-arrow-right-circle"></i></button>
                <form method="POST" style="margin:0"
                      onsubmit="return confirmarEliminar('Eliminar a ocorrência <?= Sanitizar::html($o['ticket']) ?>?')">
                  <?= Sessao::campoCsrf() ?>
                  <input type="hidden" name="_acao" value="eliminar">
                  <input type="hidden" name="id"    value="<?= $o['id'] ?>">
                  <button type="submit" class="btn-icone perigo" title="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<!-- ══ Modal de Detalhe ══ -->
<?php if ($verOcorr): ?>
<div class="modal fade show" id="modalDetalhe" tabindex="-1" style="display:block" aria-modal="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <span class="ticket-badge" style="font-size:13px"><?= Sanitizar::html($verOcorr['ticket']) ?></span>
          <span class="badge-estado <?= Etiqueta::badgeEstado($verOcorr['estado']) ?>">
            <i class="bi <?= Etiqueta::iconeEstado($verOcorr['estado']) ?>"></i>
            <?= Etiqueta::estado($verOcorr['estado']) ?>
          </span>
        </div>
        <a href="ocorrencias.php?<?= http_build_query($filtros) ?>" class="btn-close btn-close-white"></a>
      </div>
      <div class="modal-body p-4">
        <div class="row g-4">
          <!-- Dados da ocorrência -->
          <div class="col-md-6">
            <h6 class="fw-bold mb-3" style="color:var(--primario)"><i class="bi bi-person me-2"></i>Dados do Estudante</h6>
            <table class="table table-sm table-borderless" style="font-size:13px">
              <tr><th class="td-muted" style="width:140px">Nome</th><td class="fw-bold"><?= Sanitizar::html($verOcorr['estudante_nome']) ?></td></tr>
              <tr><th class="td-muted">Nº Estudante</th><td><?= Sanitizar::html($verOcorr['estudante_numero']) ?></td></tr>
              <tr><th class="td-muted">Telefone</th><td><?= Sanitizar::html($verOcorr['estudante_telefone']) ?></td></tr>
              <tr><th class="td-muted">Email</th><td><?= Sanitizar::html($verOcorr['estudante_email']) ?></td></tr>
              <tr><th class="td-muted">Curso</th><td><?= Sanitizar::html($verOcorr['curso_nome']) ?></td></tr>
              <tr><th class="td-muted">Turma</th><td>Turma <?= Sanitizar::html($verOcorr['turma_codigo']) ?> &middot; <?= Etiqueta::anoNivel((int)$verOcorr['ano_nivel']) ?></td></tr>
              <tr><th class="td-muted">Semestre</th><td><?= Sanitizar::html($verOcorr['semestre_nome']) ?></td></tr>
              <tr><th class="td-muted">Disciplina</th><td><?= Sanitizar::html($verOcorr['disciplina_nome']) ?></td></tr>
              <tr><th class="td-muted">Professor</th><td><?= Sanitizar::html($verOcorr['professor_nome']) ?></td></tr>
              <tr><th class="td-muted">Tipo</th><td><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($verOcorr['tipo_avaliacao']) ?></span></td></tr>
              <tr><th class="td-muted">Submetida</th><td><?= Formatar::dataHora($verOcorr['criado_em']) ?></td></tr>
            </table>
            <h6 class="fw-bold mb-2 mt-3" style="color:var(--primario)"><i class="bi bi-chat-text me-2"></i>Descrição</h6>
            <div style="background:#f7f9fd;border:1px solid var(--borda);border-radius:8px;padding:14px;font-size:13.5px;line-height:1.7">
              <?= nl2br(Sanitizar::html($verOcorr['descricao'])) ?>
            </div>
            <?php if ($verOcorr['observacao_oficial']): ?>
            <h6 class="fw-bold mb-2 mt-3" style="color:var(--primario)">Observação Oficial</h6>
            <div style="background:#f0fdf4;border:1px solid #a7f3d0;border-radius:8px;padding:12px;font-size:13px">
              <?= nl2br(Sanitizar::html($verOcorr['observacao_oficial'])) ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Acções e histórico -->
          <div class="col-md-6">
            <!-- Alterar estado -->
            <h6 class="fw-bold mb-3" style="color:var(--primario)"><i class="bi bi-arrow-repeat me-2"></i>Alterar Estado</h6>
            <form method="POST" class="mb-4">
              <?= Sessao::campoCsrf() ?>
              <input type="hidden" name="_acao" value="mudar_estado">
              <input type="hidden" name="id"    value="<?= $verOcorr['id'] ?>">
              <div class="row g-2">
                <div class="col-6">
                  <label class="rotulo-campo">Novo Estado</label>
                  <select name="estado" class="campo-input" style="padding:8px 12px;font-size:13px">
                    <?php foreach (Etiqueta::todosEstados() as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $verOcorr['estado'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label class="rotulo-campo">Obs. Oficial (visível ao estudante)</label>
                  <input type="text" name="observacao_oficial" class="campo-input"
                         style="padding:8px 12px;font-size:13px"
                         value="<?= Sanitizar::html($verOcorr['observacao_oficial'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="rotulo-campo">Nota Interna (não visível ao estudante)</label>
                  <input type="text" name="nota_interna" class="campo-input"
                         style="padding:8px 12px;font-size:13px"
                         value="<?= Sanitizar::html($verOcorr['nota_interna'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-save me-1"></i>Guardar Estado
                  </button>
                </div>
              </div>
            </form>

            <!-- Respostas do professor -->
            <?php if (!empty($verRespostas)): ?>
            <h6 class="fw-bold mb-2" style="color:var(--primario)"><i class="bi bi-chat-left-quote me-2"></i>Respostas do Professor</h6>
            <?php foreach ($verRespostas as $r): ?>
            <div style="background:#ede9fe;border-radius:8px;padding:12px;margin-bottom:8px;font-size:13px">
              <div class="fw-bold mb-1"><?= Sanitizar::html($r['professor_nome']) ?></div>
              <div><?= nl2br(Sanitizar::html($r['resposta'])) ?></div>
              <div class="td-muted mt-1"><?= Formatar::dataHora($r['criado_em']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Histórico de estados -->
            <h6 class="fw-bold mb-3 mt-3" style="color:var(--primario)"><i class="bi bi-clock-history me-2"></i>Histórico</h6>
            <?php if (empty($verHistorico)): ?>
            <p class="td-muted" style="font-size:13px">Nenhum histórico ainda.</p>
            <?php else: ?>
            <div class="timeline">
              <?php foreach ($verHistorico as $h): ?>
              <div class="timeline-item">
                <div class="timeline-ponto"><i class="bi <?= Etiqueta::iconeEstado($h['estado_novo']) ?>" style="font-size:11px"></i></div>
                <div class="timeline-corpo">
                  <div style="font-size:12.5px;font-weight:600">
                    <?php if ($h['estado_anterior']): ?>
                    <span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_anterior']) ?>" style="font-size:10.5px"><?= Etiqueta::estado($h['estado_anterior']) ?></span>
                    <i class="bi bi-arrow-right" style="color:var(--texto-muted);font-size:10px;margin:0 4px"></i>
                    <?php endif; ?>
                    <span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_novo']) ?>" style="font-size:10.5px"><?= Etiqueta::estado($h['estado_novo']) ?></span>
                  </div>
                  <div class="td-muted" style="font-size:11.5px;margin-top:2px">
                    <?= Sanitizar::html($h['usuario_nome'] ?? 'Sistema') ?> &middot; <?= Formatar::dataHora($h['criado_em']) ?>
                  </div>
                  <?php if ($h['observacao']): ?>
                  <div style="font-size:12px;color:var(--texto-sec);font-style:italic;margin-top:3px"><?= Sanitizar::html($h['observacao']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<!-- Modal Encaminhar Professor -->
<div class="modal fade" id="modalEncaminhar" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Encaminhar ao Professor</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= Sessao::campoCsrf() ?>
        <input type="hidden" name="_acao" value="encaminhar">
        <input type="hidden" name="id" id="encId" value="">
        <div class="modal-body p-4">
          <label class="rotulo-campo">Professor Responsável</label>
          <select name="professor_id" class="campo-input" required>
            <option value="">-- Selecionar --</option>
            <?php foreach ($profs as $p): ?>
            <option value="<?= $p['id'] ?>"><?= Sanitizar::html($p['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning"><i class="bi bi-send me-1"></i>Encaminhar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Encaminhar Múltiplos -->
<div class="modal fade" id="modalEncMulti" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Encaminhar Seleccionados</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= Sessao::campoCsrf() ?>
        <input type="hidden" name="_acao" value="encaminhar_multiplos">
        <div id="multiIds"></div>
        <div class="modal-body p-4">
          <label class="rotulo-campo">Professor Responsável</label>
          <select name="professor_id" class="campo-input" required>
            <option value="">-- Selecionar --</option>
            <?php foreach ($profs as $p): ?>
            <option value="<?= $p['id'] ?>"><?= Sanitizar::html($p['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning"><i class="bi bi-send me-1"></i>Encaminhar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirEncaminhar(id) {
    document.getElementById('encId').value = id;
    new bootstrap.Modal(document.getElementById('modalEncaminhar')).show();
}

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

<?php require_once __DIR__ . '/../../views/shared/rodape_painel.php'; ?>
