<?php
require_once __DIR__ . '/../app/bootstrap.php';
\App\Middleware\Autenticacao::exigirAdmin(); // Script de manutenção: só super_admin


use App\Models\CursoModelo;
use App\Models\TurmaModelo;
use App\Models\AnoLetivoModelo;

echo "Iniciando cadastro de cursos e turmas (Modo Idempotente)...
";

$cursoModelo = new CursoModelo();
$turmaModelo = new TurmaModelo();
$anoModelo = new AnoLetivoModelo();

// 1. Obter o Ano Letivo Ativo
$anoAtivo = $anoModelo->obterAtivo();
if (!$anoAtivo) {
    echo "Erro: Nenhum ano letivo activo encontrado.
";
    exit(1);
}
$anoId = (int)$anoAtivo['id'];
echo "Ano Letivo Activo: {$anoAtivo['nome']} (ID: $anoId)
";

$faculdadeId = 1;

$cursosParaCadastrar = [
    "ENSINO PRIMÁRIO",
    "HISTÓRIA",
    "PSICOLOGIA",
    "ENFERMAGEM GERAL",
    "ENGENHARIA ELÉCTRICA",
    "ENGENHARIA CIVIL",
    "ARQUITECTURA E URBANISMO",
    "CIÊNCIAS ECONÓMICAS E EMPRESARIAIS",
    "CIÊNCIAS FARMACÊUTICAS",
    "ADMINISTRAÇÃO PÚBLICA E GESTÃO DE CIDADE",
    "GESTÃO DE RECURSOS HUMANOS",
    "CIÊNCIAS DA COMPUTAÇÃO",
    "ANALISES CLÍNICAS",
    "CIÊNCIAS FARMACÊUTICAS OPÇÃO EM ANALISES CLÍNICAS",
    "DIREITO",
    "MEDICINA DENTÁRIA",
    "PSICOLOGIA CRIMINAL",
    "PSICOLOGIA DA EDUCAÇÃO",
    "PSICOLOGIA DA SAÚDE",
];

foreach ($cursosParaCadastrar as $index => $nomeCurso) {
    $codigoCurso = "CUR" . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
    
    echo "Processando curso: $nomeCurso... ";
    
    // Verificar se o curso já existe por código ou nome
    $existe = $cursoModelo->consultarUm("SELECT id FROM cursos WHERE codigo = ? OR nome = ?", [$codigoCurso, $nomeCurso]);
    
    if ($existe) {
        $cursoId = (int)$existe['id'];
        echo "Já existe (ID: $cursoId). ";
    } else {
        $cursoId = $cursoModelo->criar($nomeCurso, $codigoCurso, $faculdadeId, 5);
        echo "Criado (ID: $cursoId). ";
    }

    // Turmas: 2 por ano (101, 102, ..., 501, 502)
    for ($nivel = 1; $nivel <= 5; $nivel++) {
        $codigos = [($nivel * 100) + 1, ($nivel * 100) + 2];
        foreach ($codigos as $codigoTurma) {
            $codigoStr = (string)$codigoTurma;
            
            // Verificar se a turma já existe para este curso e ano letivo
            $turmaExiste = $turmaModelo->consultarUm(
                "SELECT id FROM turmas WHERE curso_id = ? AND ano_letivo_id = ? AND codigo = ?", 
                [$cursoId, $anoId, $codigoStr]
            );
            
            if (!$turmaExiste) {
                $turmaModelo->criar($cursoId, $anoId, $nivel, $codigoStr);
            }
        }
    }
    echo "Turmas verificadas/cadastradas.
";
}

echo "
Processo concluído com sucesso!
";
