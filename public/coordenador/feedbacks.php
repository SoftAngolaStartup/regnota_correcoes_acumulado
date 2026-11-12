<?php
/**
 * public/coordenador/feedbacks.php
 * Painel de Visualização de Feedbacks para Coordenadores
 */
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Helpers\{Sessao, Formatar, Sanitizar, Url};
use App\Models\{OcorrenciaModelo, CoordenadorModelo};
use App\Middleware\Autenticacao;

Autenticacao::exigirPerfil(['coordenador', 'super_admin']);

$uid   = Sessao::utilizador()['id'];
$coord = (new CoordenadorModelo())->obterPorUsuario($uid);
if (!$coord && \App\Helpers\Sessao::temPerfil('super_admin')) { \App\Helpers\Url::redirecionar(\App\Helpers\Url::admin('index.php')); }
if (!$coord) { Sessao::flash('erro','Sem curso associado.'); Url::redirecionar(Url::publico('login.php')); }

$cursoId   = $coord['curso_id'];
$om        = new OcorrenciaModelo();
$feedbacks = $om->listarFeedbacks($cursoId);

$tituloPagina = 'Feedbacks – ' . $coord['curso_nome'];
$paginaActiva = 'feedbacks';

// Menu do Coordenador
$menuLateral = [
    'Principal' => [
        ['id'=>'dashboard',  'titulo'=>'Dashboard',   'url'=>Url::coordenador('index.php'),       'icone'=>'bi-speedometer2'],
        ['id'=>'ocorrencias','titulo'=>'Ocorrências', 'url'=>Url::coordenador('ocorrencias.php'), 'icone'=>'bi-exclamation-triangle'],
    ],
    'Curso' => [
        ['id'=>'disciplinas','titulo'=>'Disciplinas', 'url'=>Url::coordenador('disciplinas.php'), 'icone'=>'bi-book'],
        ['id'=>'professores','titulo'=>'Professores', 'url'=>Url::coordenador('professores.php'), 'icone'=>'bi-person-badge'],
        ['id'=>'turmas',     'titulo'=>'Turmas',      'url'=>Url::coordenador('turmas.php'),      'icone'=>'bi-people'],
    ],
    'Relatórios' => [
        ['id'=>'feedbacks', 'titulo'=>'Feedbacks',    'url'=>Url::coordenador('feedbacks.php'),  'icone'=>'bi-chat-square-text'],
        ['id'=>'relatorios','titulo'=>'Exportar PDF','url'=>Url::coordenador('relatorios.php'),'icone'=>'bi-file-earmark-pdf'],
    ],
];

require_once APP_DIR . '/views/shared/cabecalho_painel.php';
?>

<div class="glass-card">
    <div class="glass-header">
      <span class="card-conteudo-titulo"><i class="bi bi-chat-square-text"></i> Feedbacks do Curso: <?= Sanitizar::html($coord['curso_nome']) ?></span>
    </div>
    <div style="padding: 15px;">
      <?php if (empty($feedbacks)): ?>
        <div class="sem-dados"><i class="bi bi-inbox"></i><p>Nenhum feedback recebido para este curso ainda.</p></div>
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

<?php require_once APP_DIR . '/views/shared/rodape_painel.php'; ?>
