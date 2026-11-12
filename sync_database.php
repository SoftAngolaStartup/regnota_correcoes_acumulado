<?php
/**
 * sync_database.php
 * Script de Manutenção: Sincronização de Referências Académicas
 * 
 * Garante que todas as turmas existentes tenham as disciplinas correspondentes
 * instanciadas com base na Grade Curricular.
 */

require_once __DIR__ . '/app/bootstrap.php';
if (PHP_SAPI !== 'cli') {
    \App\Middleware\Autenticacao::exigirAdmin(); // Script de manutenção: só super_admin
}

use App\Models\{TurmaModelo, GradeCurricularModelo, DisciplinaModelo, CursoModelo};

echo "--- Iniciando Sincronização de Referências ---
";

$tm = new TurmaModelo();
$gm = new GradeCurricularModelo();
$dm = new DisciplinaModelo();
$cm = new CursoModelo();

// 1. Validar se existem cursos e grade
$cursos = $cm->listarTodos();
if (empty($cursos)) {
    die("Erro: Nenhum curso encontrado no sistema. Cadastre os cursos e a grade primeiro.
");
}

// 2. Iterar por todas as turmas para garantir que têm as suas disciplinas
$turmas = $tm->listarTodas();
echo "Analisando " . count($turmas) . " turmas...
";

$totalCriadas = 0;
$totalSincronizadas = 0;

foreach ($turmas as $turma) {
    $turmaId   = $turma['id'];
    $cursoId   = $turma['curso_id'];
    $anoNivel  = $turma['ano_nivel'];
    
    // Como as turmas antigas podem não ter semestre definido na tabela 'turmas' 
    // (não existe coluna semestre_num em turmas no schema.sql, apenas em disciplinas),
    // vamos sincronizar AMBOS os semestres da grade para cada turma existente
    // para garantir que nada fique de fora, a menos que a lógica de negócio 
    // determine que uma turma é apenas de um semestre.
    
    // Buscar a grade para este curso e nível (ambos os semestres)
    $grade = $gm->listarTodas([
        'curso_id'  => $cursoId,
        'ano_nivel' => $anoNivel,
        'ativo'     => 1
    ]);

    if (empty($grade)) {
        echo "Aviso: Turma {$turma['codigo']} (Curso ID $cursoId, Nível $anoNivel) não tem grade definida.
";
        continue;
    }

    foreach ($grade as $item) {
        // Verificar se esta disciplina da grade já existe para esta turma
        // Precisamos checar se existe um registro em 'disciplinas' que ligue esta 'turma_id' a esta 'grade_id'
        
        // Usamos uma query manual via ModeloBase para checar a existência exata da ligação
        $existe = $dm->escalar("
            SELECT COUNT(*) FROM disciplinas 
            WHERE turma_id = ? AND grade_id = ?
        ", [$turmaId, $item['id']]);

        if (!$existe) {
            // Criar a instância da disciplina para a turma
            $dm->criar([
                'curso_id'     => $cursoId,
                'turma_id'     => $turmaId,
                'grade_id'     => $item['id'],
                'professor_id' => null, // Fica para associação posterior
                'ano_nivel'    => $item['ano_nivel'],
                'semestre_num' => $item['semestre_num'],
            ]);
            $totalCriadas++;
        } else {
            $totalSincronizadas++;
        }
    }
}

echo "
--- Sincronização Concluída ---
";
echo "Disciplinas já existentes: $totalSincronizadas
";
echo "Novas disciplinas instanciadas: $totalCriadas
";
echo "Total de referências validadas: " . ($totalCriadas + $totalSincronizadas) . "
";
