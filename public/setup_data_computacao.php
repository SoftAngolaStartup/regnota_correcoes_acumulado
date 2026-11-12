<?php
require_once __DIR__ . '/../app/bootstrap.php';
\App\Middleware\Autenticacao::exigirAdmin(); // Script de manutenção: só super_admin


use App\Models\{CursoModelo, TurmaModelo, DisciplinaModelo, ProfessorModelo, GradeCurricularModelo, UsuarioModelo};

echo "Iniciando cadastro automático de professores e disciplinas...\n";

// Dados
$cursoNome = 'CIÊNCIAS DA COMPUTAÇÃO';
$cursoModelo = new CursoModelo();
$curso = $cursoModelo->consultarUm("SELECT id FROM cursos WHERE nome = ?", [$cursoNome]);
if (!$curso) { die("Erro: Curso não encontrado.\n"); }
$cursoId = (int)$curso['id'];

// Professores e Disciplinas - Mapeamento
$mapaDados = [
    101 => [
        'LÍNGUA PORTUGUESA II' => 'INOCÊNCIO FORTUNATO KIYANGA',
        'PROGRAMAÇÃO I' => 'JOSÉ SILVESTRE CORREIA',
        'TRABALHO AUTÓNOMO (TA)' => 'PROFESSOR PARA SEMINÁRIO V',
        'FUNDAMENTOS DE MECÂNICA CLÁSSICA' => 'ANTÓNIO ABRÃO ADOLFO',
        'TECNOLOGIA DE PROGRAMAÇÃO WEB II' => 'NATALÍCIO CAMOSSO MANGO',
        'MATEMÁTICA II' => 'DOMINGOS GOMES SELUMBO',
        'INGLÊS II' => 'FILIPE FERREIRA PESSOA',
        'MATEMÁTICA DISCRETA' => 'AMÉRICO LIOWENLI LUCAMBA',
        'ÁLGEBRA LINEAR' => 'AMÉRICO LIOWENLI LUCAMBA',
    ],
    301 => [
        'ENGENHARIA DE SOFTWARE II' => 'MANUEL TOMÁS MARTINS SEQUESEQUE',
        'IA' => 'TARCIANO MALVIO CAPOCO CACHIQUENGUE',
        'TRABALHO AUTÓNOMO' => 'PROFESSOR PARA SEMINÁRIO V',
        'EMPREENDEDORISMO II' => 'FILIPE FERREIRA PESSOA',
        'REDES DE COMPUTADORES II' => 'DYOVANY LEANDRO BALA DA SILVA',
        'ANÁLISES DE ALGORITMO' => 'JOUSON BARRETO JOSÉ',
        'PROGRAMAÇÃO PARALELA E DISTRIBUÍDA' => 'MANUEL TOMÁS MARTINS SEQUESEQUE',
        'BASES DE DADOS AVANÇADAS II' => 'JOSÉ SILVESTRE CORREIA',
        'CONSTRUÇÃO DE COMPILADORES' => 'JOSÉ SILVESTRE CORREIA',
    ]
];

$profModelo = new ProfessorModelo();
$discModelo = new DisciplinaModelo();
$gradeModelo = new GradeCurricularModelo();
$turmaModelo = new TurmaModelo();

$profsCriados = [];
$discsCriadas = 0;
$associacoes = 0;

foreach ($mapaDados as $turmaCodigo => $disciplinas) {
    // Localizar ou criar turma
    $turma = $turmaModelo->consultarUm("SELECT id FROM turmas WHERE curso_id = ? AND codigo = ?", [$cursoId, (string)$turmaCodigo]);
    if (!$turma) {
        echo "Turma $turmaCodigo não encontrada. Pulando...\n";
        continue;
    }
    $turmaId = (int)$turma['id'];

    foreach ($disciplinas as $nomeDisc => $nomeProf) {
        // 1. Garantir Professor
        $prof = $profModelo->consultarUm("SELECT id FROM professores WHERE nome = ?", [$nomeProf]);
        if (!$prof) {
            $profId = $profModelo->criar([
                'nome' => $nomeProf,
                'bi' => 'TMP' . time() . rand(100, 999),
                'telefone' => '000000000',
                'email_pessoal' => 'temp@regnota.com',
                'especialidade' => 'Não definida',
                'ativo' => 1
            ]);
            $profsCriados[] = $nomeProf;
        } else {
            $profId = (int)$prof['id'];
        }

        // 2. Localizar Grade (usando nome da disciplina)
        $grade = $gradeModelo->consultarUm("SELECT id, ano_nivel, semestre_num FROM grade_curricular WHERE curso_id = ? AND nome = ?", [$cursoId, $nomeDisc]);
        if (!$grade) {
            echo "Grade para disciplina '$nomeDisc' não encontrada.\n";
            continue;
        }

        // 3. Cadastrar Disciplina na Turma
        $discId = $discModelo->criar([
            'curso_id' => $cursoId,
            'turma_id' => $turmaId,
            'grade_id' => (int)$grade['id'],
            'professor_id' => $profId,
            'ano_nivel' => (int)$grade['ano_nivel'],
            'semestre_num' => (int)$grade['semestre_num'],
            'nome' => $nomeDisc
        ]);
        $discsCriadas++;
        
        // 4. Associar Professor
        $discModelo->atribuirProfessor($discId, $profId);
        $associacoes++;
    }
}

echo "\nRelatório:\n";
echo "- Professores inseridos: " . count(array_unique($profsCriados)) . "\n";
echo "- Disciplinas cadastradas: $discsCriadas\n";
echo "- Associações realizadas: $associacoes\n";
echo "Concluído!\n";
