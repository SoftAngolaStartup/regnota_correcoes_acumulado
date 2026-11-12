<?php
/**
 * public/admin/disciplinas.php
 * Gestão de Disciplinas – Visualização por Curso e Ano
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Auditoria};
use App\Models\{DisciplinaModelo, CursoModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$dm  = new DisciplinaModelo();
$cm  = new CursoModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) { Sessao::flash('erro','Token inválido.'); header('Location: disciplinas.php'); exit; }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'eliminar') {
        $id=(int)($_POST['id']??0);
        if (!$dm->eliminar($id)) Sessao::flash('erro','Não é possível eliminar: existem ocorrências associadas.');
        else Sessao::flash('sucesso','Disciplina eliminada!');
    }
    header('Location: disciplinas.php?curso_id='.($_POST['f_curso']??'').'&ano_nivel='.($_POST['f_ano']??'')); exit;
}

// Determine current step (simplificado: 1=Curso, 2=Ano)
$curso_id = (int)($_GET['curso_id'] ?? 0);
$ano_nivel = (int)($_GET['ano_nivel'] ?? 0);

$step = ($curso_id) ? ($ano_nivel ? 3 : 2) : 1;

$cursos = $cm->listarTodos(true);
$disciplinas = [];

if ($step === 3) {
    // Busca todas as disciplinas do ano selecionado (ambos semestres)
    $filtros = ['curso_id' => $curso_id, 'ano_nivel' => $ano_nivel];
    $disciplinas = $dm->listarTodas($filtros);
}

$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Disciplinas'; $paginaActiva = 'disciplinas';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';

// Helper function to build URL for steps
function stepUrl($params) {
    $base = 'disciplinas.php';
    $current = array_filter(['curso_id' => $_GET['curso_id'] ?? null, 'ano_nivel' => $_GET['ano_nivel'] ?? null]);
    $new = array_merge($current, $params);
    return $base . '?' . http_build_query($new);
}

// Mapeamento de ícones por nome de curso
$iconesCurso = [
    'Ciências da Computação' => 'bi-laptop',
    'Economia'               => 'bi-graph-up',
    'Gestão'                 => 'bi-briefcase',
    'Medicina Dentária'      => 'bi-hospital',
    'Engenharia'             => 'bi-tools'
];
?>

<div class="mb-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="disciplinas.php">Cursos</a></li>
      <?php if($step >= 2): ?>
        <li class="breadcrumb-item"><a href="<?= stepUrl(['ano_nivel' => null]) ?>">Anos</a></li>
      <?php endif; ?>
    </ol>
  </nav>
</div>

<?php if($step === 1): ?>
  <h3>Selecione o Curso</h3>
  <div class="row g-3">
    <?php foreach($cursos as $c): 
        $icone = $iconesCurso[$c['nome']] ?? 'bi-book';
    ?>
      <div class="col-md-4">
        <a href="<?= stepUrl(['curso_id' => $c['id']]) ?>" class="card text-decoration-none p-3 shadow-sm hover-up">
          <div class="d-flex align-items-center gap-3">
            <i class="bi <?= $icone ?> fs-3 text-primary"></i>
            <h5><?= Sanitizar::html($c['nome']) ?></h5>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php elseif($step === 2): ?>
  <h3>Selecione o Ano</h3>
  <div class="row g-3">
    <?php for($a=1; $a<=5; $a++): ?>
      <div class="col-md-2">
        <a href="<?= stepUrl(['ano_nivel' => $a]) ?>" class="card text-decoration-none p-3 shadow-sm hover-up text-center">
          <h5><?= Etiqueta::anoNivel($a) ?></h5>
        </a>
      </div>
    <?php endfor; ?>
  </div>
<?php else: ?>
  <div class="mb-3">
    <h4><?= Sanitizar::html($cursos[array_search($curso_id, array_column($cursos, 'id'))]['nome'] ?? 'Curso') ?> - <?= Etiqueta::anoNivel($ano_nivel) ?></h4>
  </div>
  
  <div class="row g-4 mt-3">
    <div class="col-md-12">
        <div class="card p-0 shadow-sm">
            <div class="card-header bg-light fw-bold">Todas as Disciplinas</div>
            <div class="card-body p-0">
                <table class="tabela-sistema mb-0" style="font-size:13px">
                    <thead><tr><th>Matéria</th><th>Cód.</th><th>Professor</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php if(empty($disciplinas)): ?>
                            <tr><td colspan="4" class="text-center py-3 text-muted">Sem disciplinas definidas</td></tr>
                        <?php else: foreach($disciplinas as $d): ?>
                            <tr>
                                <td class="td-nome"><?= Sanitizar::html($d['nome']) ?></td>
                                <td><span class="chip-tipo"><?= Sanitizar::html($d['codigo'] ?? '—') ?></span></td>
                                <td class="td-muted"><?= $d['professor_nome'] ? Sanitizar::html($d['professor_nome']) : '<span class="text-danger fw-bold">Não atribuído</span>' ?></td>
                                <td><span class="badge-estado <?= $d['ativo']?'badge-resolvida':'badge-cancelada' ?>"><?= $d['ativo']?'Activa':'Inactiva' ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
  <?php endif; ?>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
