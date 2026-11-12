<?php
/**
 * public/alterar-senha.php
 * Alterar Senha – Acessível a qualquer utilizador autenticado
 */
require_once __DIR__ . '/../app/bootstrap.php';

use App\Helpers\Sessao;
use App\Helpers\Url;
use App\Helpers\Sanitizar;
use App\Helpers\Auditoria;
use App\Models\UsuarioModelo;
use App\Middleware\Autenticacao;

Sessao::exigirLogin();

$erro   = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        $erro = 'Pedido inválido.';
    } else {
        $senhaAtual = $_POST['senha_atual']    ?? '';
        $novaSenha  = $_POST['nova_senha']     ?? '';
        $confirmar  = $_POST['confirmar_senha'] ?? '';

        $um  = new UsuarioModelo();
        $uid = Sessao::utilizador()['id'];
        $u   = $um->obterPorId($uid);

        if (!password_verify($senhaAtual, $u['password'] ?? '')) {
            $erro = 'A senha actual está incorrecta.';
        } elseif (strlen($novaSenha) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif (!preg_match('/[A-Z]/', $novaSenha) || !preg_match('/[0-9]/', $novaSenha)) {
            $erro = 'A senha deve conter pelo menos uma letra maiúscula e um número.';
        } elseif ($novaSenha !== $confirmar) {
            $erro = 'As senhas não coincidem.';
        } elseif ($novaSenha === SENHA_INICIAL) {
            $erro = 'A nova senha não pode ser igual à senha inicial do sistema.';
        } else {
            $um->actualizarSenha($uid, $novaSenha);
            $_SESSION['primeiro_login'] = false;
            Auditoria::registar('alterar_senha', 'usuarios', $uid, 'Senha alterada com sucesso');
            Sessao::flash('sucesso', 'Senha alterada com sucesso!');
            
            // Redirecionar para o painel conforme o perfil
            Autenticacao::redirecionarPorPerfil();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alterar Senha | <?= APP_NOME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="<?= \App\Helpers\Url::asset('css/regnotas.css') ?>">
  <style>
    body { background: linear-gradient(140deg,#072d52 0%,#0b3d6e 55%,#1a5fa0 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; font-family:var(--font-family); }
    .card-alterar { background:#fff; border-radius:var(--radius-xl); overflow:hidden; box-shadow:0 30px 70px rgba(0,0,0,.3); width:100%; max-width:430px; }
    .card-topo { background:var(--academic-blue); padding:28px 36px 22px; }
    .card-topo h1 { color:#fff; font-size:var(--text-md); margin:0 0 4px; }
    .card-topo p  { color:rgba(255,255,255,.6); font-size:var(--text-sm); margin:0; }
    .card-corpo { padding:var(--space-8) var(--space-6); }
    .campo-input { font-family:var(--font-family); }
    .btn-salvar { width:100%; background:var(--academic-blue); color:#fff; border:none; padding:12px; border-radius:var(--radius-md); font-weight:700; font-family:var(--font-family); font-size:var(--text-md); cursor:pointer; }
    .btn-salvar:hover { background:var(--academic-navy); }
    .req-senha { background:#f7f9fd; border-radius:var(--radius-sm); padding:10px var(--space-4); font-size:var(--text-sm); color:var(--text-secondary); margin-bottom:var(--space-4); }
    .req-senha li { margin-bottom:var(--space-1); }
  </style>
</head>
<body>
<div class="card-alterar">
  <div class="card-topo">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <div style="width:40px;height:40px;background:var(--destaque);border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:18px;color:var(--primario-escuro)">R</div>
      <div>
        <h1>Alterar Senha</h1>
        <p>Atualize a sua senha de acesso ao sistema</p>
      </div>
    </div>
    <?php if (Sessao::utilizador()['primeiro_login'] ?? false): ?>
    <div class="alerta alerta-aviso" style="margin:0">
      <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0"></i>
      <span>Por segurança, a senha inicial deve ser alterada no primeiro acesso.</span>
    </div>
    <?php endif; ?>
  </div>
  <div class="card-corpo">
    <?php if ($erro): ?>
    <div class="alerta alerta-erro mb-3">
      <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0"></i>
      <span><?= Sanitizar::html($erro) ?></span>
    </div>
    <?php endif; ?>

    <div class="req-senha">
      <strong>Requisitos da nova senha:</strong>
      <ul class="mb-0 mt-1 ps-3">
        <li>Mínimo de 8 caracteres</li>
        <li>Pelo menos uma letra maiúscula</li>
        <li>Pelo menos um número</li>
        <li>Diferente da senha inicial (RegNota@123)</li>
      </ul>
    </div>

    <form method="POST">
      <?= Sessao::campoCsrf() ?>
      <div class="mb-3">
        <label class="rotulo-campo">Senha Actual</label>
        <input type="password" name="senha_atual" class="campo-input" placeholder="Senha actual" required>
      </div>
      <div class="mb-3">
        <label class="rotulo-campo">Nova Senha</label>
        <input type="password" name="nova_senha" id="novaSenha" class="campo-input" placeholder="Nova senha" required>
      </div>
      <div class="mb-4">
        <label class="rotulo-campo">Confirmar Nova Senha</label>
        <input type="password" name="confirmar_senha" class="campo-input" placeholder="Repetir nova senha" required>
      </div>
      <button type="submit" class="btn-salvar">
        <i class="bi bi-check-circle me-2"></i>Guardar Senha
      </button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
