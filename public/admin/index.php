<?php
/**
 * public/admin/index.php
 * Dashboard do Super Administrador
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Url, Etiqueta, Formatar, Sanitizar};
use App\Models\{OcorrenciaModelo, CursoModelo, ProfessorModelo, CoordenadorModelo, AnoLetivoModelo};
use App\Middleware\Autenticacao;

Autenticacao::exigirAdmin();
Autenticacao::verificarPrimeiroLogin();

$om  = new OcorrenciaModelo();
$cm  = new CursoModelo();
$pm  = new ProfessorModelo();
$com = new CoordenadorModelo();
$alm = new AnoLetivoModelo();

$estatisticas  = $om->estatisticasGerais();
$porCurso      = $om->estatisticasPorCurso();
$porTipo       = $om->estatisticasPorTipoAvaliacao();
$porDisciplina = $om->estatisticasPorDisciplina(8);
$recentes      = $om->obterRecentes(10);
$anoAtivo      = $alm->obterAtivo();
$totalCursos   = count($cm->listarTodos(true));
$totalProfs    = count($pm->listarTodos(true));

$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Dashboard';
$paginaActiva = 'dashboard';
require_once __DIR__ . '/../../views/shared/cabecalho_painel.php';
?>

<!-- Estatísticas Principais -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['num'=>$estatisticas['total'],      'rot'=>'Total',         'ico'=>'bi-clipboard-data', 'link'=>''],
    ['num'=>$estatisticas['submetidas'], 'rot'=>'Submetidas',    'ico'=>'bi-send',           'link'=>'estado=submetida'],
    ['num'=>$estatisticas['em_analise'], 'rot'=>'Em Análise',    'ico'=>'bi-hourglass',      'link'=>'estado=em_analise'],
    ['num'=>$estatisticas['encaminhadas'],'rot'=>'Encaminhadas', 'ico'=>'bi-arrow-right-circle', 'link'=>'estado=encaminhada_professor'],
    ['num'=>$estatisticas['resolvidas'], 'rot'=>'Resolvidas',    'ico'=>'bi-check-circle',   'link'=>'estado=resolvida'],
    ['num'=>$estatisticas['canceladas'], 'rot'=>'Canceladas',    'ico'=>'bi-x-circle',       'link'=>'estado=cancelada'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <a href="<?= Url::admin('ocorrencias.php') . ($c['link'] ? '?' . $c['link'] : '') ?>" class="text-decoration-none">
        <div class="card-estatistica hover-up">
            <div class="text-primary mb-2"><i class="bi <?= $c['ico'] ?> fs-4"></i></div>
            <div class="fs-4 fw-bold text-dark"><?= number_format((int)$c['num']) ?></div>
            <div class="small text-muted"><?= $c['rot'] ?></div>
        </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Meta Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card-estatistica d-flex align-items-center gap-3">
        <div class="p-2 bg-success text-white rounded"><i class="bi bi-mortarboard fs-5"></i></div>
        <div><div class="fw-bold"><?= $totalCursos ?></div><div class="small text-muted">Cursos</div></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-estatistica d-flex align-items-center gap-3">
        <div class="p-2 bg-purple text-white rounded"><i class="bi bi-person-badge fs-5"></i></div>
        <div><div class="fw-bold"><?= $totalProfs ?></div><div class="small text-muted">Professores</div></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-estatistica d-flex align-items-center gap-3">
        <div class="p-2 bg-warning text-white rounded"><i class="bi bi-person-workspace fs-5"></i></div>
        <div><div class="fw-bold"><?= count($com->listarTodos()) ?></div><div class="small text-muted">Coordenadores</div></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-estatistica d-flex align-items-center gap-3">
        <div class="p-2 bg-primary text-white rounded"><i class="bi bi-calendar-check fs-5"></i></div>
        <div><div class="fw-bold"><?= Sanitizar::html($anoAtivo['nome'] ?? '—') ?></div><div class="small text-muted">Ano Ativo</div></div>
    </div>
  </div>
</div>

<!-- Gráficos -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card-conteudo h-100 p-0">
            <div class="card-conteudo-header"><span class="card-conteudo-titulo"><i class="bi bi-pie-chart"></i> Ocorrências por Tipo</span></div>
            <div class="card-conteudo-corpo">
                <?php foreach ($porTipo as $item): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-medium text-truncate" style="max-width: 70%"><?= Etiqueta::tipoAvaliacao($item['tipo_avaliacao']) ?></span>
                            <span class="small fw-bold"><?= $item['total'] ?></span>
                        </div>
                        <div class="progresso-mini"><div class="progresso-mini-barra" style="width:<?= ($estatisticas['total'] > 0 ? ($item['total']/$estatisticas['total']*100) : 0) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-conteudo h-100 p-0">
            <div class="card-conteudo-header"><span class="card-conteudo-titulo"><i class="bi bi-mortarboard"></i> Top Cursos</span></div>
            <div class="card-conteudo-corpo">
                <?php foreach ($porCurso as $item): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-medium text-truncate" style="max-width: 70%"><?= Sanitizar::html($item['curso']) ?></span>
                            <span class="small fw-bold"><?= $item['total'] ?></span>
                        </div>
                        <div class="progresso-mini"><div class="progresso-mini-barra" style="width:<?= ($estatisticas['total'] > 0 ? ($item['total']/$estatisticas['total']*100) : 0) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-conteudo h-100 p-0">
            <div class="card-conteudo-header"><span class="card-conteudo-titulo"><i class="bi bi-book"></i> Top Disciplinas</span></div>
            <div class="card-conteudo-corpo">
                <?php foreach ($porDisciplina as $item): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-medium text-truncate" style="max-width: 70%"><?= Sanitizar::html($item['disciplina']) ?></span>
                            <span class="small fw-bold"><?= $item['total'] ?></span>
                        </div>
                        <div class="progresso-mini"><div class="progresso-mini-barra" style="width:<?= ($estatisticas['total'] > 0 ? ($item['total']/$estatisticas['total']*100) : 0) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/shared/rodape_painel.php'; ?>
