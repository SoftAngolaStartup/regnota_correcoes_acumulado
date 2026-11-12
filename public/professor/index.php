<?php
/**
 * public/professor/index.php
 * Dashboard do Professor
 * Vê apenas as ocorrências das suas disciplinas
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Formatar};
use App\Models\{ProfessorModelo, OcorrenciaModelo, DisciplinaModelo};
use App\Middleware\Autenticacao;
use App\Helpers\Bd;

Autenticacao::exigirPerfil('professor');
Autenticacao::verificarPrimeiroLogin();

$uid  = Sessao::utilizador()['id'];
$pm   = new ProfessorModelo();
$prof = $pm->obterPorUsuario($uid);

if (!$prof) {
    Sessao::flash('erro', 'Perfil de professor não encontrado.');
    Url::redirecionar(Url::publico('login.php'));
}

$dm   = new DisciplinaModelo();
$om   = new OcorrenciaModelo();

// Disciplinas deste professor
$disciplinas = $pm->listarDisciplinas($prof['id']);

// Ocorrências das suas disciplinas
$pdo  = Bd::obter();
$stmt = $pdo->prepare("
    SELECT o.*, d.nome AS disciplina_nome, t.codigo AS turma_codigo,
           s.nome AS semestre_nome, c.nome AS curso_nome
    FROM ocorrencias o
    JOIN disciplinas d ON o.disciplina_id = d.id
    JOIN turmas t      ON o.turma_id      = t.id
    JOIN semestres s   ON o.semestre_id   = s.id
    JOIN cursos c      ON o.curso_id      = c.id
    WHERE d.professor_id = ?
    ORDER BY o.criado_em DESC
    LIMIT 15
");
$stmt->execute([$prof['id']]);
$ocorrencias = $stmt->fetchAll();

// Contadores
$contadores = ['total'=>0,'pendentes'=>0,'encaminhadas'=>0,'respondidas'=>0];
foreach ($ocorrencias as $o) {
    $contadores['total']++;
    if ($o['estado'] === 'submetida' || $o['estado'] === 'em_analise') $contadores['pendentes']++;
    if ($o['estado'] === 'encaminhada_professor') $contadores['encaminhadas']++;
    if ($o['estado'] === 'respondida_professor')  $contadores['respondidas']++;
}

// Menu do Professor
$menuLateral = [
    'Principal' => [
        ['id'=>'dashboard',  'titulo'=>'Dashboard',    'url'=>Url::professor('index.php'),       'icone'=>'bi-speedometer2'],
        ['id'=>'ocorrencias','titulo'=>'Ocorrências',  'url'=>Url::professor('ocorrencias.php'), 'icone'=>'bi-exclamation-triangle',
         'badge' => $contadores['encaminhadas'] > 0 ? $contadores['encaminhadas'] : null],
    ],
    'As Minhas Disciplinas' => [
        ['id'=>'disciplinas','titulo'=>'Disciplinas',  'url'=>Url::professor('disciplinas.php'), 'icone'=>'bi-book'],
    ],
];

$tituloPagina = 'Dashboard – Prof. ' . mb_substr($prof['nome'], 0, 20);
$paginaActiva = 'dashboard';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>

<!-- Cards de contadores -->
<div class="row g-3 mb-4">
  <?php foreach ([
    ['num'=>count($disciplinas),           'rot'=>'As Minhas Disciplinas', 'cor'=>'rgba(25, 103, 210, 0.1)','icor'=>'#1967d2','ico'=>'bi-book-fill'],
    ['num'=>$contadores['total'],          'rot'=>'Total de Ocorrências',  'cor'=>'rgba(55, 65, 81, 0.1)','icor'=>'#374151','ico'=>'bi-clipboard-data'],
    ['num'=>$contadores['encaminhadas'],   'rot'=>'Aguardam Resposta',     'cor'=>'rgba(217, 119, 6, 0.1)','icor'=>'#d97706','ico'=>'bi-hourglass-split'],
    ['num'=>$contadores['respondidas'],    'rot'=>'Respondidas',           'cor'=>'rgba(5, 150, 105, 0.1)','icor'=>'#059669','ico'=>'bi-check-circle-fill'],
  ] as $c): ?>
  <div class="col-6 col-md-3">
    <div class="glass-card p-3 text-center hover-up">
      <div class="icone-stat mx-auto mb-2" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:<?= $c['cor'] ?>;color:<?= $c['icor'] ?>">
        <i class="bi <?= $c['ico'] ?>"></i>
      </div>
      <div class="h4 mb-0 fw-bold"><?= (int)$c['num'] ?></div>
      <div class="small text-muted"><?= $c['rot'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Ocorrências encaminhadas com destaque -->
<?php
$encaminhadas = array_filter($ocorrencias, fn($o) => $o['estado'] === 'encaminhada_professor');
if (!empty($encaminhadas)):
?>
<div class="alerta alerta-aviso mb-4">
  <i class="bi bi-bell-fill" style="flex-shrink:0;font-size:15px"></i>
  <span>Tem <strong><?= count($encaminhadas) ?></strong> ocorrência(s) encaminhadas aguardando a sua resposta.</span>
  <a href="<?= Url::professor('ocorrencias.php?estado=encaminhada_professor') ?>" class="ms-auto btn-base btn-destaque btn-sm">Ver agora</a>
</div>
<?php endif; ?>

<!-- Disciplinas -->
<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="fw-bold"><i class="bi bi-book-half"></i> As Minhas Disciplinas</span>
    <a href="<?= Url::professor('disciplinas.php') ?>" class="btn-base btn-fantasma btn-sm">Ver todas</a>
  </div>
  <div style="overflow-x:auto">
    <table class="tabela-sistema mb-0">
      <thead>
        <tr><th>Disciplina</th><th>Código</th><th>Curso</th><th>Ano</th><th>Semestre</th></tr>
      </thead>
      <tbody>
        <?php if (empty($disciplinas)): ?>
        <tr><td colspan="5"><div class="sem-dados"><i class="bi bi-book"></i><p>Nenhuma disciplina atribuída.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($disciplinas as $d): ?>
        <tr>
          <td class="td-nome"><?= Sanitizar::html($d['nome']) ?></td>
          <td><?= $d['codigo'] ? '<span class="chip-tipo">'.Sanitizar::html($d['codigo']).'</span>' : '—' ?></td>
          <td class="td-muted"><?= Sanitizar::html($d['curso_nome']) ?></td>
          <td><?= Etiqueta::anoNivel((int)$d['ano_nivel']) ?></td>
          <td><?= $d['semestre_num'] ?>º Semestre</td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Últimas ocorrências -->
<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="fw-bold"><i class="bi bi-clock-history"></i> Ocorrências Recentes</span>
    <a href="<?= Url::professor('ocorrencias.php') ?>" class="btn-base btn-fantasma btn-sm">Ver todas</a>
  </div>
  <div style="overflow-x:auto">
    <table class="tabela-sistema mb-0">
      <thead>
        <tr><th>Ticket</th><th>Estudante</th><th>Disciplina</th><th>Tipo</th><th>Data</th><th>Estado</th></tr>
      </thead>
      <tbody>
        <?php if (empty($ocorrencias)): ?>
        <tr><td colspan="6"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhuma ocorrência nas suas disciplinas.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($ocorrencias as $o): ?>
        <tr>
          <td><a href="<?= Url::professor('ocorrencias.php?ver='.$o['id']) ?>" class="ticket-badge text-decoration-none"><?= Sanitizar::html($o['ticket']) ?></a></td>
          <td class="td-nome"><?= Sanitizar::html($o['estudante_nome']) ?></td>
          <td style="font-size:13px"><?= Sanitizar::html($o['disciplina_nome']) ?></td>
          <td><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></span></td>
          <td class="td-muted"><?= Formatar::tempoRelativo($o['criado_em']) ?></td>
          <td>
            <span class="badge-estado <?= Etiqueta::badgeEstado($o['estado']) ?>" style="font-size:11px">
              <i class="bi <?= Etiqueta::iconeEstado($o['estado']) ?>"></i>
              <?= Etiqueta::estado($o['estado']) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
