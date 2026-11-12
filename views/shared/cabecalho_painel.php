<?php
/**
 * views/shared/cabecalho_painel.php
 * Layout parcial reutilizável: Sidebar + Topbar
 *
 * Variáveis esperadas pelo ficheiro que inclui este parcial:
 *   $tituloPagina string – Título da página actual
 *   $paginaActiva string – Identificador da entrada activa no menu
 *   $menuLateral  array  – Lista de itens do menu lateral
 */

use App\Helpers\Sessao;
use App\Helpers\Url;
use App\Helpers\Etiqueta;
use App\Helpers\Sanitizar;

$utilizador = Sessao::utilizador();
$inicial    = strtoupper(mb_substr($utilizador['nome'], 0, 1));
$flash      = Sessao::obterFlash();

// Mapa de chips de perfil
$chipPerfil = match($utilizador['perfil']) {
    'super_admin' => ['classe' => 'chip-super-admin', 'icone' => 'bi-shield-fill-check'],
    'coordenador' => ['classe' => 'chip-coordenador', 'icone' => 'bi-person-workspace'],
    'professor'   => ['classe' => 'chip-professor',   'icone' => 'bi-person-badge'],
    default       => ['classe' => '', 'icone' => 'bi-person'],
};
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= Sanitizar::html($tituloPagina ?? 'Painel') ?> | <?= APP_NOME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="<?= \App\Helpers\Url::asset('css/regnotas.css') ?>">
  <link rel="stylesheet" type="text/css" href="<?= \App\Helpers\Url::asset('css/painel.css') ?>">
</head>
<body>

<!-- Skip to content (acessibilidade teclado) -->
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#0A172A;color:#fff;padding:8px 16px;z-index:9999;transition:top .2s;font-family:var(--font-family);font-weight:600;font-size:.85rem;text-decoration:none;border-radius:0 0 6px 0">Saltar para conteúdo</a>

<!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar" role="complementary" aria-label="Menu de navegação">

  <!-- Marca -->
  <div class="sidebar-marca">
    <div class="sidebar-logo">R</div>
    <div class="sidebar-nome"><?= APP_NOME ?></div>
    <div class="sidebar-desc">v<?= APP_VERSAO ?> &middot; <?= Etiqueta::perfil($utilizador['perfil']) ?></div>
  </div>

  <!-- Navegação dinâmica (definida em cada painel) -->
  <nav class="sidebar-nav" aria-label="Seções principais">
    <?php foreach ($menuLateral ?? [] as $secao => $itens): ?>
      <div class="nav-secao"><?= Sanitizar::html($secao) ?></div>
      <?php foreach ($itens as $item): ?>
        <a href="<?= Sanitizar::html($item['url']) ?>"
           class="nav-item <?= ($paginaActiva ?? '') === $item['id'] ? 'activo' : '' ?>">
          <i class="<?= Sanitizar::html($item['icone']) ?> nav-icone"></i>
          <?= Sanitizar::html($item['titulo']) ?>
          <?php if (!empty($item['badge'])): ?>
          <span class="nav-badge"><?= (int)$item['badge'] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <!-- Rodapé -->
  <div class="sidebar-rodape">
    <div class="sidebar-utilizador">
      <div class="sidebar-avatar"><?= $inicial ?></div>
      <div>
        <div class="sidebar-nome-u"><?= Sanitizar::html($utilizador['nome']) ?></div>
        <div class="sidebar-perfil"><?= Etiqueta::perfil($utilizador['perfil']) ?></div>
      </div>
    </div>
    <a href="<?= Url::publico('alterar-senha.php') ?>" class="sidebar-sair" style="background:rgba(255,255,255,0.05);margin-bottom:4px;border-radius:8px">
      <i class="bi bi-key"></i> Alterar Senha
    </a>
    <a href="<?= Url::publico('logout.php') ?>" class="sidebar-sair">
      <i class="bi bi-box-arrow-left"></i> Terminar Sessão
    </a>
  </div>

</aside>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="fecharSidebar()"></div>

<!-- ══ WRAPPER PRINCIPAL ═════════════════════════════════════ -->
<div class="painel-wrapper">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-esquerda">
      <button class="btn-hamburger" onclick="alternarSidebar()" aria-label="Abrir/fechar menu lateral" aria-expanded="false" aria-controls="sidebar" id="btnSidebar">
        <i class="bi bi-list"></i>
      </button>
      <span class="topbar-titulo"><?= Sanitizar::html($tituloPagina ?? '') ?></span>
    </div>
    <div class="topbar-direita">
      <button onclick="toggleTheme()" aria-label="Alternar modo escuro ou claro" style="background:none; border:none; cursor:pointer; margin-right:10px; padding:6px; border-radius:6px;" title="Alternar tema">
          <i class="bi bi-moon-stars" id="themeIcon"></i>
      </button>
      <span class="chip-perfil <?= $chipPerfil['classe'] ?>">
        <i class="bi <?= $chipPerfil['icone'] ?>"></i>
        <?= Etiqueta::perfil($utilizador['perfil']) ?>
      </span>
    </div>
  </header>

  <!-- Área de conteúdo -->
  <main class="painel-conteudo" id="main-content">

    <!-- Flash message -->
    <?php if ($flash): ?>
    <div class="alerta alerta-<?= $flash['tipo'] === 'sucesso' ? 'sucesso' : ($flash['tipo'] === 'erro' ? 'erro' : 'info') ?> fade-in mb-4" id="flashMsg">
      <i class="bi <?= $flash['tipo'] === 'sucesso' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>" style="font-size:16px;flex-shrink:0"></i>
      <span><?= Sanitizar::html($flash['mensagem']) ?></span>
      <button onclick="this.parentElement.remove()" aria-label="Fechar notificação" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:18px;color:inherit;line-height:1" title="Fechar">&times;</button>
    </div>
    <?php endif; ?>
