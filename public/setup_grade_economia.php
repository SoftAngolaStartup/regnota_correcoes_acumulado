<?php
require_once __DIR__ . '/../app/bootstrap.php';
\App\Middleware\Autenticacao::exigirAdmin(); // Script de manutenção: só super_admin


use App\Models\CursoModelo;
use App\Models\GradeCurricularModelo;

echo "Iniciando cadastro da grelha curricular de Ciências Económicas e Empresariais...
";

$cursoModelo = new CursoModelo();
$gradeModelo = new GradeCurricularModelo();

$curso = $cursoModelo->consultarUm("SELECT id FROM cursos WHERE nome = ?", ['CIÊNCIAS ECONÓMICAS E EMPRESARIAIS']);

if (!$curso) {
    echo "Erro: Curso 'CIÊNCIAS ECONÓMICAS E EMPRESARIAIS' não encontrado.
";
    exit(1);
}

$cursoId = (int)$curso['id'];
echo "Curso localizado: CIÊNCIAS ECONÓMICAS E EMPRESARIAIS (ID: $cursoId)
";

$disciplinas = [
    // 1º Ano - 1º Semestre
    [1, 1, 'Matemática I'],
    [1, 1, 'Contabilidade Geral I'],
    [1, 1, 'História Económica e Social de Angola'],
    [1, 1, 'Introdução à Gestão I'],
    [1, 1, 'Língua Portuguesa I'],
    [1, 1, 'Introdução à Economia I'],
    [1, 1, 'Metodologia de Investigação'],

    // 1º Ano - 2º Semestre
    [1, 2, 'Matemática II'],
    [1, 2, 'Contabilidade Geral II'],
    [1, 2, 'Introdução ao Direito'],
    [1, 2, 'Introdução à Gestão II'],
    [1, 2, 'Língua Portuguesa II'],
    [1, 2, 'Introdução à Economia II'],
    [1, 2, 'Sociologia'],

    // 2º Ano - 1º Semestre
    [2, 1, 'Cálculo Financeiro'],
    [2, 1, 'Estatística I'],
    [2, 1, 'Contabilidade Analítica I'],
    [2, 1, 'Microeconomia I'],
    [2, 1, 'Macroeconomia I'],
    [2, 1, 'Informática'],
    [2, 1, 'Comunicação em Finanças'],
    [2, 1, 'Plano Estratégico'],

    // 2º Ano - 2º Semestre
    [2, 2, 'Álgebra Linear'],
    [2, 2, 'Estatística II'],
    [2, 2, 'Contabilidade Analítica II'],
    [2, 2, 'Microeconomia II'],
    [2, 2, 'Macroeconomia II'],
    [2, 2, 'Demografia'],
    [2, 2, 'Gestão Estratégica de Custos e Formação de Preços'],
    [2, 2, 'Planificação Tributária'],

    // --- Variante A: Economia do Desenvolvimento e Políticas Públicas ---
    [3, 1, 'Econometria (Var A)'],
    [3, 1, 'Política Económica (Var A)'],
    [3, 1, 'História do Pensamento Económico (Var A)'],
    [3, 1, 'Economia do Desenvolvimento (Var A)'],
    [3, 1, 'Matemática Aplicada à Economia (Var A)'],
    [3, 1, 'Direito Económico (Var A)'],

    [3, 2, 'Métodos de Previsão (Var A)'],
    [3, 2, 'Contabilidade Nacional (Var A)'],
    [3, 2, 'Economia Monetária e Financeira (Var A)'],
    [3, 2, 'Finanças Públicas (Var A)'],
    [3, 2, 'Investigação Operacional (Var A)'],

    [4, 1, 'Economia Internacional (Var A)'],
    [4, 1, 'Economia Regional e Urbana (Var A)'],
    [4, 1, 'Economia Industrial e da Inovação (Var A)'],
    [4, 1, 'Economia dos Recursos Naturais e do Ambiente (Var A)'],
    [4, 1, 'Sociologia das Organizações (Var A)'],

    [4, 2, 'Direito Comercial (Var A)'],
    [4, 2, 'Economia de Materiais (Var A)'],
    [4, 2, 'Planejamento (Var A)'],
    [4, 2, 'Seminários de Elaboração de Monografias (Var A)'],

    // --- Variante B: Contabilidade, Auditoria e Fiscalidade ---
    [3, 1, 'Fiscalidade I (Var B)'],
    [3, 1, 'Contabilidade Geral III (Var B)'],
    [3, 1, 'Gestão Financeira I (Var B)'],
    [3, 1, 'Planeamento e Controlo de Gestão (Var B)'],
    [3, 1, 'Auditoria I (Var B)'],
    [3, 1, 'Auditoria e Revisão de Contas I (Var B)'],
    [3, 1, 'Estrutura e Base de Dados (Var B)'],
    [3, 1, 'Contabilidade de Seguros (Var B)'],
    [3, 1, 'Organização de Empresa (Var B)'],
    [3, 1, 'Mercado de Capital (Var B)'],

    [3, 2, 'Direito Fiscal e Tributário (Var B)'],
    [3, 2, 'Contabilidade Financeira (Var B)'],
    [3, 2, 'Gestão Financeira II (Var B)'],
    [3, 2, 'Métodos Quantitativos (Var B)'],
    [3, 2, 'Auditoria II (Var B)'],
    [3, 2, 'Gestão de Recursos Humanos (Var B)'],
    [3, 2, 'Auditoria e Revisão de Contas II (Var B)'],
    [3, 2, 'Direito do Trabalho (Var B)'],
    [3, 2, 'Direito Económico (Var B)'],
    [3, 2, 'Psicossociologia das Organizações (Var B)'],
    [3, 2, 'Contabilidade das Instituições de Crédito (Var B)'],
    [3, 2, 'Análise de Projectos de Investimentos (Var B)'],

    [4, 1, 'Fiscalidade II (Var B)'],
    [4, 1, 'Análise Financeira (Var B)'],
    [4, 2, 'Instrumentos de Derivados (Var B)'],

    // --- Variante C: Gestão de Empresas ---
    // Distribuindo as disciplinas de 3º ano entre 1º e 2º semestre
    [3, 1, 'Gestão Estratégica (Var C)'],
    [3, 1, 'Gestão Orçamental (Var C)'],
    [3, 1, 'Contabilidade Pública (Var C)'],
    [3, 1, 'Planeamento e Controlo de Gestão (Var C)'],
    [3, 1, 'Psicossociologia das Organizações (Var C)'],
    [3, 1, 'Gestão do Pessoal (Var C)'],

    [3, 2, 'Direito do Trabalho (Var C)'],
    [3, 2, 'Direito Comercial (Var C)'],
    [3, 2, 'Fiscalidade Internacional (Var C)'],
    [3, 2, 'Métodos de Previsão (Var C)'],
    [3, 2, 'Análise de Projectos de Investimentos (Var C)'],

    // Distribuindo as disciplinas de 4º ano
    [4, 1, 'Estrutura e Processos Organizacionais (Var C)'],
    [4, 1, 'Sistemas Informáticos para Gestão (Var C)'],
    [4, 1, 'Introdução à Gestão da Produção/Recursos Humanos/Marketing (Var C)'],

    [4, 2, 'Gestão de Canais de Vendas (Var C)'],
    [4, 2, 'Logística (Var C)'],
    [4, 2, 'Marketing de Serviços (Var C)'],
    [4, 2, 'Gestão das Operações (Var C)'],
];

foreach ($disciplinas as $item) {
    [$ano, $semestre, $nome] = $item;
    
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
