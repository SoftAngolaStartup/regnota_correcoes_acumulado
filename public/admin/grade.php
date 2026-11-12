<?php
/**
 * public/admin/grade.php
 * Gestão da Grade Curricular - Definição de Matérias por Curso
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta, Auditoria};
use App\Models\{GradeCurricularModelo, CursoModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$gm  = new GradeCurricularModelo();
$cm  = new CursoModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) { Sessao::flash('erro','Token inválido.'); header('Location: grade.php'); exit; }
    $acao = $_POST['_acao'] ?? '';
    
    if ($acao === 'criar') {
        $dados = [
            'nome'          => Sanitizar::texto($_POST['nome']??''),
            'codigo'        => strtoupper(Sanitizar::texto($_POST['codigo']??'')),
            'curso_id'      => (int)($_POST['curso_id']??0),
            'ano_nivel'     => (int)($_POST['ano_nivel']??0),
            'semestre_num'  => (int)($_POST['semestre_num']??0),
            'ativo'         => (int)($_POST['ativo']??1)
        ];
        if ($dados['nome'] && $dados['curso_id'] && $dados['ano_nivel'] && in_array($dados['semestre_num'], [1,2])) {
            $id = $gm->criar($dados);
            Auditoria::registar('criar_grade', 'grade_curricular', $id, "Matéria criada: {$dados['nome']}", $uid);
            Sessao::flash('sucesso', 'Matéria adicionada à grade!');
        } else {
            Sessao::flash('erro', 'Preencha todos os campos obrigatórios.');
        }
    } elseif ($acao === 'editar') {
        $id = (int)($_POST['id']??0);
        $dados = [
            'nome'          => Sanitizar::texto($_POST['nome']??''),
            'codigo'        => strtoupper(Sanitizar::texto($_POST['codigo']??'')),
            'ano_nivel'     => (int)($_POST['ano_nivel']??0),
            'semestre_num'  => (int)($_POST['semestre_num']??0),
            'ativo'         => (int)($_POST['ativo']??1)
        ];
        if ($id && $dados['nome']) {
            $gm->actualizar($id, $dados);
            Auditoria::registar('editar_grade', 'grade_curricular', $id, null, $uid);
            Sessao::flash('sucesso', 'Grade actualizada!');
        }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id']??0);
        if ($gm->eliminar($id)) {
            Sessao::flash('sucesso', 'Matéria removida da grade!');
        } else {
            Sessao::flash('erro', 'Erro ao eliminar matéria.');
        }
    }
    header('Location: grade.php?curso_id='.($_POST['f_curso']??'').'&ano_nivel='.($_POST['f_ano']??'')); exit;
}

// Determine current step
$curso_id = (int)($_GET['curso_id'] ?? 0);
$ano_nivel = (int)($_GET['ano_nivel'] ?? 0);

$step = ($curso_id) ? ($ano_nivel ? 3 : 2) : 1;

$cursos = $cm->listarTodos(true);
$grade = [];

if ($step === 3) {
    // Busca grade para o ano selecionado (ambos semestres)
    $filtros = ['curso_id' => $curso_id, 'ano_nivel' => $ano_nivel];
    $grade = $gm->listarTodas($filtros);
}

$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Grade Curricular'; $paginaActiva = 'grade';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';

// Helper function to build URL for steps
function stepUrl($params) {
    $base = 'grade.php';
    $current = array_filter(['curso_id' => $_GET['curso_id'] ?? null, 'ano_nivel' => $_GET['ano_nivel'] ?? null]);
    $new = array_merge($current, $params);
    return $base . '?' . http_build_query($new);
}
?>

<div class="mb-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="grade.php">Cursos</a></li>
      <?php if($step >= 2): ?>
        <li class="breadcrumb-item"><a href="<?= stepUrl(['ano_nivel' => null]) ?>">Anos</a></li>
      <?php endif; ?>
    </ol>
  </nav>
</div>

<?php
// Mapeamento de ícones por nome de curso
$iconesCurso = [
    'Administração Pública e Gestão de Cidade' => 'bi-building-gear',
    'Análises Clínicas' => 'bi-microscope',
    'Arquitectura e Urbanismo' => 'bi-rulers',
    'Ciências da Computação' => 'bi-code-slash',
    'Ciências Económicas e Empresariais' => 'bi-graph-up-arrow',
    'Ciências Farmacêuticas' => 'bi-flask',
    'Ciências Farmacêuticas Opção em Análises Clínicas' => 'bi-capsule-pill',
    'Direito' => 'bi-book-half',
    'Enfermagem Geral' => 'bi-heart-pulse',
    'Engenharia Civil' => 'bi-building-fill-up',
    'Engenharia Eléctrica' => 'bi-lightning-charge',
    'Ensino Primário' => 'bi-alphabet-uppercase',
    'Gestão de Recursos Humanos' => 'bi-people-fill',
    'História' => 'bi-hourglass-split',
    'Licenciatura em Engenharia Informática e Computação' => 'bi-cpu-fill',
    'Medicina Dentária' => 'bi-tooth',
    'Psicologia' => 'bi-person-hearts',
    'Psicologia Criminal' => 'bi-fingerprint',
    'Psicologia da Educação' => 'bi-mortarboard',
    'Psicologia da Saúde' => 'bi-activity'
];
?>

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
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?= Sanitizar::html($cursos[array_search($curso_id, array_column($cursos, 'id'))]['nome'] ?? 'Curso') ?> - <?= Etiqueta::anoNivel($ano_nivel) ?></h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGrade" id="btnNovo"><i class="bi bi-plus-lg"></i> Adicionar Matéria</button>
  </div>
  
  <div class="row g-4 mt-3">
    <?php for($s=1; $s<=2; $s++): 
        $disciplinasSemestre = array_filter($grade, fn($g) => (int)$g['semestre_num'] === $s);
    ?>
    <div class="col-md-6">
        <div class="glass-card">
            <div class="glass-header">
                <span class="fw-bold d-flex align-items-center gap-2"><i class="bi bi-calendar-event"></i> <?= $s ?>º Semestre</span>
            </div>
            <div class="card-conteudo-corpo p-0">
                <table class="tabela-sistema">
                    <thead><tr><th>Matéria</th><th>Cód.</th><th style="text-align:center">Acções</th></tr></thead>
                    <tbody>
                        <?php if(empty($disciplinasSemestre)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">Sem matérias definidas</td></tr>
                        <?php else: foreach($disciplinasSemestre as $g): ?>
                            <tr>
                                <td class="td-nome"><?= Sanitizar::html($g['nome']) ?></td>
                                <td><span class="chip-tipo"><?= Sanitizar::html($g['codigo'] ?? '—') ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button class="btn-icone" onclick="abrirEditar(<?= htmlspecialchars(json_encode($g),ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                                        <form method="POST" style="margin:0" onsubmit="return confirmarEliminar('Eliminar esta matéria da grade?')"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" value="eliminar"><input type="hidden" name="id" value="<?= $g['id'] ?>"><input type="hidden" name="f_curso" value="<?= $curso_id ?>"><input type="hidden" name="f_ano" value="<?= $ano_nivel ?>"><button type="submit" class="btn-icone perigo"><i class="bi bi-trash"></i></button></form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<!-- Modal -->
<div class="modal fade" id="modalGrade" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between"><h5 class="mb-0" id="gradeTitulo">Nova Matéria na Grade</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" id="gradeAcao" value="criar"><input type="hidden" name="id" id="gradeId" value=""><input type="hidden" name="f_curso" value="<?= $curso_id ?>"><input type="hidden" name="f_ano" value="<?= $ano_nivel ?>">
    <div class="modal-body p-4"><div class="row g-3">
      <div class="col-sm-8"><label class="rotulo-campo">Nome da Matéria *</label><input type="text" name="nome" id="gradeNome" class="campo-input" required></div>
      <div class="col-sm-4"><label class="rotulo-campo">Código</label><input type="text" name="codigo" id="gradeCod" class="campo-input" placeholder="MAT101"></div>
      <div class="col-12"><label class="rotulo-campo">Curso *</label><select name="curso_id" id="gradeCurso" class="campo-input" required><?php foreach($cursos as $c): ?><option value="<?= $c['id'] ?>"><?= Sanitizar::html($c['nome']) ?></option><?php endforeach; ?></select></div>
      <div class="col-sm-4"><label class="rotulo-campo">Ano *</label><select name="ano_nivel" id="gradeAnivel" class="campo-input" required><option value="">--</option><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= Etiqueta::anoNivel($i) ?></option><?php endfor; ?></select></div>
      <div class="col-sm-4"><label class="rotulo-campo">Semestre *</label><select name="semestre_num" id="gradeSem" class="campo-input" required><option value="">--</option><option value="1">1º Semestre</option><option value="2">2º Semestre</option></select></div>
      <div class="col-sm-4" id="gradeAtivoDiv" style="display:none"><label class="rotulo-campo">Estado</label><select name="ativo" class="campo-input" id="gradeAtivo"><option value="1">Activa</option><option value="0">Inactiva</option></select></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button></div>
  </form>
</div></div></div>

<script>
function abrirEditar(g){
    document.getElementById('gradeTitulo').textContent='Editar Matéria da Grade';
    document.getElementById('gradeAcao').value='editar';
    document.getElementById('gradeId').value=g.id;
    document.getElementById('gradeNome').value=g.nome;
    document.getElementById('gradeCod').value=g.codigo||'';
    document.getElementById('gradeCurso').value=g.curso_id;
    document.getElementById('gradeAnivel').value=g.ano_nivel;
    document.getElementById('gradeSem').value=g.semestre_num;
    document.getElementById('gradeAtivo').value=g.ativo;
    document.getElementById('gradeAtivoDiv').style.display='';
    new bootstrap.Modal(document.getElementById('modalGrade')).show();
}

document.getElementById('btnNovo').addEventListener('click',function(){
    document.getElementById('gradeTitulo').textContent='Nova Matéria na Grade';
    document.getElementById('gradeAcao').value='criar';
    document.getElementById('gradeId').value='';
    document.getElementById('modalGrade').querySelector('form').reset();
    document.getElementById('gradeAtivoDiv').style.display='none';
    
    // Pre-fill selection based on wizard state
    document.getElementById('gradeCurso').value = '<?= $curso_id ?>';
    document.getElementById('gradeAnivel').value = '<?= $ano_nivel ?>';
});
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
