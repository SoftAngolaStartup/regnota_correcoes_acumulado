<?php
/**
 * public/admin/semestres.php
 * Gestão de Semestres – CRUD Completo
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Formatar};
use App\Models\{SemestreModelo, AnoLetivoModelo};
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$sm  = new SemestreModelo();
$alm = new AnoLetivoModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro', 'Token inválido.'); header('Location: semestres.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'criar') {
        $anoId  = (int)($_POST['ano_letivo_id'] ?? 0);
        $num    = (int)($_POST['numero'] ?? 0);
        $nome   = Sanitizar::texto($_POST['nome'] ?? '');
        $inicio = $_POST['data_inicio'] ?? '';
        $fim    = $_POST['data_fim'] ?? '';
        $ativo  = (int)($_POST['ativo'] ?? 0);
        if ($anoId && in_array($num, [1,2]) && $nome) {
            $sm->criar($anoId, $num, $nome, $inicio ?: null, $fim ?: null, $ativo);
            Sessao::flash('sucesso', 'Semestre criado!');
        } else Sessao::flash('erro', 'Preencha todos os campos obrigatórios.');
    } elseif ($acao === 'editar') {
        $id    = (int)($_POST['id'] ?? 0);
        $nome  = Sanitizar::texto($_POST['nome'] ?? '');
        $inicio= $_POST['data_inicio'] ?? '';
        $fim   = $_POST['data_fim'] ?? '';
        $ativo = (int)($_POST['ativo'] ?? 0);
        if ($id && $nome) { $sm->actualizar($id, $nome, $inicio ?: null, $fim ?: null, $ativo); Sessao::flash('sucesso', 'Semestre actualizado!'); }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$sm->eliminar($id)) Sessao::flash('erro', 'Não é possível eliminar: existem ocorrências associadas.');
        else Sessao::flash('sucesso', 'Semestre eliminado.');
    }
    header('Location: semestres.php'); exit;
}

$semestres   = $sm->listarTodos();
$anos        = $alm->listarTodos();
$anoAtivo    = $alm->obterAtivo();
$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Semestres'; $paginaActiva = 'semestres';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="d-flex justify-content-end mb-4">
  <button class="btn-base btn-primario" data-bs-toggle="modal" data-bs-target="#modalSem" id="btnNovo">
    <i class="bi bi-plus-lg"></i> Novo Semestre
  </button>
</div>

<div class="glass-card">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-calendar-range"></i> Semestres
      <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($semestres) ?></span>
    </span>
  </div>
  <div style="overflow-x:auto; padding: 15px;">
    <table class="tabela-sistema">
      <thead><tr><th>Nome</th><th>Nº</th><th>Ano Letivo</th><th>Início</th><th>Fim</th><th>Estado</th><th style="text-align:center">Acções</th></tr></thead>
      <tbody>
        <?php if (empty($semestres)): ?>
        <tr><td colspan="7"><div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhum semestre registado.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($semestres as $s): ?>
        <tr>
          <td class="td-nome"><?= Sanitizar::html($s['nome']) ?></td>
          <td><span class="chip-tipo"><?= $s['numero'] ?>º</span></td>
          <td class="td-muted"><?= Sanitizar::html($s['ano_nome']) ?></td>
          <td class="td-muted"><?= Formatar::data($s['data_inicio']) ?></td>
          <td class="td-muted"><?= Formatar::data($s['data_fim']) ?></td>
          <td><span class="badge-estado <?= $s['ativo'] ? 'badge-resolvida' : 'badge-encerrada' ?>"><?= $s['ativo'] ? 'Activo' : 'Inactivo' ?></span></td>
          <td>
            <div class="d-flex gap-1 justify-content-center">
              <button class="btn-icone" onclick="abrirEditar(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
              <form method="POST" style="margin:0" onsubmit="return confirmarEliminar()">
                <?= Sessao::campoCsrf() ?>
                <input type="hidden" name="_acao" value="eliminar">
                <input type="hidden" name="id"    value="<?= $s['id'] ?>">
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
<div class="modal fade" id="modalSem" tabindex="-1"><div class="modal-dialog modal-md"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between">
    <h5 class="mb-0" id="semTitulo">Novo Semestre</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <form method="POST"><?= Sessao::campoCsrf() ?>
    <input type="hidden" name="_acao" id="semAcao" value="criar">
    <input type="hidden" name="id"    id="semId"   value="">
    <div class="modal-body p-4"><div class="row g-3">
      <div class="col-sm-6">
        <label class="rotulo-campo">Ano Letivo *</label>
        <select name="ano_letivo_id" id="semAno" class="campo-input" required onchange="autoNome()">
          <option value="">-- Selecionar --</option>
          <?php foreach ($anos as $a): ?>
          <option value="<?= $a['id'] ?>" data-nome="<?= Sanitizar::html($a['nome']) ?>" <?= ($anoAtivo && $a['id']==$anoAtivo['id']) ? 'selected' : '' ?>><?= Sanitizar::html($a['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6">
        <label class="rotulo-campo">Número *</label>
        <select name="numero" id="semNum" class="campo-input" required onchange="autoNome()">
          <option value="">--</option>
          <option value="1">1º Semestre</option>
          <option value="2">2º Semestre</option>
        </select>
      </div>
      <div class="col-12">
        <label class="rotulo-campo">Nome *</label>
        <input type="text" name="nome" id="semNome" class="campo-input" required placeholder="Ex: 1º Semestre 2025/2026">
      </div>
      <div class="col-sm-6">
        <label class="rotulo-campo">Data de Início</label>
        <input type="date" name="data_inicio" id="semInicio" class="campo-input">
      </div>
      <div class="col-sm-6">
        <label class="rotulo-campo">Data de Fim</label>
        <input type="date" name="data_fim" id="semFim" class="campo-input">
      </div>
      <div class="col-12">
        <div class="form-check">
          <input type="checkbox" name="ativo" value="1" id="semAtivo" class="form-check-input">
          <label class="form-check-label" for="semAtivo">Semestre activo</label>
        </div>
      </div>
    </div></div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
    </div>
  </form>
</div></div></div>
<script>
function autoNome() {
    var num = document.getElementById('semNum');
    var ano = document.getElementById('semAno');
    if (num.value && ano.selectedIndex > 0) {
        var nomeAno = ano.options[ano.selectedIndex].dataset.nome;
        document.getElementById('semNome').value = num.value + 'º Semestre ' + nomeAno;
    }
}
function abrirEditar(s) {
    document.getElementById('semTitulo').textContent = 'Editar Semestre';
    document.getElementById('semAcao').value   = 'editar';
    document.getElementById('semId').value     = s.id;
    document.getElementById('semNome').value   = s.nome;
    document.getElementById('semInicio').value = s.data_inicio || '';
    document.getElementById('semFim').value    = s.data_fim || '';
    document.getElementById('semAtivo').checked= s.ativo == 1;
    new bootstrap.Modal(document.getElementById('modalSem')).show();
}
document.getElementById('btnNovo').addEventListener('click', function() {
    document.getElementById('semTitulo').textContent = 'Novo Semestre';
    document.getElementById('semAcao').value = 'criar';
    document.getElementById('semId').value   = '';
    document.getElementById('modalSem').querySelector('form').reset();
});
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
