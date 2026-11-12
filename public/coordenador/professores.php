<?php // public/coordenador/professores.php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao,Url,Sanitizar,Formatar,Auditoria};
use App\Helpers\Bd;
use App\Models\{ProfessorModelo,CoordenadorModelo,DisciplinaModelo};
use App\Middleware\Autenticacao;

Autenticacao::exigirPerfil(['coordenador','super_admin']);
$uid=Sessao::utilizador()['id'];
$coord=(new CoordenadorModelo())->obterPorUsuario($uid);
if (!$coord && \App\Helpers\Sessao::temPerfil('super_admin')) { \App\Helpers\Url::redirecionar(\App\Helpers\Url::admin('index.php')); }
$cursoId=$coord['curso_id']??0;
$pm=new ProfessorModelo(); $dm=new DisciplinaModelo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro', 'Token inválido.'); header('Location: professores.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'criar') {
        $dados = [
            'nome'          => Sanitizar::texto($_POST['nome'] ?? ''),
            'bi'            => Sanitizar::texto($_POST['bi'] ?? ''),
            'telefone'      => Sanitizar::texto($_POST['telefone'] ?? ''),
            'email_pessoal' => Sanitizar::texto($_POST['email_pessoal'] ?? ''),
            'especialidade' => Sanitizar::texto($_POST['especialidade'] ?? ''),
            'ativo'         => 1,
        ];
        if ($dados['nome'] && $dados['bi']) {
            $pm->criar($dados);
            Auditoria::registar('criar_professor', 'professores', 0, "Novo professor criado: {$dados['nome']}", $uid);
            Sessao::flash('sucesso', 'Professor adicionado com sucesso!');
        } else {
            Sessao::flash('erro', 'Nome e BI são obrigatórios.');
        }
        header('Location: professores.php'); exit;
    } elseif ($acao === 'actualizar') {
        $id = (int)($_POST['id'] ?? 0);
        $dados = [
            'nome'          => Sanitizar::texto($_POST['nome'] ?? ''),
            'bi'            => Sanitizar::texto($_POST['bi'] ?? ''),
            'telefone'      => Sanitizar::texto($_POST['telefone'] ?? ''),
            'email_pessoal' => Sanitizar::texto($_POST['email_pessoal'] ?? ''),
            'especialidade' => Sanitizar::texto($_POST['especialidade'] ?? ''),
            'ativo'         => (int)($_POST['ativo'] ?? 1),
        ];
        if ($id && $dados['nome'] && $dados['bi']) {
            $pm->actualizar($id, $dados);
            Auditoria::registar('actualizar_professor', 'professores', $id, "Professor actualizado: {$dados['nome']}", $uid);
            Sessao::flash('sucesso', 'Professor actualizado com sucesso!');
        } else {
            Sessao::flash('erro', 'Nome e BI são obrigatórios.');
        }
        header('Location: professores.php'); exit;
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            if ($pm->eliminar($id)) {
                Auditoria::registar('eliminar_professor', 'professores', $id, "Professor eliminado", $uid);
                Sessao::flash('sucesso', 'Professor eliminado com sucesso!');
            } else {
                Sessao::flash('erro', 'Não é possível eliminar este professor pois possui ocorrências cadastradas em seu nome.');
            }
        }
        header('Location: professores.php'); exit;
    }
}

// Obter professores e suas disciplinas
$pdo = Bd::obter();
$stmt = $pdo->prepare("
    SELECT p.*, u.email, u.ultimo_login,
           GROUP_CONCAT(g.nome SEPARATOR ', ') AS disciplinas
    FROM professores p
    JOIN usuarios u ON p.usuario_id = u.id
    LEFT JOIN disciplinas d ON d.professor_id = p.id
    LEFT JOIN grade_curricular g ON d.grade_id = g.id
    WHERE (d.curso_id = ? OR d.id IS NULL) AND p.ativo = 1
    GROUP BY p.id
    ORDER BY p.nome
");
$stmt->execute([$cursoId]);
$professores = $stmt->fetchAll();

$menuLateral=['Principal'=>[['id'=>'dashboard','titulo'=>'Dashboard','url'=>Url::coordenador('index.php'),'icone'=>'bi-speedometer2'],['id'=>'ocorrencias','titulo'=>'Ocorrências','url'=>Url::coordenador('ocorrencias.php'),'icone'=>'bi-exclamation-triangle']],'Curso'=>[['id'=>'disciplinas','titulo'=>'Disciplinas','url'=>Url::coordenador('disciplinas.php'),'icone'=>'bi-book'],['id'=>'professores','titulo'=>'Professores','url'=>Url::coordenador('professores.php'),'icone'=>'bi-person-badge'],['id'=>'turmas','titulo'=>'Turmas','url'=>Url::coordenador('turmas.php'),'icone'=>'bi-people']],'Relatórios'=>[['id'=>'relatorios','titulo'=>'Exportar PDF','url'=>Url::coordenador('relatorios.php'),'icone'=>'bi-file-earmark-pdf']]];
$tituloPagina='Professores – '.($coord['curso_nome']??''); $paginaActiva='professores';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="alerta alerta-info mb-4"><i class="bi bi-info-circle-fill" style="flex-shrink:0"></i><span>Exibe apenas professores com disciplinas no seu curso.</span></div>
<div class="d-flex justify-content-end mb-4">
    <button type="button" class="btn-base btn-primario" onclick="abrirNovoProfessor()">
        <i class="bi bi-plus-circle"></i> Adicionar Professor
    </button>
</div>

<?php require_once APP_DIR . '/views/shared/componentes.php'; ?>
<div class="bloco-pauta-titulo"><span class="ico-tile sm"><i class="bi bi-person-badge"></i></span>Professores do curso<span class="contagem"><?= count($professores) ?></span></div>
<?php if (empty($professores)): ?>
  <div class="glass-card"><div class="sem-dados"><i class="bi bi-person-plus"></i><p>Nenhum professor com disciplinas neste curso.</p></div></div>
<?php else: ?>
<div class="grelha-fichas">
  <?php foreach ($professores as $p): ?>
  <article class="ficha">
    <div class="ficha-topo"><span class="avatar lg"><?= Sanitizar::html(iniciaisNome($p['nome'])) ?></span></div>
    <h4 class="ficha-nome"><?= Sanitizar::html($p['nome']) ?></h4>
    <ul class="contactos">
      <li class="longo"><i class="bi bi-book"></i><span><?= Sanitizar::html($p['disciplinas'] ?: 'Sem disciplinas') ?></span></li>
      <li><i class="bi bi-envelope"></i><span><?= Sanitizar::html($p['email']) ?></span></li>
      <li><i class="bi bi-telephone"></i><span><?= Sanitizar::html($p['telefone'] ?: '—') ?></span></li>
    </ul>
    <div class="ficha-acoes">
      <a href="<?= Url::coordenador('disciplinas.php') ?>" class="btn-icone" title="Gerir Disciplinas"><i class="bi bi-book"></i></a>
      <button class="btn-icone" title="Editar" onclick="abrirEditarProfessor(<?= $p['id'] ?>, '<?= addslashes(Sanitizar::html($p['nome'])) ?>', '<?= addslashes(Sanitizar::html($p['bi'])) ?>', '<?= addslashes(Sanitizar::html($p['telefone'])) ?>', '<?= addslashes(Sanitizar::html($p['email_pessoal']??'')) ?>', '<?= addslashes(Sanitizar::html($p['especialidade']??'')) ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
      <form method="POST" style="display:inline" onsubmit="return confirm('Tem a certeza que deseja eliminar este professor? Ele será removido se não tiver ocorrências vinculadas.')">
                                    <?= Sessao::campoCsrf() ?>
                                    <input type="hidden" name="_acao" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-icone perigo" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Novo Professor -->
<div class="modal fade" id="modalNovoProfessor" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Adicionar Professor</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= Sessao::campoCsrf() ?>
        <input type="hidden" name="_acao" value="criar">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="rotulo-campo">Nome Completo</label>
              <input type="text" name="nome" class="campo-input" placeholder="Nome..." required>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">BI / Número Identificação</label>
              <input type="text" name="bi" class="campo-input" placeholder="Ex: 123456789" required>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Telefone</label>
              <input type="text" name="telefone" class="campo-input" placeholder="Ex: 9xxxxxxx">
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Email Pessoal</label>
              <input type="email" name="email_pessoal" class="campo-input" placeholder="email@exemplo.com">
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Especialidade</label>
              <input type="text" name="especialidade" class="campo-input" placeholder="Ex: Engenharia de Software">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar Professor</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar Professor -->
<div class="modal fade" id="modalEditarProfessor" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Professor</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= Sessao::campoCsrf() ?>
        <input type="hidden" name="_acao" value="actualizar">
        <input type="hidden" name="id" id="editProfId">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="rotulo-campo">Nome Completo</label>
              <input type="text" name="nome" id="editProfNome" class="campo-input" required>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">BI / Número Identificação</label>
              <input type="text" name="bi" id="editProfBi" class="campo-input" required>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Telefone</label>
              <input type="text" name="telefone" id="editProfTel" class="campo-input">
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Email Pessoal</label>
              <input type="email" name="email_pessoal" id="editProfEmail" class="campo-input">
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Especialialidade</label>
              <input type="text" name="especialidade" id="editProfEsp" class="campo-input">
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="ativo" id="editProfAtivo" value="1" checked>
                <label class="form-check-label" for="editProfAtivo">Professor Activo</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirNovoProfessor() {
    new bootstrap.Modal(document.getElementById('modalNovoProfessor')).show();
}
function abrirEditarProfessor(id, nome, bi, tel, email, esp) {
    document.getElementById('editProfId').value = id;
    document.getElementById('editProfNome').value = nome;
    document.getElementById('editProfBi').value = bi;
    document.getElementById('editProfTel').value = tel;
    document.getElementById('editProfEmail').value = email;
    document.getElementById('editProfEsp').value = esp;
    new bootstrap.Modal(document.getElementById('modalEditarProfessor')).show();
}
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
