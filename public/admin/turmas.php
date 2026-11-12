<?php
/**
 * public/admin/turmas.php
 * Gestão de Turmas - Fluxo Simplificado (Curso -> Ano)
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Auditoria};
use App\Models\{TurmaModelo, CursoModelo, AnoLetivoModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$tm  = new TurmaModelo();
$cm  = new CursoModelo();
$alm = new AnoLetivoModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) { Sessao::flash('erro','Token inválido.'); header('Location: turmas.php'); exit; }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'gerar') {
        $cursoId  = (int)($_POST['curso_id'] ?? 0);
        $anoId    = (int)($_POST['ano_letivo_id'] ?? 0);
        $anoNivel = (int)($_POST['ano_nivel'] ?? 0);
        $semestre = (int)($_POST['semestre_num'] ?? 0);
        $qtd      = min(max((int)($_POST['quantidade'] ?? 2), 1), 9);
        if ($cursoId && $anoId && $anoNivel >= 1 && $anoNivel <= 5 && $semestre >= 1 && $semestre <= 2) {
            $criadas = $tm->gerarAutomaticamente($cursoId, $anoId, $anoNivel, $semestre, $qtd);
            Auditoria::registar('gerar_turmas','turmas',null,"Geradas $criadas turmas para nível $anoNivel, semestre $semestre",$uid);
            Sessao::flash('sucesso', "$criadas turma(s) gerada(s) com sucesso!");
        } else Sessao::flash('erro','Preencha todos os campos obrigatórios.');
    } elseif ($acao === 'alternar') {
        $id   = (int)($_POST['id'] ?? 0);
        $ativo= (int)($_POST['ativo'] ?? 0);
        if ($id) { $tm->actualizar($id, $ativo); Sessao::flash('sucesso','Estado da turma actualizado!'); }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$tm->eliminar($id)) Sessao::flash('erro','Não é possível eliminar: existem ocorrências associadas.');
        else Sessao::flash('sucesso','Turma eliminada!');
    }
    header('Location: turmas.php?curso_id='.($_POST['f_curso']??'').'&ano_nivel='.($_POST['f_ano']??'')); exit;
}

// Determine current step (Curso -> Ano)
$curso_id = (int)($_GET['curso_id'] ?? 0);
$ano_nivel = (int)($_GET['ano_nivel'] ?? 0);

$step = ($curso_id) ? ($ano_nivel ? 3 : 2) : 1;

$cursos = $cm->listarTodos(true);
$anos   = $alm->listarTodos();
$turmas = [];

if ($step === 3) {
    $turmas = $tm->listarTodas(array_filter(['curso_id'=>$curso_id,'ano_nivel'=>$ano_nivel]));
}

$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Turmas'; $paginaActiva = 'turmas';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';

function stepUrl($params) {
    $base = 'turmas.php';
    $current = array_filter(['curso_id' => $_GET['curso_id'] ?? null, 'ano_nivel' => $_GET['ano_nivel'] ?? null]);
    $new = array_merge($current, $params);
    return $base . '?' . http_build_query($new);
}
?>

<div class="mb-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="turmas.php">Cursos</a></li>
      <?php if($step >= 2): ?><li class="breadcrumb-item"><a href="<?= stepUrl(['ano_nivel' => null]) ?>">Anos</a></li><?php endif; ?>
    </ol>
  </nav>
</div>

<?php
$iconesCurso = ['Ciências da Computação' => 'bi-laptop', 'Economia' => 'bi-graph-up', 'Gestão' => 'bi-briefcase', 'Medicina Dentária' => 'bi-hospital', 'Engenharia' => 'bi-tools'];
?>

<?php if($step === 1): ?>
  <h3>Selecione o Curso</h3>
  <div class="row g-3">
    <?php foreach($cursos as $c): $icone = $iconesCurso[$c['nome']] ?? 'bi-book'; ?>
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
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?= Sanitizar::html($cursos[array_search($curso_id, array_column($cursos, 'id'))]['nome'] ?? 'Curso') ?> - <?= Etiqueta::anoNivel($ano_nivel) ?></h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGerar"><i class="bi bi-magic me-1"></i> Gerar Turmas</button>
  </div>
  
  <div class="row g-4 mt-3">
    <div class="col-md-12">
        <div class="card p-0 shadow-sm">
            <div class="card-header bg-light fw-bold">Todas as Turmas</div>
            <div class="card-body p-0">
                <table class="tabela-sistema mb-0">
                    <thead><tr><th>Código</th><th>Estado</th><th>Acções</th></tr></thead>
                    <tbody>
                        <?php if(empty($turmas)): ?>
                            <tr><td colspan="3" class="text-center py-3 text-muted">Sem turmas</td></tr>
                        <?php else: foreach($turmas as $t): ?>
                            <tr>
                                <td class="td-nome"><?= Sanitizar::html($t['codigo']) ?></td>
                                <td><span class="badge-estado <?= $t['ativo']?'badge-resolvida':'badge-cancelada' ?>"><?= $t['ativo']?'Activa':'Inactiva' ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                      <form method="POST" style="margin:0"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" value="alternar"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="ativo" value="<?= $t['ativo']?0:1 ?>"><input type="hidden" name="f_curso" value="<?= $curso_id ?>"><input type="hidden" name="f_ano" value="<?= $ano_nivel ?>"><button type="submit" class="btn-icone aviso"><i class="bi bi-toggle-<?= $t['ativo']?'on':'off' ?>"></i></button></form>
                                      <form method="POST" style="margin:0" onsubmit="return confirmarEliminar('Eliminar turma <?= Sanitizar::html($t['codigo']) ?>?')"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" value="eliminar"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="f_curso" value="<?= $curso_id ?>"><input type="hidden" name="f_ano" value="<?= $ano_nivel ?>"><button type="submit" class="btn-icone perigo"><i class="bi bi-trash"></i></button></form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
<?php endif; ?>

<!-- Modal Gerar -->
<div class="modal fade" id="modalGerar" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between"><h5 class="mb-0"><i class="bi bi-magic me-2"></i>Gerar Turmas</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" value="gerar"><input type="hidden" name="f_curso" value="<?= $curso_id ?>"><input type="hidden" name="f_ano" value="<?= $ano_nivel ?>">
    <div class="modal-body p-4"><div class="row g-3">
      <div class="col-12"><label class="rotulo-campo">Curso</label><select name="curso_id" class="campo-input" required><?php foreach($cursos as $c): ?><option value="<?= $c['id'] ?>" <?= $curso_id==$c['id']?'selected':'' ?>><?= Sanitizar::html($c['nome']) ?></option><?php endforeach; ?></select></div>
      <div class="col-sm-6"><label class="rotulo-campo">Ano Letivo</label><select name="ano_letivo_id" class="campo-input" required><?php foreach($anos as $a): ?><option value="<?= $a['id'] ?>"><?= Sanitizar::html($a['nome']) ?></option><?php endforeach; ?></select></div>
      <div class="col-sm-3"><label class="rotulo-campo">Ano Curso</label><select name="ano_nivel" class="campo-input" required><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>" <?= $ano_nivel==$i?'selected':'' ?>><?= Etiqueta::anoNivel($i) ?></option><?php endfor; ?></select></div>
      <div class="col-sm-3"><label class="rotulo-campo">Semestre</label><select name="semestre_num" class="campo-input" required><option value="1">1º</option><option value="2">2º</option></select></div>
      <div class="col-12"><label class="rotulo-campo">Nº Turmas</label><select name="quantidade" class="campo-input"><?php for($i=1;$i<=9;$i++): ?><option value="<?= $i ?>" <?= $i==2?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
    </div></div>
    <div class="modal-footer"><button type="submit" class="btn btn-primary">Gerar</button></div>
  </form>
</div></div></div>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
