<?php
/**
 * public/admin/coordenadores.php
 * Gestão de Coordenadores – CRUD Completo
 * Um coordenador por curso; cria automaticamente utilizador (BI@RegNota.com)
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Url, Sanitizar, Formatar, Auditoria};
use App\Models\{CoordenadorModelo, CursoModelo};
use App\Middleware\Autenticacao;

Autenticacao::exigirAdmin();

$cm  = new CoordenadorModelo();
$cur = new CursoModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro', 'Token inválido.'); header('Location: coordenadores.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'criar') {
        $dados = [
            'nome'          => Sanitizar::texto($_POST['nome'] ?? ''),
            'bi'            => Sanitizar::texto($_POST['bi'] ?? ''),
            'telefone'      => Sanitizar::texto($_POST['telefone'] ?? ''),
            'email_pessoal' => Sanitizar::texto($_POST['email_pessoal'] ?? ''),
            'curso_id'      => (int)($_POST['curso_id'] ?? 0),
        ];
        if (!$dados['nome'] || !$dados['bi'] || !$dados['curso_id']) {
            Sessao::flash('erro', 'Preencha todos os campos obrigatórios.');
        } else {
            $id = $cm->criar($dados);
            Auditoria::registar('criar_coordenador', 'coordenadores', $id,
                "Coordenador: {$dados['nome']} – email: {$dados['bi']}@RegNota.com", $uid);
            Sessao::flash('sucesso', "Coordenador criado! Acesso: {$dados['bi']}@RegNota.com | Senha: RegNota@123");
        }
    } elseif ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $dados = [
            'nome'          => Sanitizar::texto($_POST['nome'] ?? ''),
            'bi'            => Sanitizar::texto($_POST['bi'] ?? ''),
            'telefone'      => Sanitizar::texto($_POST['telefone'] ?? ''),
            'email_pessoal' => Sanitizar::texto($_POST['email_pessoal'] ?? ''),
            'ativo'         => (int)($_POST['ativo'] ?? 1),
        ];
        if ($id && $dados['nome']) {
            $cm->actualizar($id, $dados);
            Sessao::flash('sucesso', 'Coordenador actualizado!');
        }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $cm->eliminar($id);
        Sessao::flash('sucesso', 'Coordenador eliminado.');
    }
    header('Location: coordenadores.php'); exit;
}

$coordenadores = $cm->listarTodos();
$cursos        = $cur->listarTodos(true);

$menuLateral  = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Coordenadores';
$paginaActiva = 'coordenadores';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>

<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-funnel"></i> Filtros e Acções</span>
    <button class="btn-base btn-primario" data-bs-toggle="modal" data-bs-target="#modalCoord" id="btnNovo">
        <i class="bi bi-plus-lg me-1"></i> Novo Coordenador
    </button>
  </div>
  <div class="card-conteudo-corpo">
    <div class="row g-3">
        <div class="col-sm-6 col-md-4">
            <input type="text" class="campo-input" placeholder="Pesquisar por nome ou curso...">
        </div>
        <div class="col-sm-6 col-md-2">
            <button class="btn-base btn-primario w-100">Pesquisar</button>
        </div>
    </div>
  </div>
</div>

<div class="alerta alerta-info mb-4">
  <i class="bi bi-info-circle-fill" style="flex-shrink:0;font-size:15px"></i>
  <span>Cada curso tem <strong>um único coordenador</strong>. Ao criar, é gerado o email <strong>BI@RegNota.com</strong> com senha <strong>RegNota@123</strong>.</span>
</div>

<div class="glass-card">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-person-workspace"></i> Coordenadores
      <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($coordenadores) ?></span>
    </span>
  </div>
  <div class="card-conteudo-corpo" style="padding: 0;">
    <div style="overflow-x:auto">
      <table class="tabela-sistema">
        <thead>
          <tr><th>Nome</th><th>Curso</th><th>BI</th><th>Email Acesso</th><th>Telefone</th><th>Último Login</th><th>Estado</th><th style="text-align:center">Acções</th></tr>
        </thead>
        <tbody>
          <?php if (empty($coordenadores)): ?>
          <tr><td colspan="8"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhum coordenador registado.</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($coordenadores as $c): ?>
          <tr>
            <td class="td-nome"><?= Sanitizar::html($c['nome']) ?></td>
            <td>
              <span style="font-size:12px;font-weight:700;background:var(--primario);color:#fff;padding:2px 8px;border-radius:6px"><?= Sanitizar::html($c['curso_codigo']) ?></span>
              <div class="td-muted"><?= Sanitizar::html($c['curso_nome']) ?></div>
            </td>
            <td class="td-muted"><?= Sanitizar::html($c['bi']) ?></td>
            <td style="font-size:12px;font-family:monospace"><?= Sanitizar::html($c['bi']) ?>@RegNota.com</td>
            <td class="td-muted"><?= Sanitizar::html($c['telefone']) ?></td>
            <td class="td-muted"><?= $c['ultimo_login'] ? Formatar::dataHora($c['ultimo_login']) : 'Nunca' ?></td>
            <td><span class="badge-estado <?= $c['ativo'] ? 'badge-resolvida' : 'badge-cancelada' ?>"><?= $c['ativo'] ? 'Activo' : 'Inactivo' ?></span></td>
            <td>
              <div class="d-flex gap-1 justify-content-center">
                <button class="btn-icone" onclick="abrirEditar(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="margin:0" onsubmit="return confirmarEliminar('Eliminar coordenador?')">
                  <?= Sessao::campoCsrf() ?>
                  <input type="hidden" name="_acao" value="eliminar">
                  <input type="hidden" name="id"    value="<?= $c['id'] ?>">
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

<!-- Modal -->
<div class="modal fade" id="modalCoord" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-cabecalho d-flex align-items-center justify-content-between">
        <h5 class="mb-0" id="coordTitulo">Novo Coordenador</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= Sessao::campoCsrf() ?>
        <input type="hidden" name="_acao" id="coordAcao" value="criar">
        <input type="hidden" name="id"    id="coordId"   value="">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="rotulo-campo">Curso *</label>
              <select name="curso_id" id="coordCurso" class="campo-input" required>
                <option value="">-- Selecionar curso --</option>
                <?php foreach ($cursos as $cur): ?>
                <option value="<?= $cur['id'] ?>"><?= Sanitizar::html($cur['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Nome Completo *</label>
              <input type="text" name="nome" id="coordNome" class="campo-input" required>
            </div>
            <div class="col-sm-6">
              <label class="rotulo-campo">Número do BI *</label>
              <input type="text" name="bi" id="coordBi" class="campo-input" required placeholder="003214567LA042">
            </div>
            <div class="col-sm-6">
              <label class="rotulo-campo">Telefone *</label>
              <input type="text" name="telefone" id="coordTel" class="campo-input" required>
            </div>
            <div class="col-12">
              <label class="rotulo-campo">Email Pessoal</label>
              <input type="email" name="email_pessoal" id="coordEmailP" class="campo-input">
            </div>
            <div class="col-12" id="coordAtivoDiv" style="display:none">
              <div class="form-check">
                <input type="checkbox" name="ativo" value="1" id="coordAtivo" class="form-check-input" checked>
                <label class="form-check-label" for="coordAtivo">Coordenador activo</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-base btn-fantasma" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn-base btn-primario"><i class="bi bi-save me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function abrirEditar(c) {
    document.getElementById('coordTitulo').textContent = 'Editar Coordenador';
    document.getElementById('coordAcao').value   = 'editar';
    document.getElementById('coordId').value     = c.id;
    document.getElementById('coordNome').value   = c.nome;
    document.getElementById('coordBi').value     = c.bi;
    document.getElementById('coordTel').value    = c.telefone;
    document.getElementById('coordEmailP').value = c.email_pessoal || '';
    document.getElementById('coordAtivo').checked= c.ativo == 1;
    document.getElementById('coordAtivoDiv').style.display = '';
    
    // Fix: Set the course ID and keep disabled
    document.getElementById('coordCurso').value = c.curso_id;
    document.getElementById('coordCurso').disabled = true;
    
    new bootstrap.Modal(document.getElementById('modalCoord')).show();
}
document.getElementById('btnNovo').addEventListener('click', function() {
    document.getElementById('coordTitulo').textContent = 'Novo Coordenador';
    document.getElementById('coordAcao').value = 'criar';
    document.getElementById('coordId').value   = '';
    document.getElementById('modalCoord').querySelector('form').reset();
    document.getElementById('coordAtivoDiv').style.display = 'none';
    document.getElementById('coordCurso').disabled = false;
});
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
