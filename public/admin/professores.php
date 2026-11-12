<?php
error_reporting(0); // Prevent PHP warnings/errors from breaking the HTML/JS output
/**
 * public/admin/professores.php
 * Gestão de Professores – CRUD Completo
 * Cria automaticamente um utilizador (BI@RegNota.com) ao cadastrar
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Url, Sanitizar, Formatar, Auditoria};
use App\Models\ProfessorModelo;
use App\Middleware\Autenticacao;

Autenticacao::exigirAdmin();

$pm  = new ProfessorModelo();
$uid = Sessao::utilizador()['id'];

// ── AJAX Handlers ──────────────────────────────────────────────
if (isset($_GET['acao']) && $_GET['acao'] === 'listar_disciplinas_turma') {
    header('Content-Type: application/json');
    $turmaId = (int)($_GET['turma_id'] ?? 0);
    $profId = (int)($_SESSION['selected_prof_id'] ?? 0); 
    
    if (!$turmaId) {
        echo json_encode([]); exit;
    }

    $dm = new \App\Models\DisciplinaModelo();
    $disciplinas = $dm->listarTodas(['turma_id' => $turmaId]);
    
    $resultado = [];
    foreach ($disciplinas as $d) {
        $resultado[] = [
            'id' => $d['id'],
            'nome' => $d['nome'],
            'codigo' => $d['codigo'],
            'associado' => ($d['professor_id'] == $profId)
        ];
    }
    echo json_encode($resultado);
    exit;
}

if (isset($_GET['acao']) && $_GET['acao'] === 'set_selected_prof') {
    header('Content-Type: application/json');
    $profId = (int)($_GET['id'] ?? 0);
    if ($profId) {
        $_SESSION['selected_prof_id'] = $profId;
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false]);
    }
    exit;
}

// ── POST ─────────────────────────────────────────────────
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
            'fotografia'    => null,
        ];
        if (!$dados['nome'] || !$dados['bi'] || !$dados['telefone'] || !$dados['especialidade']) {
            Sessao::flash('erro', 'Preencha todos os campos obrigatórios.');
        } else {
            $id = $pm->criar($dados);
            Auditoria::registar('criar_professor', 'professores', $id,
                "Professor criado: {$dados['nome']} – email: {$dados['bi']}@RegNota.com", $uid);
            Sessao::flash('sucesso', "Professor \"{$dados['nome']}\" criado! Email de acesso: {$dados['bi']}@RegNota.com | Senha: RegNota@123");
        }
    } elseif ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $dados = [
            'nome'          => Sanitizar::texto($_POST['nome'] ?? ''),
            'bi'            => Sanitizar::texto($_POST['bi'] ?? ''),
            'telefone'      => Sanitizar::texto($_POST['telefone'] ?? ''),
            'email_pessoal' => Sanitizar::texto($_POST['email_pessoal'] ?? ''),
            'especialidade' => Sanitizar::texto($_POST['especialidade'] ?? ''),
            'ativo'         => (int)($_POST['ativo'] ?? 1),
        ];
        if ($id && $dados['nome']) {
            $pm->actualizar($id, $dados);
            Auditoria::registar('editar_professor', 'professores', $id, null, $uid);
            Sessao::flash('sucesso', 'Professor actualizado com sucesso!');
        } else {
            Sessao::flash('erro', 'Nome é obrigatório para actualizar.');
        }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$pm->eliminar($id)) {
            Sessao::flash('erro', 'Não é possível eliminar: este professor está associado a disciplinas ou ocorrências.');
        } else {
            Auditoria::registar('eliminar_professor', 'professores', $id, null, $uid);
            Sessao::flash('sucesso', 'Professor eliminado com sucesso.');
        }
    }
    header('Location: professores.php'); exit;
}

$professores = $pm->listarTodos();
$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Professores';
$paginaActiva = 'professores';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>

<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-funnel"></i> Filtros e Acções</span>
    <button class="btn-base btn-primario" id="btnNovo">
        <i class="bi bi-plus-lg me-1"></i> Novo Professor
    </button>
  </div>
  <div class="card-conteudo-corpo">
    <div class="row g-3">
        <div class="col-sm-6 col-md-4">
            <input type="text" class="campo-input" placeholder="Pesquisar por nome ou BI...">
        </div>
        <div class="col-sm-6 col-md-2">
            <button class="btn-base btn-primario w-100">Pesquisar</button>
        </div>
    </div>
  </div>
</div>

<div class="glass-card">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-person-badge"></i> Professores
      <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($professores) ?></span>
    </span>
  </div>
  <div class="card-conteudo-corpo" style="padding: 0;">
    <div style="overflow-x:auto;">
      <table class="tabela-sistema">
        <thead>
          <tr><th>Nome</th><th>BI</th><th>Email Acesso</th><th>Especialidade</th><th>Telefone</th><th>Último Login</th><th>Estado</th><th style="text-align:center">Acções</th></tr>
        </thead>
        <tbody>
          <?php if (empty($professores)): ?>
          <tr><td colspan="8"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhum professor registrad0.</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($professores as $p): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div style="width:34px;height:34px;border-radius:8px;background:var(--primario);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                  <?= strtoupper(mb_substr($p['nome'],0,2)) ?>
                </div>
                <span class="td-nome"><?= Sanitizar::html($p['nome']) ?></span>
              </div>
            </td>
            <td class="td-muted"><?= Sanitizar::html($p['bi']) ?></td>
            <td style="font-size:12px;font-family:monospace"><?= Sanitizar::html($p['bi']) ?>@RegNota.com</td>
            <td><?= Sanitizar::html($p['especialidade']) ?></td>
            <td class="td-muted"><?= Sanitizar::html($p['telefone']) ?></td>
            <td class="td-muted"><?= $p['ultimo_login'] ? Formatar::dataHora($p['ultimo_login']) : 'Nunca' ?></td>
            <td>
              <?php if ($p['ativo']): ?>
              <span class="badge-estado badge-resolvida">Activo</span>
              <?php else: ?>
              <span class="badge-estado badge-cancelada">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-1 justify-content-center">
                <button class="btn-icone" title="Editar" onclick="abrirEditar(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="margin:0" onsubmit="return confirmarEliminar('Eliminar o professor <?= Sanitizar::html($p['nome']) ?>?')">
                  <?= Sessao::campoCsrf() ?>
                  <input type="hidden" name="_acao" value="eliminar">
                  <input type="hidden" name="id"    value="<?= $p['id'] ?>">
                  <button type="submit" class="btn-icone perigo"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Criar/Editar -->
<div class="modal fade" id="modalProf" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <h5 class="mb-0" id="profTitulo">Novo Professor</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= Sessao::campoCsrf() ?>
        <input type="hidden" name="_acao" id="profAcao" value="criar">
        <input type="hidden" name="id"    id="profId"   value="">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="rotulo-campo">Nome Completo *</label>
              <input type="text" name="nome" id="profNome" class="campo-input" required placeholder="Nome completo do professor">
            </div>
            <div class="col-sm-6">
              <label class="rotulo-campo">Número do BI *</label>
              <input type="text" name="bi" id="profBi" class="campo-input" required placeholder="003214567LA042">
              <small class="text-muted" id="emailPreview" style="font-size:11px"></small>
            </div>
            <div class="col-sm-6">
              <label class="rotulo-campo">Telefone *</label>
              <input type="text" name="telefone" id="profTel" class="campo-input" required placeholder="+244 9xx xxx xxx">
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Email Pessoal</label>
              <input type="email" name="email_pessoal" id="profEmail" class="campo-input" placeholder="email@pessoal.ao">
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Especialidade *</label>
              <input type="text" name="especialidade" id="profEsp" class="campo-input" required placeholder="Ex: Matemática, Programação...">
            </div>
            <div class="col-12" id="campoAtivo" style="display:none">
              <div class="form-check">
                <input type="checkbox" name="ativo" value="1" id="profAtivo" class="form-check-input" checked>
                <label class="form-check-label" for="profAtivo">Professor activo</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function confirmarEliminar(msg) {
    return confirm(msg);
}

function abrirEditar(p) {
    document.getElementById('profTitulo').textContent = 'Editar Professor';
    document.getElementById('profAcao').value   = 'editar';
    document.getElementById('profId').value     = p.id;
    document.getElementById('profNome').value   = p.nome;
    document.getElementById('profBi').value     = p.bi;
    document.getElementById('profTel').value    = p.telefone;
    document.getElementById('profEmail').value  = p.email_pessoal || '';
    document.getElementById('profEsp').value    = p.especialidade;
    document.getElementById('profAtivo').checked= p.ativo == 1;
    document.getElementById('campoAtivo').style.display = '';
    document.getElementById('emailPreview').textContent = '';
    
    const modalEl = document.getElementById('modalProf');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Input preview for BI
    const profBi = document.getElementById('profBi');
    if (profBi) {
        profBi.addEventListener('input', function() {
            var preview = document.getElementById('emailPreview');
            if (preview) {
                preview.textContent = this.value ? 'Email de acesso: ' + this.value + '@RegNota.com' : '';
            }
        });
    }

    // Novo Professor button
    const btnNovo = document.getElementById('btnNovo');
    if (btnNovo) {
        btnNovo.addEventListener('click', function() {
            document.getElementById('profTitulo').textContent = 'Novo Professor';
            document.getElementById('profAcao').value = 'criar';
            document.getElementById('profId').value   = '';
            
            const modalProf = document.getElementById('modalProf');
            if (modalProf) {
                const form = modalProf.querySelector('form');
                if (form) form.reset();
                
                document.getElementById('campoAtivo').style.display = 'none';
                document.getElementById('emailPreview').textContent = '';
                bootstrap.Modal.getOrCreateInstance(modalProf).show();
            }
        });
    }
});
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
