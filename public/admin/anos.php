<?php
/**
 * public/admin/anos.php
 * Gestão de Anos Letivos – CRUD Completo
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Formatar, Auditoria};
use App\Models\AnoLetivoModelo;
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$am  = new AnoLetivoModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro', 'Token inválido.'); header('Location: anos.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'criar') {
        $nome  = Sanitizar::texto($_POST['nome'] ?? '');
        $ativo = (int)($_POST['ativo'] ?? 0);
        if (!preg_match('/^\d{4}\/\d{4}$/', $nome)) {
            Sessao::flash('erro', 'Formato inválido. Use AAAA/AAAA (ex: 2025/2026).');
        } else {
            $id = $am->criar($nome, $ativo);
            Auditoria::registar('criar_ano_letivo', 'anos_letivos', $id, $nome, $uid);
            Sessao::flash('sucesso', "Ano letivo «{$nome}» criado!");
        }
    } elseif ($acao === 'editar') {
        $id    = (int)($_POST['id'] ?? 0);
        $nome  = Sanitizar::texto($_POST['nome'] ?? '');
        $ativo = (int)($_POST['ativo'] ?? 0);
        if ($id && preg_match('/^\d{4}\/\d{4}$/', $nome)) {
            $am->actualizar($id, $nome, $ativo);
            Sessao::flash('sucesso', 'Ano letivo actualizado!');
        }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$am->eliminar($id)) Sessao::flash('erro', 'Não é possível eliminar: existem semestres associados.');
        else Sessao::flash('sucesso', 'Ano letivo eliminado.');
    }
    header('Location: anos.php'); exit;
}

$anos        = $am->listarTodos();
$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Anos Letivos'; $paginaActiva = 'anos';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="d-flex justify-content-end mb-4">
  <button class="btn-base btn-primario" data-bs-toggle="modal" data-bs-target="#modalAno" id="btnNovo">
    <i class="bi bi-plus-lg"></i> Novo Ano Letivo
  </button>
</div>
<div class="glass-card">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-calendar3"></i> Anos Letivos
      <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($anos) ?></span>
    </span>
  </div>
  <div style="overflow-x:auto">
    <table class="tabela-sistema">
      <thead><tr><th>Ano Letivo</th><th>Estado</th><th>Criado em</th><th style="text-align:center">Acções</th></tr></thead>
      <tbody>
        <?php if (empty($anos)): ?>
        <tr><td colspan="4"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhum ano letivo registado.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($anos as $a): ?>
        <tr>
          <td><span style="font-size:1.1rem;font-weight:800;color:var(--primario)"><?= Sanitizar::html($a['nome']) ?></span></td>
          <td>
            <?php if ($a['ativo']): ?>
            <span class="badge-estado badge-resolvida"><i class="bi bi-check-circle-fill"></i> Activo</span>
            <?php else: ?>
            <span class="badge-estado badge-encerrada">Inactivo</span>
            <?php endif; ?>
          </td>
          <td class="td-muted"><?= Formatar::data($a['criado_em']) ?></td>
          <td>
            <div class="d-flex gap-1 justify-content-center">
              <button class="btn-icone" title="Editar" onclick="abrirEditar(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
              <form method="POST" style="margin:0" onsubmit="return confirmarEliminar('Eliminar o ano «<?= Sanitizar::html($a['nome']) ?>»?')">
                <?= Sessao::campoCsrf() ?>
                <input type="hidden" name="_acao" value="eliminar">
                <input type="hidden" name="id"    value="<?= $a['id'] ?>">
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

<!-- Modal -->
<div class="modal fade" id="modalAno" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between">
    <h5 class="mb-0" id="anoTitulo">Novo Ano Letivo</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <form method="POST"><?= Sessao::campoCsrf() ?>
    <input type="hidden" name="_acao" id="anoAcao" value="criar">
    <input type="hidden" name="id"    id="anoId"   value="">
    <div class="modal-body p-4">
      <div class="mb-3">
        <label class="rotulo-campo">Nome do Ano Letivo *</label>
        <input type="text" name="nome" id="anoNome" class="campo-input" placeholder="Ex: 2025/2026" pattern="\d{4}/\d{4}" required>
        <small class="text-muted">Formato obrigatório: AAAA/AAAA</small>
      </div>
      <div class="form-check">
        <input type="checkbox" name="ativo" value="1" id="anoAtivo" class="form-check-input">
        <label class="form-check-label" for="anoAtivo">
          Definir como ano letivo <strong>activo</strong>
          <div><small class="text-muted">Apenas um ano pode estar activo de cada vez.</small></div>
        </label>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-base btn-fantasma" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" class="btn-base btn-primario"><i class="bi bi-save me-1"></i>Guardar</button>
    </div>
  </form>
</div></div></div>
<script>
function abrirEditar(a) {
    document.getElementById('anoTitulo').textContent = 'Editar Ano Letivo';
    document.getElementById('anoAcao').value  = 'editar';
    document.getElementById('anoId').value    = a.id;
    document.getElementById('anoNome').value  = a.nome;
    document.getElementById('anoAtivo').checked = a.ativo == 1;
    new bootstrap.Modal(document.getElementById('modalAno')).show();
}
document.getElementById('btnNovo').addEventListener('click', function() {
    document.getElementById('anoTitulo').textContent = 'Novo Ano Letivo';
    document.getElementById('anoAcao').value = 'criar';
    document.getElementById('anoId').value   = '';
    document.getElementById('anoNome').value = '';
    document.getElementById('anoAtivo').checked = false;
});
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
