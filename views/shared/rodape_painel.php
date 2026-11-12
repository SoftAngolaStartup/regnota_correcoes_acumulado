    </main><!-- /painel-conteudo -->
</div><!-- /painel-wrapper -->

<!-- Rodapé -->
<footer style="padding: 20px; text-align: center; color: var(--texto-muted); font-size: 12px; border-top: 1px solid var(--borda);">
    &copy; <?= date('Y') ?> <?= APP_NOME ?> &middot; Versão <?= APP_VERSAO ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Carregar preferência ao iniciar
function getPreferredTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) return savedTheme;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}
const theme = getPreferredTheme();
document.documentElement.setAttribute('data-theme', theme);

/* ── Sidebar mobile ─────────────────────────────────────── */
function alternarSidebar() {
  document.getElementById('sidebar').classList.toggle('aberta');
  document.getElementById('sidebarOverlay').classList.toggle('visivel');
}
function fecharSidebar() {
  document.getElementById('sidebar').classList.remove('aberta');
  document.getElementById('sidebarOverlay').classList.remove('visivel');
}

/* ── Gestão de Tema (Dark/Light) ───────────────────────── */
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    document.getElementById('themeIcon').className = newTheme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
}
document.addEventListener('DOMContentLoaded', () => {
    const theme = getPreferredTheme();
    document.getElementById('themeIcon').className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
});

/* ── Auto-fechar flash após 5 segundos ──────────────────── */
(function() {
  var msg = document.getElementById('flashMsg');
  if (msg) {
    setTimeout(function() {
      msg.style.transition = 'opacity .4s';
      msg.style.opacity    = '0';
      setTimeout(function() { if (msg.parentNode) msg.remove(); }, 400);
    }, 5000);
  }
})();

/* ── Confirmar eliminação ───────────────────────────────── */
function confirmarEliminar(mensagem) {
  return confirm(mensagem || 'Tem a certeza que pretende eliminar este registo?\nEsta acção não pode ser revertida.');
}

/* ── Submeter form com confirmação ─────────────────────── */
function submeterComConfirmacao(formId, mensagem) {
  if (confirmarEliminar(mensagem)) {
    document.getElementById(formId).submit();
  }
}
</script>
</body>
</html>
