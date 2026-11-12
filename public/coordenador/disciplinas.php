<?php
/**
 * public/coordenador/disciplinas.php
 * Disciplinas do Curso – Coordenador (visualização e gestão)
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Etiqueta};
use App\Models\{DisciplinaModelo, CoordenadorModelo, ProfessorModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirPerfil(['coordenador','super_admin']);

$uid     = Sessao::utilizador()['id'];
$coord   = (new CoordenadorModelo())->obterPorUsuario($uid);
if (!$coord && \App\Helpers\Sessao::temPerfil('super_admin')) { \App\Helpers\Url::redirecionar(\App\Helpers\Url::admin('index.php')); }
$cursoId = $coord['curso_id'] ?? 0;
$dm      = new DisciplinaModelo();
$pm      = new ProfessorModelo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST Disciplinas: " . print_r($_POST, true));
    if (!Sessao::verificarCsrf($_POST['_token']??'')) {
        Sessao::flash('erro', 'Sessão expirada ou token inválido.');
        header('Location: disciplinas.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'criar') {
        $dados = ['nome'=>Sanitizar::texto($_POST['nome']??''),'codigo'=>strtoupper(Sanitizar::texto($_POST['codigo']??'')),'curso_id'=>$cursoId,'professor_id'=>(int)($_POST['professor_id']??0),'ano_nivel'=>(int)($_POST['ano_nivel']??0),'semestre_num'=>(int)($_POST['semestre_num']??0)];
        if ($dados['nome']&&$dados['professor_id']&&$dados['ano_nivel']) { $dm->criar($dados); Sessao::flash('sucesso','Disciplina criada!'); }
        else Sessao::flash('erro','Preencha os campos obrigatórios.');
    } elseif ($acao === 'editar') {
        $id = (int)($_POST['id']??0);
        $dados = ['nome'=>Sanitizar::texto($_POST['nome']??''),'codigo'=>strtoupper(Sanitizar::texto($_POST['codigo']??'')),'professor_id'=>(int)($_POST['professor_id']??0),'ano_nivel'=>(int)($_POST['ano_nivel']??0),'semestre_num'=>(int)($_POST['semestre_num']??0),'ativo'=>(int)($_POST['ativo']??1)];
        if ($id&&$dados['nome']) { $dm->actualizar($id,$dados); Sessao::flash('sucesso','Disciplina actualizada!'); }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id']??0);
        if ($id) {
            if ($dm->eliminar($id)) Sessao::flash('sucesso', 'Disciplina eliminada!');
            else Sessao::flash('erro', 'Não foi possível eliminar.');
        }
    }
    header('Location: disciplinas.php'); exit;
}

$disciplinas = $dm->listarTodas(['curso_id'=>$cursoId]);
$professores = $pm->listarTodos(true);
$menuLateral = [
    'Principal'=>[['id'=>'dashboard','titulo'=>'Dashboard','url'=>Url::coordenador('index.php'),'icone'=>'bi-speedometer2'],['id'=>'ocorrencias','titulo'=>'Ocorrências','url'=>Url::coordenador('ocorrencias.php'),'icone'=>'bi-exclamation-triangle']],
    'Curso'=>[['id'=>'disciplinas','titulo'=>'Disciplinas','url'=>Url::coordenador('disciplinas.php'),'icone'=>'bi-book'],['id'=>'professores','titulo'=>'Professores','url'=>Url::coordenador('professores.php'),'icone'=>'bi-person-badge'],['id'=>'turmas','titulo'=>'Turmas','url'=>Url::coordenador('turmas.php'),'icone'=>'bi-people']],
    'Relatórios'=>[['id'=>'relatorios','titulo'=>'Exportar PDF','url'=>Url::coordenador('relatorios.php'),'icone'=>'bi-file-earmark-pdf']],
];
$tituloPagina='Disciplinas – '.($coord['curso_nome']??''); $paginaActiva='disciplinas';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="d-flex justify-content-end mb-4">
    <button class="btn-base btn-primario" data-bs-toggle="modal" data-bs-target="#modalD" id="btnNovo">
        <i class="bi bi-plus-lg"></i> Nova Disciplina
    </button>
</div>

<?php require_once APP_DIR . '/views/shared/componentes.php'; ?>
<?php $semDocente = count(array_filter($disciplinas, fn($d) => empty($d['professor_nome']))); ?>
<?php if ($semDocente > 0): ?>
  <div class="alerta alerta-aviso"><i class="bi bi-person-dash"></i><span><?= $semDocente ?> <?= $semDocente === 1 ? 'disciplina sem docente atribuído' : 'disciplinas sem docente atribuído' ?>.</span></div>
<?php endif; ?>
<?= agruparFichas($disciplinas, ['editar' => true, 'eliminar' => true, 'vazio' => 'Ainda não há disciplinas neste curso. Use “Nova Disciplina” para criar a primeira.']) ?>

<div class="modal fade" id="modalD" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between"><h5 class="mb-0" id="dTitulo">Nova Disciplina</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" id="dAcao" value="criar"><input type="hidden" name="id" id="dId" value="">
    <div class="modal-body p-4"><div class="row g-3">
      <div class="col-sm-8"><label class="rotulo-campo">Nome *</label><input type="text" name="nome" id="dNome" class="campo-input" required></div>
      <div class="col-sm-4"><label class="rotulo-campo">Código</label><input type="text" name="codigo" id="dCod" class="campo-input"></div>
      <div class="col-sm-6"><label class="rotulo-campo">Ano do Curso *</label><select name="ano_nivel" id="dAnivel" class="campo-input" required><option value="">--</option><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= Etiqueta::anoNivel($i) ?></option><?php endfor; ?></select></div>
      <div class="col-sm-6"><label class="rotulo-campo">Semestre *</label><select name="semestre_num" id="dSem" class="campo-input" required><option value="">--</option><option value="1">1º Semestre</option><option value="2">2º Semestre</option></select></div>
      <div class="col-12">
        <label class="rotulo-campo">Professor *</label>
        <input type="text" id="dProfSearch" class="campo-input mb-2" placeholder="Pesquisar professor...">
        <select name="professor_id" id="dProf" class="campo-input" required>
          <option value="">-- Selecionar --</option>
          <?php foreach($professores as $p): ?>
            <option value="<?= $p['id'] ?>"><?= Sanitizar::html($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12" id="dAtivoDiv" style="display:none"><div class="form-check"><input type="checkbox" name="ativo" value="1" id="dAtivo" class="form-check-input" checked><label class="form-check-label" for="dAtivo">Activa</label></div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button></div>
  </form>
</div></div></div>
<script>
function abrirEditar(d){document.getElementById('dTitulo').textContent='Editar Disciplina';document.getElementById('dAcao').value='editar';document.getElementById('dId').value=d.id;document.getElementById('dNome').value=d.nome;document.getElementById('dCod').value=d.codigo||'';document.getElementById('dAnivel').value=d.ano_nivel;document.getElementById('dSem').value=d.semestre_num;document.getElementById('dProf').value=d.professor_id;document.getElementById('dAtivo').checked=d.ativo==1;document.getElementById('dAtivoDiv').style.display='';new bootstrap.Modal(document.getElementById('modalD')).show();}

document.getElementById('dProfSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const select = document.getElementById('dProf');
    const options = select.options;

    for (let i = 1; i < options.length; i++) {
        const text = options[i].text.toLowerCase();
        options[i].style.display = text.includes(searchTerm) ? '' : 'none';
    }
});

document.getElementById('btnNovo').addEventListener('click',function(){document.getElementById('dTitulo').textContent='Nova Disciplina';document.getElementById('dAcao').value='criar';document.getElementById('dId').value='';document.getElementById('modalD').querySelector('form').reset();document.getElementById('dAtivoDiv').style.display='none';});

</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
