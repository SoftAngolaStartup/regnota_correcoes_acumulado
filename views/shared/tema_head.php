<?php
/**
 * views/shared/tema_head.php
 * Script inline para detecção e aplicação prévia do tema (claro/escuro)
 * evitando FOUC (Flash of Unstyled Content).
 */
?>
<script>
(function() {
    try {
        var tema = localStorage.getItem('regnotas_tema') || 'light';
        document.documentElement.setAttribute('data-theme', tema);
    } catch (e) {}
})();
</script>
