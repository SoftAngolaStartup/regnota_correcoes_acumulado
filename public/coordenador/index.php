<?php
/**
 * public/coordenador/index.php
 * Dashboard do Coordenador
 * Acesso restrito ao seu próprio curso
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Formatar};
use App\Helpers\Bd;
use App\Models\{OcorrenciaModelo, CoordenadorModelo, DisciplinaModelo, ProfessorModelo};
use App\Middleware\Autenticacao;

Autenticacao::exigirPerfil(['coordenador','super_admin']);
Autenticacao::verificarPrimeiroLogin();

$uid   = Sessao::utilizador()['id'];
$coord = (new CoordenadorModelo())->obterPorUsuario($uid);
if (!$coord && \App\Helpers\Sessao::temPerfil('super_admin')) { \App\Helpers\Url::redirecionar(\App\Helpers\Url::admin('index.php')); }
if (!$coord) { Sessao::flash('erro','Sem curso associado.'); Url::redirecionar(Url::publico('login.php')); }

$cursoId   = $coord['curso_id'];
$om        = new OcorrenciaModelo();
$dm        = new DisciplinaModelo();

$estatisticas  = $om->estatisticasGerais(); // filtrado pelo curso
$recentes      = $om->listarTodas(['curso_id'=>$cursoId], 10, 0);
$totalDiscs    = count($dm->listarTodas(['curso_id'=>$cursoId,'ativo'=>1]));

// Menu do Coordenador
$menuLateral = [
    'Principal' => [
        ['id'=>'dashboard',  'titulo'=>'Dashboard',   'url'=>Url::coordenador('index.php'),       'icone'=>'bi-speedometer2'],
        ['id'=>'ocorrencias','titulo'=>'Ocorrências', 'url'=>Url::coordenador('ocorrencias.php'), 'icone'=>'bi-exclamation-triangle'],
    ],
    'Curso' => [
        ['id'=>'disciplinas','titulo'=>'Disciplinas', 'url'=>Url::coordenador('disciplinas.php'), 'icone'=>'bi-book'],
        ['id'=>'professores','titulo'=>'Professores', 'url'=>Url::coordenador('professores.php'), 'icone'=>'bi-person-badge'],
        ['id'=>'turmas',     'titulo'=>'Turmas',      'url'=>Url::coordenador('turmas.php'),      'icone'=>'bi-people'],
    ],
    'Relatórios' => [
        ['id'=>'relatorios','titulo'=>'Exportar PDF','url'=>Url::coordenador('relatorios.php'),'icone'=>'bi-file-earmark-pdf'],
    ],
];

$tituloPagina = 'Dashboard – ' . $coord['curso_nome'];
$paginaActiva = 'dashboard';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';

// Recolher estatísticas do curso
$statsCurso = $om->estatisticasGerais();
$tempoMedio = $om->obterTempoMedioResolucao();
// Obter dados específicos do curso
$pdo = Bd::obter();
$stmtCurso = $pdo->prepare("SELECT COUNT(*) AS total, SUM(estado='submetida') AS submetidas, SUM(estado='resolvida') AS resolvidas, SUM(estado='encaminhada_professor') AS encaminhadas FROM ocorrencias WHERE curso_id=?");
$stmtCurso->execute([$cursoId]);
$statsCurso = $stmtCurso->fetch();
?>
<div class="alerta alerta-info mb-4">
  <i class="bi bi-info-circle-fill" style="flex-shrink:0"></i>
  <span>Está a gerir o curso: <strong><?= Sanitizar::html($coord['curso_nome']) ?></strong>. Apenas visualiza dados do seu curso.</span>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
    ['num'=>$statsCurso['total']??0,      'rot'=>'Total do Curso',  'classe'=>'ico-outro','icor'=>'var(--academic-navy)','ico'=>'bi-clipboard-data-fill'],
    ['num'=>$statsCurso['submetidas']??0,  'rot'=>'Pendentes',      'classe'=>'ico-submetida','icor'=>'var(--state-submetida)','ico'=>'bi-send-fill'],
    ['num'=>$statsCurso['encaminhadas']??0,'rot'=>'Encaminhadas',   'classe'=>'ico-encaminhada','icor'=>'var(--state-encaminhada)','ico'=>'bi-arrow-right-circle'],
    ['num'=>$statsCurso['resolvidas']??0,  'rot'=>'Resolvidas',     'classe'=>'ico-resolvida','icor'=>'var(--state-resolvida)','ico'=>'bi-check-circle-fill'],
    ['num'=>$tempoMedio,                   'rot'=>'T. Médio (h)',   'classe'=>'ico-outro','icor'=>'var(--academic-gold-dark)','ico'=>'bi-clock-history'],
    ['num'=>$totalDiscs,                   'rot'=>'Disciplinas',    'classe'=>'ico-outro','icor'=>'var(--academic-blue)','ico'=>'bi-book-fill'],
  ] as $c): ?>
  <div class="col-6 col-xl-2">
    <div class="card-estatistica <?= $c['classe'] ?> hover-up">
      <div class="icone-stat mx-auto mb-2" style="width:40px;height:40px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:rgba(11,29,58,.06);color:<?= $c['icor'] ?>"><i class="bi <?= $c['ico'] ?>"></i></div>
      <div class="numero"><?= is_float($c['num']) ? number_format($c['num'], 1) : number_format((int)$c['num']) ?></div>
      <div class="rotulo"><?= $c['rot'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card-conteudo mb-4">
  <div class="card-conteudo-header">
    <span class="card-conteudo-title"><i class="bi bi-clock-history"></i> Ocorrências Recentes</span>
    <a href="<?= Url::coordenador('ocorrencias.php') ?>" class="btn-base btn-fantasma btn-sm">Ver todas</a>
  </div>
  <div class="card-conteudo-body" style="overflow-x:auto">
    <table class="tabela-academica mb-0">
      <thead><tr><th>Ticket</th><th>Estudante</th><th>Turma</th><th>Disciplina</th><th>Tipo</th><th>Data</th><th>Estado</th></tr></thead>
      <tbody>
        <?php if (empty($recentes)): ?>
        <tr><td colspan="7"><div class="estado-vazio"><i class="bi bi-inbox icon"></i><p class="titulo">Nenhuma ocorrência registada</p><p class="descricao">Crie a sua primeira ocorrência para começar a acompanhar.</p></div></td></tr>
        <?php else: foreach ($recentes as $o): ?>
        <tr>
          <td><a href="<?= Url::coordenador('ocorrencias.php?ver='.$o['id']) ?>" class="ticket-badge text-no-underline"><?= Sanitizar::html($o['ticket']) ?></a></td>
          <td class="td-nome"><?= Sanitizar::html($o['estudante_nome']) ?></td>
          <td class="td-muted">Turma <?= Sanitizar::html($o['turma_codigo']) ?></td>
          <td style="font-size:var(--text-sm)"><?= Sanitizar::html($o['disciplina_nome']) ?></td>
          <td><span class="chip-tipo"><?= Etiqueta::tipoAvaliacao($o['tipo_avaliacao']) ?></span></td>
          <td class="td-muted"><?= Formatar::tempoRelativo($o['criado_em']) ?></td>
          <td><span class="badge-estado <?= Etiqueta::badgeEstado($o['estado']) ?>" style="font-size:var(--text-xs)"><i class="bi <?= Etiqueta::iconeEstado($o['estado']) ?>"></i> <?= Etiqueta::estado($o['estado']) ?></span></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
