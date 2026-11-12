<?php
require_once __DIR__ . '/../app/bootstrap.php';
\App\Middleware\Autenticacao::exigirAdmin(); // Script de manutenção: só super_admin


use App\Models\CursoModelo;
use App\Models\GradeCurricularModelo;

echo "Iniciando cadastro da grelha curricular de Ciências da Computação...
";

$cursoModelo = new CursoModelo();
$gradeModelo = new GradeCurricularModelo();

// 1. Localizar o curso "CIÊNCIAS DA COMPUTAÇÃO"
$curso = $cursoModelo->consultarUm("SELECT id FROM cursos WHERE nome = ?", ['CIÊNCIAS DA COMPUTAÇÃO']);

if (!$curso) {
    echo "Erro: Curso 'CIÊNCIAS DA COMPUTAÇÃO' não encontrado.
";
    exit(1);
}

$cursoId = (int)$curso['id'];
echo "Curso localizado: CIÊNCIAS DA COMPUTAÇÃO (ID: $cursoId)
";

// 2. Lista de disciplinas fornecidas
$disciplinas = [
    // 1º Ano - 1º Semestre
    [1, 1, 'Algoritmo e Programação'],
    [1, 1, 'Matemática I'],
    [1, 1, 'Geometria Analítica e Vectores'],
    [1, 1, 'Introdução às Ciências da Computação'],
    [1, 1, 'Língua Portuguesa I'],
    [1, 1, 'Inglês I'],
    [1, 1, 'Metodologia de Investigação Científica'],
    [1, 1, 'Tecnologia de Programação Web I'],

    // 1º Ano - 2º Semestre
    [1, 2, 'Programação II'],
    [1, 2, 'Matemática II'],
    [1, 2, 'Álgebra Linear'],
    [1, 2, 'Matemática Discreta'],
    [1, 2, 'Língua Portuguesa II'],
    [1, 2, 'Inglês II'],
    [1, 2, 'Fundamentos de Mecânica Clássica'],
    [1, 2, 'Tecnologia de Programação Web II'],

    // 2º Ano - 1º Semestre (3º Semestre)
    [2, 1, 'Matemática III'],
    [2, 1, 'Programação II'],
    [2, 1, 'Probabilidade e Estatística Aplicada à Computação'],
    [2, 1, 'Fundamentos de Electricidade e Magnetismo'],
    [2, 1, 'Estrutura de Dados I'],
    [2, 1, 'Arquitectura e Organização de Computadores I'],

    // 2º Ano - 2º Semestre (4º Semestre)
    [2, 2, 'Cálculo Numérico Computacional'],
    [2, 2, 'Lógica Aplicada à Computação'],
    [2, 2, 'Arquitectura e Organização de Computadores II'],
    [2, 2, 'Teoria e Métodos Gráficos'],
    [2, 2, 'Estrutura de Dados II'],
    [2, 2, 'Sistemas Lógicos Digitais'],

    // 3º Ano - 1º Semestre (5º Semestre)
    [3, 1, 'Sistema Operativo'],
    [3, 1, 'Redes de Computadores I'],
    [3, 1, 'Introdução à Inteligência Artificial'],
    [3, 1, 'Engenharia de Software I'],
    [3, 1, 'Linguagens Formais e Autómatos'],
    [3, 1, 'Opção'],
    [3, 1, 'Bases de Dados Avançadas I'],

    // 3º Ano - 2º Semestre (6º Semestre)
    [3, 2, 'Construção de Compiladores'],
    [3, 2, 'Redes de Computadores II'],
    [3, 2, 'Análise de Algoritmos'],
    [3, 2, 'Engenharia de Software II'],
    [3, 2, 'Bases de Dados Avançadas II'],
    [3, 2, 'Introdução à Gestão de Software'],
    [3, 2, 'Programação Paralela e Distribuída'],

    // 4º Ano - 1º Semestre (7º Semestre)
    [4, 1, 'Teorias de Linguagens'],
    [4, 1, 'Projecto de Software I'],
    [4, 1, 'Auditoria Computacional I'],
    [4, 1, 'Computação Gráfica'],
    [4, 1, 'Arquitectura e Padrões de Software'],

    // 4º Ano - 2º Semestre (8º Semestre)
    [4, 2, 'Computação Móvel'],
    [4, 2, 'Teste e Qualidade de Software'],
    [4, 2, 'Metodologia da Investigação Tecnológica'],
    [4, 2, 'Projecto de Software II'],
    [4, 2, 'Interacção Humano-Computador'],

    // 5º Ano - 1º Semestre (9º Semestre)
    [5, 1, 'Sistemas de Informação'],
    [5, 1, 'Segurança Computacional'],
    [5, 1, 'Empreendedorismo'],
    [5, 1, 'Gestão e Organização Empresarial'],
    [5, 1, 'Seminários de Preparação Trabalho de Fim de Curso'],

    // 5º Ano - 2º Semestre (10º Semestre)
    [5, 2, 'Defesa de Trabalho de Fim de Curso'],
];

foreach ($disciplinas as $item) {
    [$ano, $semestre, $nome] = $item;
    
    // Verificar se a disciplina já existe na grelha para evitar duplicados
    $existe = $gradeModelo->consultarUm(
        "SELECT id FROM grade_curricular WHERE curso_id = ? AND ano_nivel = ? AND semestre_num = ? AND nome = ?", 
        [$cursoId, $ano, $semestre, $nome]
    );

    if ($existe) {
        echo "Disciplina '$nome' já existe. Pulando...
";
    } else {
        $gradeModelo->criar([
            'curso_id'     => $cursoId,
            'ano_nivel'    => $ano,
            'semestre_num' => $semestre,
            'nome'         => $nome,
            'codigo'       => null,
            'ativo'        => 1,
        ]);
        echo "Disciplina '$nome' cadastrada.
";
    }
}

echo "
Cadastro da grelha curricular concluído com sucesso!
";
