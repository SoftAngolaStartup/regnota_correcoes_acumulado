<?php
/**
 * public/professor/disciplinas.php
 * Disciplinas Atribuídas ao Professor (apenas visualização)
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta};
use App\Models\ProfessorModelo;
use App\Middleware\Autenticacao;

Autenticacao::exigirPerfil('professor');

$uid  = Sessao::utilizador()['id'];
$prof = (new ProfessorModelo())->obterPorUsuario($uid);
if (!$prof) { Url::redirecionar(Url::publico('login.php')); }

$disciplinas = (new ProfessorModelo())->listarDisciplinas($prof['id']);

$menuLateral = [
    'Principal' => [
        ['id'=>'dashboard',  'titulo'=>'Dashboard',   'url'=>Url::professor('index.php'),       'icone'=>'bi-speedometer2'],
        ['id'=>'ocorrencias','titulo'=>'Ocorrências', 'url'=>Url::professor('ocorrencias.php'), 'icone'=>'bi-exclamation-triangle'],
    ],
    'As Minhas Disciplinas' => [
        ['id'=>'disciplinas','titulo'=>'Disciplinas', 'url'=>Url::professor('disciplinas.php'), 'icone'=>'bi-book'],
    ],
];
$tituloPagina = 'As Minhas Disciplinas';
$paginaActiva = 'disciplinas';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="alerta alerta-info mb-4">
  <i class="bi bi-info-circle-fill" style="flex-shrink:0"></i>
  <span>Estas são as disciplinas que lhe foram atribuídas. Para alterações, contacte a coordenação.</span>
</div>
<div class="row g-3">
  <?php if (empty($disciplinas)): ?>
  <div class="col-12">
    <div class="glass-card sem-dados p-5">
      <i class="bi bi-book" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:10px"></i>
      <p>Nenhuma disciplina atribuída ainda.</p>
    </div>
  </div>
  <?php else: ?>
  <?php foreach ($disciplinas as $d): ?>
  <div class="col-md-6 col-xl-4">
    <div class="glass-card h-100 hover-up">
      <div style="padding:20px">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div>
            <div style="font-weight:700;font-size:15px;color:var(--primario)"><?= Sanitizar::html($d['nome']) ?></div>
            <?php if ($d['codigo']): ?><span class="chip-tipo mt-1"><?= Sanitizar::html($d['codigo']) ?></span><?php endif; ?>
          </div>
          <span class="badge-estado <?= $d['ativo'] ? 'badge-resolvida' : 'badge-cancelada' ?>"><?= $d['ativo'] ? 'Activa' : 'Inactiva' ?></span>
        </div>
        <div class="mt-3">
          <div class="d-flex align-items-center gap-2 td-muted mb-1" style="font-size:12.5px">
            <i class="bi bi-mortarboard"></i> <?= Sanitizar::html($d['curso_nome']) ?>
          </div>
          <div class="d-flex align-items-center gap-2 td-muted mb-1" style="font-size:12.5px">
            <i class="bi bi-calendar3"></i> <?= Etiqueta::anoNivel((int)$d['ano_nivel']) ?> &middot; <?= $d['semestre_num'] ?>º Semestre
          </div>
        </div>
        <div class="mt-3 pt-3" style="border-top:1px solid rgba(0,0,0,0.05)">
          <a href="<?= Url::professor('ocorrencias.php?disciplina_id='.$d['id']) ?>" class="btn-base btn-fantasma w-100 justify-content-center">
            <i class="bi bi-exclamation-triangle me-1"></i> Ver Ocorrências
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
