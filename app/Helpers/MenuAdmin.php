<?php
/**
 * app/Helpers/MenuAdmin.php
 * Gera o menu lateral do painel de administração
 * Reutilizado por todas as páginas admin
 */

use App\Helpers\Url;

return [
    'Principal' => [
        ['id'=>'dashboard',  'titulo'=>'Dashboard',   'url'=>Url::admin('index.php'),       'icone'=>'bi-speedometer2'],
        ['id'=>'ocorrencias','titulo'=>'Ocorrências', 'url'=>Url::admin('ocorrencias.php'), 'icone'=>'bi-exclamation-triangle'],
    ],
    'Académico' => [
        ['id'=>'cursos',        'titulo'=>'Cursos',        'url'=>Url::admin('cursos.php'),        'icone'=>'bi-mortarboard'],
        ['id'=>'grade',         'titulo'=>'Grade Curricular','url'=>Url::admin('grade.php'),        'icone'=>'bi-grid-3x3-gap'],
        ['id'=>'disciplinas',   'titulo'=>'Disciplinas',   'url'=>Url::admin('disciplinas.php'),   'icone'=>'bi-book'],
        ['id'=>'turmas',        'titulo'=>'Turmas',        'url'=>Url::admin('turmas.php'),        'icone'=>'bi-people'],
        ['id'=>'professores',   'titulo'=>'Professores',   'url'=>Url::admin('professores.php'),   'icone'=>'bi-person-badge'],
        ['id'=>'coordenadores', 'titulo'=>'Coordenadores', 'url'=>Url::admin('coordenadores.php'), 'icone'=>'bi-person-workspace'],
    ],
    'Configuração' => [
        ['id'=>'anos',      'titulo'=>'Anos Letivos', 'url'=>Url::admin('anos.php'),      'icone'=>'bi-calendar3'],
        ['id'=>'semestres', 'titulo'=>'Semestres',    'url'=>Url::admin('semestres.php'), 'icone'=>'bi-calendar-range'],
        ['id'=>'usuarios',  'titulo'=>'Utilizadores', 'url'=>Url::admin('usuarios.php'),  'icone'=>'bi-shield-check'],
    ],
    'Relatórios' => [
        ['id'=>'relatorios', 'titulo'=>'Exportar PDF', 'url'=>Url::admin('relatorios.php'), 'icone'=>'bi-file-earmark-pdf'],
        ['id'=>'feedbacks',  'titulo'=>'Feedbacks',    'url'=>Url::admin('feedbacks.php'),  'icone'=>'bi-chat-square-text'],
        ['id'=>'auditoria',  'titulo'=>'Auditoria',    'url'=>Url::admin('auditoria.php'),  'icone'=>'bi-journal-text'],
    ],
    'Portal' => [
        ['id'=>'portal', 'titulo'=>'Portal Estudante', 'url'=>Url::publico('index.php'), 'icone'=>'bi-box-arrow-up-right'],
    ],
];
