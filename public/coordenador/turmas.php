<?php // public/coordenador/turmas.php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao,Url,Sanitizar,Etiqueta,Auditoria};
use App\Models\{TurmaModelo,CoordenadorModelo,AnoLetivoModelo};
use App\Middleware\Autenticacao;

Autenticacao::exigirPerfil(['coordenador','super_admin']);
$uid=Sessao::utilizador()['id'];
$coord=(new CoordenadorModelo())->obterPorUsuario($uid);
if (!$coord && \App\Helpers\Sessao::temPerfil('super_admin')) { \App\Helpers\Url::redirecionar(\App\Helpers\Url::admin('index.php')); }
$cursoId=$coord['curso_id']??0;
$tm=new TurmaModelo();
$alm=new AnoLetivoModelo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro', 'Token inválido.'); header('Location: turmas.php'); exit;
    }
    if (($_POST['_acao'] ?? '') === 'criar') {
        $anoId   = (int)($_POST['ano_letivo_id'] ?? 0);
        $nivel   = (int)($_POST['ano_nivel'] ?? 0);
        $codigo  = Sanitizar::texto($_POST['codigo'] ?? '');
        if ($anoId && $nivel && $codigo) {
            $tm->criar($cursoId, $anoId, $nivel, $codigo);
            Auditoria::registar('criar_turma', 'turmas', 0, "Nova turma $codigo criada", $uid);
            Sessao::flash('sucesso', 'Turma criada com sucesso!');
        } else {
            Sessao::flash('erro', 'Por favor, preencha todos os campos obrigatórios.');
        }
        header('Location: turmas.php'); exit;
    }
}

$turmas=$tm->listarTodas(['curso_id'=>$cursoId]);
$anos   = $alm->listarTodos();
$menuLateral=['Principal'=>[['id'=>'dashboard','titulo'=>'Dashboard','url'=>Url::coordenador('index.php'),'icone'=>'bi-speedometer2'],['id'=>'ocorrencias','titulo'=>'Ocorrências','url'=>Url::coordenador('ocorrencias.php'),'icone'=>'bi-exclamation-triangle']],'Curso'=>[['id'=>'disciplinas','titulo'=>'Disciplinas','url'=>Url::coordenador('disciplinas.php'),'icone'=>'bi-book'],['id'=>'professores','titulo'=>'Professores','url'=>Url::coordenador('professores.php'),'icone'=>'bi-person-badge'],['id'=>'turmas','titulo'=>'Turmas','url'=>Url::coordenador('turmas.php'),'icone'=>'bi-people']],'Relatórios'=>[['id'=>'relatorios','titulo'=>'Exportar PDF','url'=>Url::coordenador('relatorios.php'),'icone'=>'bi-file-earmark-pdf']]];
$tituloPagina='Turmas – '.($coord['curso_nome']??''); $paginaActiva='turmas';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="d-flex justify-content-end mb-4">
  <button type="button" class="btn-base btn-primario" onclick="abrirNovaTurma()">
    <i class="bi bi-plus-circle"></i> Nova Turma
  </button>
</div>

<?php if (empty($turmas)): ?>
<div class="glass-card"><div class="sem-dados"><i class="bi bi-people"></i><p>Ainda não há turmas neste curso. Use “Nova turma” para criar a primeira.</p></div></div>
<?php else: ?>
<div class="cabeca-pagina"><h2>Turmas do curso <span class="badge bg-secondary"><?= count($turmas) ?></span></h2></div>
<div class="grelha-turmas">
  <?php foreach ($turmas as $t): ?>
  <article class="turma-cartao <?= $t['ativo'] ? '' : 'inativa' ?>">
    <div class="turma-codigo" aria-hidden="true"><?= Sanitizar::html($t['codigo']) ?></div>
    <div class="turma-info">
      <div class="td-nome">Turma <?= Sanitizar::html($t['codigo']) ?></div>
      <span class="td-muted"><?= Etiqueta::anoNivel((int)$t['ano_nivel']) ?> &middot; <?= Sanitizar::html($t['ano_nome']) ?></span>
      <span class="badge-estado <?= $t['ativo'] ? 'badge-resolvida' : 'badge-cancelada' ?>"><?= $t['ativo'] ? 'Activa' : 'Inactiva' ?></span>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<!-- Modal Nova Turma -->
<div class="modal fade" id="modalNovaTurma" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nova Turma</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= Sessao::campoCsrf() ?>
        <input type="hidden" name="_acao" value="criar">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="rotulo-campo">Ano Letivo</label>
              <select name="ano_letivo_id" class="campo-input" required>
                <option value="">-- Selecionar --</option>
                <?php foreach($anos as $a): ?>
                <option value="<?= $a['id'] ?>"><?= Sanitizar::html($a['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Ano do Curso (Nível)</label>
              <select name="ano_nivel" class="campo-input" required>
                <option value="">-- Selecionar --</option>
                <option value="1">1º Ano</option>
                <option value="2">2º Ano</option>
                <option value="3">3º Ano</option>
                <option value="4">4º Ano</option>
                <option value="5">5º Ano</option>
              </select>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Código da Turma</label>
              <input type="text" name="codigo" class="campo-input" placeholder="Ex: 101" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar Turma</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirNovaTurma() {
    new bootstrap.Modal(document.getElementById('modalNovaTurma')).show();
}
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
