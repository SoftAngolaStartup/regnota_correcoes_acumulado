<?php
/**
 * public/admin/usuarios.php
 * Gestão de Utilizadores do Sistema – CRUD (apenas Super Admin)
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Url, Sanitizar, Formatar, Etiqueta, Auditoria};
use App\Helpers\Bd;
use App\Models\UsuarioModelo;
use App\Middleware\Autenticacao;
Autenticacao::exigirAdmin();

$um  = new UsuarioModelo();
$uid = Sessao::utilizador()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        Sessao::flash('erro', 'Token inválido.'); header('Location: usuarios.php'); exit;
    }
    $acao = $_POST['_acao'] ?? '';
    if ($acao === 'editar') {
        $id    = (int)($_POST['id'] ?? 0);
        $nome  = Sanitizar::texto($_POST['nome'] ?? '');
        $email = Sanitizar::texto($_POST['email'] ?? '');
        $perfil= $_POST['perfil'] ?? '';
        $ativo = (int)($_POST['ativo'] ?? 1);
        $perfisValidos = ['super_admin','coordenador','professor'];
        if ($id && $nome && in_array($perfil, $perfisValidos)) {
            $um->actualizar($id, $nome, $email, $perfil, $ativo);
            Auditoria::registar('editar_usuario','usuarios',$id,null,$uid);
            Sessao::flash('sucesso', 'Utilizador actualizado!');
        }
    } elseif ($acao === 'reset_senha') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $um->actualizarSenha($id, SENHA_INICIAL);
            // Forçar alteração no próximo login
            Bd::obter()->prepare("UPDATE usuarios SET primeiro_login=1, senha_alterada=0 WHERE id=?")->execute([$id]);
            Auditoria::registar('reset_senha','usuarios',$id,'Senha reposta para padrão',$uid);
            Sessao::flash('sucesso', 'Senha reposta para o valor padrão (RegNota@123).');
        }
    } elseif ($acao === 'alternar_ativo') {
        $id    = (int)($_POST['id'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 0);
        if ($id && $id !== $uid) {
            Bd::obter()->prepare("UPDATE usuarios SET ativo=? WHERE id=?")->execute([$ativo, $id]);
            Sessao::flash('sucesso', 'Estado do utilizador alterado.');
        } else {
            Sessao::flash('erro', 'Não pode alterar o estado da sua própria conta.');
        }
    } elseif ($acao === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== $uid) {
            if ($um->eliminar($id)) {
                Auditoria::registar('eliminar_usuario', 'usuarios', $id, 'Utilizador eliminado permanentemente', $uid);
                Sessao::flash('sucesso', 'Utilizador eliminado com sucesso.');
            } else {
                Sessao::flash('erro', 'Erro ao eliminar utilizador.');
            }
        } else {
            Sessao::flash('erro', 'Não pode eliminar a sua própria conta.');
        }
    }
    header('Location: usuarios.php'); exit;
}

$utilizadores = $um->listarTodos();
$menuLateral  = require APP_DIR . '/app/Helpers/MenuAdmin.php';
$tituloPagina = 'Utilizadores'; $paginaActiva = 'usuarios';
require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>
<div class="glass-card mb-4">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-funnel"></i> Filtros</span>
  </div>
  <div class="card-conteudo-corpo">
    <div class="row g-3">
        <div class="col-sm-6 col-md-4">
            <input type="text" class="campo-input" placeholder="Pesquisar por nome ou email...">
        </div>
        <div class="col-sm-6 col-md-2">
            <button class="btn-base btn-primario w-100">Pesquisar</button>
        </div>
    </div>
  </div>
</div>

<div class="glass-card">
  <div class="glass-header">
    <span class="card-conteudo-titulo"><i class="bi bi-shield-check"></i> Utilizadores do Sistema
      <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($utilizadores) ?></span>
    </span>
  </div>
  <div class="card-conteudo-corpo" style="padding: 0;">
    <div style="overflow-x:auto;">
      <table class="tabela-sistema">
        <thead><tr><th>Nome</th><th>Email</th><th>Perfil</th><th>Último Login</th><th>Tentativas</th><th>Estado</th><th style="text-align:center">Acções</th></tr></thead>
        <tbody>
          <?php foreach ($utilizadores as $u): ?>
          <tr <?= $u['id'] == $uid ? 'style="background:#fffbeb"' : '' ?>>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div style="width:32px;height:32px;border-radius:8px;background:var(--primario);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0"><?= strtoupper(mb_substr($u['nome'],0,1)) ?></div>
                <div>
                  <div class="td-nome"><?= Sanitizar::html($u['nome']) ?> <?= $u['id']==$uid?'<span class="badge-estado badge-analise" style="font-size:10px">Você</span>':'' ?></div>
                  <div class="td-muted" style="font-size:11px">
                    <?php if ($u['primeiro_login']): ?><span class="badge-estado badge-submetida" style="font-size:10px">1º login pendente</span><?php endif; ?>
                  </div>
                </div>
              </div>
            </td>
            <td style="font-size:12px;font-family:monospace"><?= Sanitizar::html($u['email']) ?></td>
            <td><?php
              $chipPerfil = ['super_admin'=>'chip-super-admin','coordenador'=>'chip-coordenador','professor'=>'chip-professor'];
              ?><span class="chip-perfil <?= $chipPerfil[$u['perfil']] ?? '' ?>"><?= Etiqueta::perfil($u['perfil']) ?></span></td>
            <td class="td-muted"><?= $u['ultimo_login'] ? Formatar::dataHora($u['ultimo_login']) : 'Nunca' ?></td>
            <td><?= $u['tentativas_login'] > 0 ? '<span class="badge-estado badge-cancelada">'.$u['tentativas_login'].' falhas</span>' : '<span class="td-muted">0</span>' ?></td>
            <td><span class="badge-estado <?= $u['ativo'] ? 'badge-resolvida' : 'badge-cancelada' ?>"><?= $u['ativo'] ? 'Activo' : 'Inactivo' ?></span></td>
            <td>
              <div class="d-flex gap-1 justify-content-center">
                <button class="btn-icone" title="Editar" onclick="abrirEditar(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="margin:0" onsubmit="return confirm('Repor senha para RegNota@123?')">
                  <?= Sessao::campoCsrf() ?>
                  <input type="hidden" name="_acao" value="reset_senha">
                  <input type="hidden" name="id"    value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-icone aviso" title="Repor Senha"><i class="bi bi-key"></i></button>
                </form>
                <?php if ($u['id'] != $uid): ?>
                <form method="POST" style="margin:0">
                  <?= Sessao::campoCsrf() ?>
                  <input type="hidden" name="_acao" value="alternar_ativo">
                  <input type="hidden" name="id"    value="<?= $u['id'] ?>">
                  <input type="hidden" name="ativo"  value="<?= $u['ativo'] ? 0 : 1 ?>">
                  <button type="submit" class="btn-icone <?= $u['ativo'] ? 'perigo' : 'aviso' ?>" title="<?= $u['ativo'] ? 'Desactivar' : 'Activar' ?>">
                    <i class="bi bi-toggle-<?= $u['ativo'] ? 'on' : 'off' ?>"></i>
                  </button>
                </form>
                <form method="POST" style="margin:0" onsubmit="return confirm('Eliminar permanentemente este utilizador?')">
                  <?= Sessao::campoCsrf() ?>
                  <input type="hidden" name="_acao" value="eliminar">
                  <input type="hidden" name="id"    value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-icone perigo" title="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="alerta alerta-aviso mt-4">
  <i class="bi bi-shield-exclamation" style="flex-shrink:0;font-size:15px"></i>
  <span>Os utilizadores são criados automaticamente ao registar professores e coordenadores. Esta secção permite gerir estados e repor senhas.</span>
</div>
<!-- Modal Editar -->
<div class="modal fade" id="modalUser" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-cabecalho d-flex align-items-center justify-content-between">
    <h5 class="mb-0">Editar Utilizador</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <form method="POST"><?= Sessao::campoCsrf() ?>
    <input type="hidden" name="_acao" value="editar">
    <input type="hidden" name="id"    id="userId"  value="">
    <div class="modal-body p-4"><div class="row g-3">
      <div class="col-12"><label class="rotulo-campo">Nome</label><input type="text" name="nome" id="userNome" class="campo-input" required></div>
      <div class="col-12"><label class="rotulo-campo">Email</label><input type="email" name="email" id="userEmail" class="campo-input"></div>
      <div class="col-sm-6"><label class="rotulo-campo">Perfil</label>
        <select name="perfil" id="userPerfil" class="campo-input">
          <option value="super_admin">Super Admin</option>
          <option value="coordenador">Coordenador</option>
          <option value="professor">Professor</option>
        </select>
      </div>
      <div class="col-sm-6"><label class="rotulo-campo">Estado</label>
        <select name="ativo" id="userAtivo" class="campo-input">
          <option value="1">Activo</option>
          <option value="0">Inactivo</option>
        </select>
      </div>
    </div></div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
    </div>
  </form>
</div></div></div>
<script>
function abrirEditar(u) {
    document.getElementById('userId').value    = u.id;
    document.getElementById('userNome').value  = u.nome;
    document.getElementById('userEmail').value = u.email;
    document.getElementById('userPerfil').value= u.perfil;
    document.getElementById('userAtivo').value = u.ativo;
    new bootstrap.Modal(document.getElementById('modalUser')).show();
}
</script>
<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
