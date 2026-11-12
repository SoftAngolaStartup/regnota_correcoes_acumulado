<?php
/**
 * public/admin/cursos.php
 * Gestão de Cursos – CRUD Completo
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Auditoria};
use App\Models\{CursoModelo, FaculdadeModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$cm  = new CursoModelo();
$fm  = new FaculdadeModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) { Sessao::flash('erro','Token inválido.'); header('Location: cursos.php'); exit; }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'criar') {
        $nome  = Sanitizar::texto($_POST['nome'] ?? '');
        $cod   = strtoupper(Sanitizar::texto($_POST['codigo'] ?? ''));
        $fid   = (int)($_POST['faculdade_id'] ?? 1);
        $anos  = (int)($_POST['duracao_anos'] ?? 5);
        if ($nome && $cod) { $id = $cm->criar($nome, $cod, $fid, $anos); Auditoria::registar('criar_curso','cursos',$id,null,$uid); Sessao::flash('sucesso','Curso criado!'); }
        else Sessao::flash('erro','Preencha os campos obrigatórios.');
    } elseif ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0); $nome = Sanitizar::texto($_POST['nome'] ?? ''); $cod = strtoupper(Sanitizar::texto($_POST['codigo'] ?? '')); $anos = (int)($_POST['duracao_anos'] ?? 5); $ativo = (int)($_POST['ativo'] ?? 1);
        if ($id && $nome) { $cm->actualizar($id, $nome, $cod, $anos, $ativo); Sessao::flash('sucesso','Curso actualizado!'); }
    }
    header('Location: cursos.php'); exit;
}

$cursos    = $cm->listarTodos();
$faculdades= $fm->listarTodas();
$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Cursos'; $paginaActiva = 'cursos';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="d-flex justify-content-end mb-4">
  <button class="btn-base btn-primario" data-bs-toggle="modal" data-bs-target="#modalCurso" id="btnNovo"><i class="bi bi-plus-lg"></i> Novo Curso</button>
</div>
<div class="glass-card">
  <div class="glass-header"><span class="card-conteudo-titulo"><i class="bi bi-mortarboard"></i> Cursos <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($cursos) ?></span></span></div>
  <div style="overflow-x:auto">
    <table class="tabela-sistema">
      <thead><tr><th>Código</th><th>Nome</th><th>Faculdade</th><th>Duração</th><th>Coordenador</th><th>Estado</th><th style="text-align:center">Acções</th></tr></thead>
      <tbody>
        <?php if (empty($cursos)): ?><tr><td colspan="7"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Sem cursos.</p></div></td></tr>
        <?php else: foreach ($cursos as $c): ?>
        <tr>
          <td><span style="font-weight:800;color:var(--primario)"><?= Sanitizar::html($c['codigo']) ?></span></td>
          <td class="td-nome"><?= Sanitizar::html($c['nome']) ?></td>
          <td class="td-muted"><?= Sanitizar::html($c['faculdade_nome']) ?></td>
          <td class="td-muted"><?= $c['duracao_anos'] ?> anos</td>
          <td class="td-muted"><?= $c['coordenador_nome'] ? Sanitizar::html($c['coordenador_nome']) : '<em>Sem coordenador</em>' ?></td>
          <td><span class="badge-estado <?= $c['ativo'] ? 'badge-resolvida' : 'badge-cancelada' ?>"><?= $c['ativo'] ? 'Activo' : 'Inactivo' ?></span></td>
          <td><div class="d-flex gap-1 justify-content-center">
            <button class="btn-icone" onclick="abrirEditar(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
          </div></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Modal -->
<div class="modal fade" id="modalCurso" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between"><h5 class="mb-0" id="cursoTitulo">Novo Curso</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST"><?= Sessao::campoCsrf() ?><input type="hidden" name="_acao" id="cursoAcao" value="criar"><input type="hidden" name="id" id="cursoId" value="">
    <div class="modal-body p-4"><div class="row g-3">
      <div class="col-sm-8"><label class="rotulo-campo">Nome do Curso *</label><input type="text" name="nome" id="cursoNome" class="campo-input" required></div>
      <div class="col-sm-4"><label class="rotulo-campo">Código *</label><input type="text" name="codigo" id="cursoCodigo" class="campo-input" required placeholder="COMP"></div>
      <div class="col-sm-6"><label class="rotulo-campo">Faculdade *</label><select name="faculdade_id" id="cursoFac" class="campo-input" required><?php foreach($faculdades as $f): ?><option value="<?= $f['id'] ?>"><?= Sanitizar::html($f['nome']) ?></option><?php endforeach; ?></select></div>
      <div class="col-sm-6"><label class="rotulo-campo">Duração (anos)</label><select name="duracao_anos" id="cursoAnos" class="campo-input"><?php for($i=3;$i<=6;$i++): ?><option value="<?= $i ?>" <?= $i==5?'selected':'' ?>><?= $i ?> anos</option><?php endfor; ?></select></div>
      <div class="col-12" id="cursoAtivoDiv" style="display:none"><div class="form-check"><input type="checkbox" name="ativo" value="1" id="cursoAtivo" class="form-check-input" checked><label class="form-check-label" for="cursoAtivo">Curso activo</label></div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn-base btn-fantasma" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn-base btn-primario"><i class="bi bi-save me-1"></i>Guardar</button></div>
  </form>
</div></div></div>
<script>
function abrirEditar(c){document.getElementById('cursoTitulo').textContent='Editar Curso';document.getElementById('cursoAcao').value='editar';document.getElementById('cursoId').value=c.id;document.getElementById('cursoNome').value=c.nome;document.getElementById('cursoCodigo').value=c.codigo;document.getElementById('cursoAnos').value=c.duracao_anos;document.getElementById('cursoAtivo').checked=c.ativo==1;document.getElementById('cursoAtivoDiv').style.display='';new bootstrap.Modal(document.getElementById('modalCurso')).show();}
document.getElementById('btnNovo').addEventListener('click',function(){document.getElementById('cursoTitulo').textContent='Novo Curso';document.getElementById('cursoAcao').value='criar';document.getElementById('cursoId').value='';document.getElementById('modalCurso').querySelector('form').reset();document.getElementById('cursoAtivoDiv').style.display='none';});
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
