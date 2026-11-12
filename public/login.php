<?php
/**
 * public/login.php
 * Página de Login Unificada – Admin, Coordenador, Professor
 *
 * Fluxo:
 *  1. Verifica CSRF
 *  2. Procura utilizador por email
 *  3. Verifica bloqueio por tentativas
 *  4. Valida senha com bcrypt
 *  5. Regista na auditoria
 *  6. Redireciona para o painel correcto conforme perfil
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Helpers\Sessao;
use App\Helpers\Url;
use App\Helpers\Auditoria;
use App\Helpers\Sanitizar;
use App\Models\UsuarioModelo;
use App\Middleware\Autenticacao;

// Se já autenticado, redirecionar para painel correcto
if (Sessao::autenticado()) {
    Autenticacao::redirecionarPorPerfil();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        $erro = 'Pedido inválido. Por favor recarregue a página.';
    } else {
        $email = Sanitizar::texto($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!$email || !$senha) {
            $erro = 'Por favor preencha o email e a senha.';
        } else {
            $um = new UsuarioModelo();
            $utilizador = $um->obterPorEmail($email);

            if (!$utilizador) {
                $erro = 'Email ou senha incorrectos.';
                Auditoria::registar('login_falhado', 'usuarios', null,
                    "Email não encontrado: $email");
                sleep(1); // Anti-brute-force
            } elseif ($um->verificarBloqueio($utilizador['id'])) {
                $erro = 'Conta temporariamente bloqueada por excesso de tentativas. '
                      . 'Tente novamente em ' . BLOQUEIO_MINUTOS . ' minutos.';
                Auditoria::registar('login_bloqueado', 'usuarios', $utilizador['id'],
                    "Tentativa durante bloqueio");
            } elseif (!password_verify($senha, $utilizador['password'])) {
                $um->incrementarTentativas($utilizador['id']);
                $tentativas = (int)($utilizador['tentativas_login'] ?? 0) + 1;
                $restam     = MAX_TENTATIVAS_LOGIN - $tentativas;
                $erro = 'Email ou senha incorrectos.'
                      . ($restam > 0 ? " Restam $restam tentativa(s)." : '');
                Auditoria::registar('login_falhado', 'usuarios', $utilizador['id'],
                    "Senha incorrecta. Tentativa $tentativas");
                sleep(1);
            } else {
                // Login com sucesso
                $um->registarLogin($utilizador['id']);
                Sessao::iniciarLogin($utilizador);

                // Guardar flag de primeiro login na sessão
                if ($utilizador['primeiro_login']) {
                    $_SESSION['primeiro_login'] = true;
                }

                Auditoria::registar('login_sucesso', 'usuarios', $utilizador['id'],
                    "Login bem-sucedido. Perfil: {$utilizador['perfil']}",
                    $utilizador['id']);

                // Redirecionar para a página pedida originalmente (ou painel padrão)
                $rd = $_GET['rd'] ?? '';
                if ($rd && strpos($rd, '/') === 0 && strpos($rd, '//') !== 0 && strpos($rd, '\\') === false) {
                    Url::redirecionar($rd);
                }
                Autenticacao::redirecionarPorPerfil($utilizador['perfil']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acesso ao Sistema | <?= APP_NOME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?php require APP_DIR . '/views/shared/tema_head.php'; ?>
  <link rel="stylesheet" type="text/css" href="<?= \App\Helpers\Url::asset('css/regnotas.css') ?>">
  <style>
    .login-envolvente { width:100%; max-width:430px; }
    .login-card { background:var(--surface-card); color:var(--text-primary); border:1px solid var(--border-default); border-radius:var(--radius-xl); overflow:hidden; box-shadow:var(--shadow-lg); }
    .login-topo { background:var(--surface-sidebar); padding:34px 36px 26px; text-align:center; border-bottom:3px solid var(--marcador); }
    .login-logo { position:relative; width:64px; height:64px; background:var(--marcador); border-radius:var(--radius-lg); display:grid; place-items:center; font:800 28px/1 var(--font-display); color:#17231e; margin:0 auto 14px; }
    .login-logo::after { content:''; position:absolute; left:13px; top:7px; bottom:7px; width:2px; background:var(--margem); opacity:.75; border-radius:2px; }
    .login-topo h1 { color:#eef5f0; font:700 var(--text-lg) var(--font-display); margin:0 0 4px; }
    .login-topo p  { color:rgba(230,240,234,.7); font-size:var(--text-sm); margin:0; }
    .login-corpo { padding:var(--space-8) var(--space-6); }
    .campo-grupo { margin-bottom:var(--space-4); }
    .btn-entrar { width:100%; background:var(--quadro); color:var(--text-on-primary); border:none; padding:var(--space-4); border-radius:var(--radius-md); font:700 var(--text-md) var(--font-family); cursor:pointer; transition:background .2s; }
    .btn-entrar:hover { background:var(--quadro-forte); }
    .link-voltar { text-align:center; margin-top:var(--space-4); font-size:var(--text-sm); }
    .link-voltar a { color:rgba(230,240,234,.75); text-decoration:none; }
    .link-voltar a:hover { color:#fff; }
    .info-box { background:rgba(255,255,255,.08); border-radius:var(--radius-sm); padding:10px var(--space-4); margin-top:var(--space-4); font-size:var(--text-xs); color:rgba(230,240,234,.7); text-align:center; }
    .olho-btn { position:absolute; right:var(--space-3); top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-secondary); font-size:var(--text-md); }
  </style>
</head>
<body class="fundo-pauta">

<div class="login-envolvente">
  <div class="login-card">

    <!-- Topo -->
    <div class="login-topo">
      <div class="login-logo">R</div>
      <h1><?= APP_NOME ?></h1>
      <p><?= APP_TITULO ?></p>
    </div>

    <!-- Corpo -->
    <div class="login-corpo">
      <?php if ($erro): ?>
      <div class="alerta alerta-erro mb-3">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:15px;flex-shrink:0"></i>
        <span><?= Sanitizar::html($erro) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <?= Sessao::campoCsrf() ?>

        <div class="campo-grupo">
          <label class="rotulo-campo"><i class="bi bi-envelope me-1"></i> Email Institucional</label>
          <input type="email" name="email" class="campo-input"
                 value="<?= Sanitizar::html($_POST['email'] ?? '') ?>"
                 placeholder="utilizador@RegNota.com" autofocus required>
        </div>

        <div class="campo-grupo">
          <label class="rotulo-campo"><i class="bi bi-lock me-1"></i> Senha</label>
          <div style="position:relative">
            <input type="password" name="senha" id="campoSenha" class="campo-input"
                   placeholder="••••••••" required style="padding-right:42px">
            <button type="button" onclick="alternarSenha()" class="olho-btn">
              <i class="bi bi-eye" id="iconeOlho"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-entrar">
          <i class="bi bi-box-arrow-in-right me-2"></i>Entrar no Sistema
        </button>
      </form>
    </div>
  </div>

  <div class="link-voltar">
    <a href="<?= Url::publico('index.php') ?>">
      <i class="bi bi-arrow-left me-1"></i>Portal do Estudante
    </a>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function alternarSenha() {
  var c = document.getElementById('campoSenha');
  var i = document.getElementById('iconeOlho');
  if (c.type === 'password') { c.type = 'text';     i.className = 'bi bi-eye-slash'; }
  else                        { c.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>
<?php require APP_DIR . '/views/shared/tema_publico.php'; ?>
</body>
</html>
