<?php
/**
 * public/professor/ocorrencias.php
 * Ocorrências das Disciplinas do Professor + Resposta
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Formatar, Auditoria};
use App\Models\{ProfessorModelo, OcorrenciaModelo, HistoricoModelo, RespostaProfessorModelo};
use App\Middleware\Autenticacao;
use App\Helpers\Bd;

Autenticacao::exigirPerfil('professor');

$uid  = Sessao::utilizador()['id'];
$prof = (new ProfessorModelo())->obterPorUsuario($uid);
if (!$prof) { Url::redirecionar(Url::publico('login.php')); }

$om  = new OcorrenciaModelo();
$hm  = new HistoricoModelo();
$rpm = new RespostaProfessorModelo();

// ── POST: Responder ocorrência ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro','Token inválido.'); header('Location: ocorrencias.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);

    if ($acao === 'responder' && $id) {
        $resposta = Sanitizar::texto($_POST['resposta'] ?? '');
        if (!$resposta) {
            Sessao::flash('erro','A resposta não pode estar vazia.');
        } else {
            // Verificar que a ocorrência pertence às disciplinas do professor
            $pdo  = Bd::obter();
            $stmt = $pdo->prepare("SELECT o.id, o.estado FROM ocorrencias o JOIN disciplinas d ON o.disciplina_id=d.id WHERE o.id=? AND d.professor_id=?");
            $stmt->execute([$id, $prof['id']]);
            $ocorr = $stmt->fetch();

            if ($ocorr) {
                // Guardar resposta
                $rpm->criar($id, $prof['id'], $resposta);
                // Mudar estado para "Respondida pelo Professor"
                $anterior = $ocorr['estado'];
                $om->actualizarEstado($id, 'respondida_professor', null, null, $uid);
                $hm->registar($id, $anterior, 'respondida_professor',
                    'Resposta submetida pelo professor', $uid);
                Auditoria::registar('professor_responder', 'ocorrencias', $id, null, $uid);
                Sessao::flash('sucesso', 'Resposta submetida com sucesso! Estado actualizado.');
            } else {
                Sessao::flash('erro', 'Não tem permissão para responder a esta ocorrência.');
            }
        }
    }
    header('Location: ocorrencias.php' . ($id ? '?ver='.$id : '')); exit;
}

// ── Listagem das ocorrências do professor ─────────────────
$estadoFiltro = Sanitizar::texto($_GET['estado'] ?? '');
$turmaFiltro  = (int)($_GET['turma_id'] ?? 0);
$semestreFiltro = (int)($_GET['semestre_id'] ?? 0);

$pdo  = Bd::obter();
$where = " WHERE d.professor_id = ?";
$params = [$prof['id']];

if ($estadoFiltro) {
    $where .= " AND o.estado = ?";
    $params[] = $estadoFiltro;
}
if ($turmaFiltro) {
    $where .= " AND o.turma_id = ?";
    $params[] = $turmaFiltro;
}
if ($semestreFiltro) {
    $where .= " AND o.semestre_id = ?";
    $params[] = $semestreFiltro;
}

$stmt = $pdo->prepare("
    SELECT o.*, d.nome AS disciplina_nome, t.codigo AS turma_codigo,
           s.nome AS semestre_nome, c.nome AS curso_nome
    FROM ocorrencias o
    JOIN disciplinas d ON o.disciplina_id = d.id
    JOIN turmas t      ON o.turma_id      = t.id
    JOIN semestres s   ON o.semestre_id   = s.id
    JOIN cursos c      ON o.curso_id      = c.id
    $where
    ORDER BY FIELD(o.estado,'encaminhada_professor','submetida','em_analise') DESC, o.criado_em DESC
");
$stmt->execute($params);
$ocorrencias = $stmt->fetchAll();

// Dados para os filtros
$turmas = (new \App\Models\TurmaModelo())->listarTodas(['professor_id' => $prof['id']]); // Simplified
$semestres = (new \App\Models\SemestreModelo())->listarTodos();

// Detalhe
$verId    = (int)($_GET['ver'] ?? 0);
$verOcorr = null;
if ($verId) {
    $stmtV = $pdo->prepare("SELECT o.*, d.nome AS disciplina_nome, t.codigo AS turma_codigo, s.nome AS semestre_nome, c.nome AS curso_nome FROM ocorrencias o JOIN disciplinas d ON o.disciplina_id=d.id JOIN turmas t ON o.turma_id=t.id JOIN semestres s ON o.semestre_id=s.id JOIN cursos c ON o.curso_id=c.id WHERE o.id=? AND d.professor_id=?");
    $stmtV->execute([$verId, $prof['id']]);
    $verOcorr   = $stmtV->fetch() ?: null;
    $verRespostas  = $verOcorr ? $rpm->listarPorOcorrencia($verId) : [];
    $verHistorico  = $verOcorr ? $hm->listarPorOcorrencia($verId) : [];
}

$menuLateral = [
    'Principal' => [
        ['id'=>'dashboard',  'titulo'=>'Dashboard',   'url'=>Url::professor('index.php'),       'icone'=>'bi-speedometer2'],
        ['id'=>'ocorrencias','titulo'=>'Ocorrências', 'url'=>Url::professor('ocorrencias.php'), 'icone'=>'bi-exclamation-triangle'],
    ],
    'As Minhas Disciplinas' => [
        ['id'=>'disciplinas','titulo'=>'Disciplinas', 'url'=>Url::professor('disciplinas.php'), 'icone'=>'bi-book'],
    ],
];
$tituloPagina = 'Ocorrências – Minhas Disciplinas';
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
        <div class="col-md-3">
          <label class="rotulo-campo">Turma</label>
          <select name="turma_id" class="campo-input">
            <option value="">Todas</option>
            <?php foreach($turmas as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($_GET['turma_id']??'')==$t['id']?'selected':'' ?>><?= Sanitizar::html($t['codigo']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="rotulo-campo">Semestre</label>
          <select name="semestre_id" class="campo-input">
            <option value="">Todos</option>
            <?php foreach($semestres as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($_GET['semestre_id']??'')==$s['id']?'selected':'' ?>><?= Sanitizar::html($s['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="rotulo-campo">Estado</label>
          <select name="estado" class="campo-input">
            <option value="">Todos</option>
            <?php foreach(Etiqueta::todosEstados() as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($_GET['estado']??'')===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn-base btn-primario w-100 justify-content-center"><i class="bi bi-search"></i> Filtrar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="fw-bold"><i class="bi bi-list-ul"></i> Ocorrências</span>
    <span class="badge bg-secondary"><?= count($ocorrencias) ?></span>
  </div>
  <div style="overflow-x:auto">
    <table class="tabela-sistema mb-0">
      <thead>
        <tr><th>Ticket</th><th>Estudante</th><th>Disciplina / Turma</th><th>Tipo</th><th>Data</th><th>Estado</th><th style="text-align:center">Acções</th></tr>
      </thead>
      <tbody>
        <?php if (empty($ocorrencias)): ?>
        <tr><td colspan="7"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhuma ocorrência encontrada.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($ocorrencias as $o): ?>
        <tr>
          <td><a href="?ver=<?= $o['id'] ?>" class="ticket-badge text-decoration-none"><?= Sanitizar::html($o['ticket']) ?></a></td>
          <td>
            <div class="td-nome"><?= Sanitizar::html($o['estudante_nome']) ?></div>
            <div class="td-muted"><?= Sanitizar::html($o['estudante_numero']) ?></div>
          </td>
          <td>
            <div style="font-size:13px;font-weight:600"><?= Sanitizar::html($o['disciplina_nome']) ?></div>
            <div class="td-muted">Turma <?= Sanitizar::html($o['turma_codigo']) ?> &middot; <?= Sanitizar::html($o['semestre_nome']) ?></div>
          </td>
          <td><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></span></td>
          <td class="td-muted"><?= Formatar::tempoRelativo($o['criado_em']) ?></td>
          <td>
            <span class="badge-estado <?= Etiqueta::badgeEstado($o['estado']) ?>" style="font-size:11px">
              <i class="bi <?= Etiqueta::iconeEstado($o['estado']) ?>"></i>
              <?= Etiqueta::estado($o['estado']) ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1 justify-content-center">
              <a href="?ver=<?= $o['id'] ?>" class="btn-icone" title="Ver detalhes"><i class="bi bi-eye"></i></a>
              <?php if ($o['estado'] === 'encaminhada_professor'): ?>
              <a href="?ver=<?= $o['id'] ?>#responder" class="btn-icone aviso" title="Responder">
                <i class="bi bi-reply-fill"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal de detalhe + resposta -->
<?php if ($verOcorr): ?>
<div class="modal fade show" id="modalDetalhe" tabindex="-1" style="display:block" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="ticket-badge"><?= Sanitizar::html($verOcorr['ticket']) ?></span>
          <span class="badge-estado <?= Etiqueta::badgeEstado($verOcorr['estado']) ?>">
            <i class="bi <?= Etiqueta::iconeEstado($verOcorr['estado']) ?>"></i>
            <?= Etiqueta::estado($verOcorr['estado']) ?>
          </span>
        </div>
        <a href="ocorrencias.php" class="btn-close btn-close-white"></a>
      </div>
      <div class="modal-body p-4">
        <!-- Dados do estudante -->
        <div class="row g-3 mb-4">
          <div class="col-sm-6"><small class="td-muted d-block">Estudante</small><strong><?= Sanitizar::html($verOcorr['estudante_nome']) ?></strong></div>
          <div class="col-sm-6"><small class="td-muted d-block">Nº Estudante</small><?= Sanitizar::html($verOcorr['estudante_numero']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Contacto</small><?= Sanitizar::html($verOcorr['estudante_telefone']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Disciplina</small><?= Sanitizar::html($verOcorr['disciplina_nome']) ?></div>
          <div class="col-sm-4"><small class="td-muted d-block">Tipo Avaliação</small><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($verOcorr['tipo_avaliacao']) ?></span></div>
          <div class="col-sm-6"><small class="td-muted d-block">Turma</small>Turma <?= Sanitizar::html($verOcorr['turma_codigo']) ?></div>
          <div class="col-sm-6"><small class="td-muted d-block">Submetida em</small><?= Formatar::dataHora($verOcorr['criado_em']) ?></div>
          <div class="col-12">
            <small class="td-muted d-block mb-1">Descrição do Problema</small>
            <div style="background:#f7f9fd;border:1px solid var(--borda);border-radius:8px;padding:14px;font-size:13.5px;line-height:1.7">
              <?= nl2br(Sanitizar::html($verOcorr['descricao'])) ?>
            </div>
          </div>
        </div>

        <!-- Respostas anteriores -->
        <?php if (!empty($verRespostas)): ?>
        <h6 class="fw-bold mb-2" style="color:var(--primario)"><i class="bi bi-chat-left-quote me-2"></i>As Minhas Respostas Anteriores</h6>
        <?php foreach ($verRespostas as $r): ?>
        <div style="background:#ede9fe;border-left:4px solid var(--primario);border-radius:8px;padding:12px;margin-bottom:8px;font-size:13px">
          <?= nl2br(Sanitizar::html($r['resposta'])) ?>
          <div class="td-muted mt-1" style="font-size:11px"><?= Formatar::dataHora($r['criado_em']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Formulário de resposta -->
        <?php if ($verOcorr['estado'] === 'encaminhada_professor'): ?>
        <div id="responder" style="border:2px solid var(--destaque);border-radius:12px;padding:18px;margin-top:16px;background:#fffbeb">
          <h6 class="fw-bold mb-3" style="color:var(--primario)">
            <i class="bi bi-reply-fill me-2"></i>Submeter Resposta / Parecer
          </h6>
          <form method="POST">
            <?= Sessao::campoCsrf() ?>
            <input type="hidden" name="_acao" value="responder">
            <input type="hidden" name="id"    value="<?= $verOcorr['id'] ?>">
            <div class="mb-3">
              <label class="rotulo-campo">Resposta / Parecer *</label>
              <textarea name="resposta" class="campo-input" rows="5" required
                        placeholder="Descreva a sua análise, erro identificado ou medida a tomar..."></textarea>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-2"></i>Submeter Resposta
              </button>
              <a href="ocorrencias.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
          </form>
        </div>
        <?php endif; ?>

        <!-- Histórico -->
        <?php if (!empty($verHistorico)): ?>
        <hr class="mt-4">
        <h6 class="fw-bold mb-3" style="color:var(--primario)"><i class="bi bi-clock-history me-2"></i>Histórico de Estados</h6>
        <div class="timeline">
          <?php foreach ($verHistorico as $h): ?>
          <div class="timeline-item">
            <div class="timeline-ponto">
              <i class="bi <?= Etiqueta::iconeEstado($h['estado_novo']) ?>" style="font-size:11px"></i>
            </div>
            <div class="timeline-corpo">
              <div style="font-size:12px;font-weight:600">
                <?php if ($h['estado_anterior']): ?>
                <span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_anterior']) ?>" style="font-size:10px"><?= Etiqueta::estado($h['estado_anterior']) ?></span>
                <i class="bi bi-arrow-right" style="font-size:10px;color:var(--texto-muted);margin:0 4px"></i>
                <?php endif; ?>
                <span class="badge-estado <?= Etiqueta::badgeEstado($h['estado_novo']) ?>" style="font-size:10px"><?= Etiqueta::estado($h['estado_novo']) ?></span>
              </div>
              <div class="td-muted" style="font-size:11px"><?= Sanitizar::html($h['usuario_nome'] ?? 'Sistema') ?> &middot; <?= Formatar::dataHora($h['criado_em']) ?></div>
              <?php if ($h['observacao']): ?>
              <div style="font-size:12px;font-style:italic;color:var(--texto-sec);margin-top:2px"><?= Sanitizar::html($h['observacao']) ?></div>
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
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
