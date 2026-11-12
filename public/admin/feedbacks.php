<?php
/**
 * public/admin/feedbacks.php
 * Painel de Visualização de Feedbacks
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Formatar, Sanitizar};
use App\Models\OcorrenciaModelo;
use App\Middleware\Autenticacao;

Autenticacao::exigirAdmin();

$om  = new OcorrenciaModelo();
$feedbacks = $om->listarFeedbacks();

$tituloPagina = 'Feedbacks de Estudantes';
$paginaActiva = 'feedbacks';

// Menu lateral do painel admin
$menuLateral = require APP_DIR . '/app/Helpers/MenuAdmin.php';
require_once __DIR__ . '/../../views/shared/cabecalho_painel.php';
?>

<div class="glass-card">
    <div class="glass-header">
      <span class="card-conteudo-titulo"><i class="bi bi-chat-square-text"></i> Feedbacks Recebidos</span>
    </div>
    <div style="padding: 15px;">
      <?php if (empty($feedbacks)): ?>
        <div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhum feedback recebido ainda.</p></div>
      <?php else: ?>
        <table class="tabela-sistema">
            <thead>
                <tr>
                    <th>Ticket</th><th>Estudante</th><th>Disciplina</th><th>Feedback</th><th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks as $f): ?>
                <tr>
                    <td><?= Sanitizar::html($f['ticket']) ?></td>
                    <td><?= Sanitizar::html($f['estudante_nome']) ?></td>
                    <td><?= Sanitizar::html($f['disciplina_nome']) ?></td>
                    <td style="max-width: 300px; white-space: normal;"><?= Sanitizar::html($f['feedback']) ?></td>
                    <td class="td-muted"><?= Formatar::dataHora($f['feedback_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/shared/rodape_painel.php'; ?>
