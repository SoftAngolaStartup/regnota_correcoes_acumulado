<?php
require_once __DIR__ . '/../app/bootstrap.php';
\App\Middleware\Autenticacao::exigirAdmin(); // Script de manutenção: só super_admin


use App\Models\CursoModelo;
use App\Models\GradeCurricularModelo;

echo "Iniciando cadastro das grelhas curriculares...
";

$cursoModelo = new CursoModelo();
$gradeModelo = new GradeCurricularModelo();

$cursosParaProcessar = [
    'ENSINO PRIMÁRIO' => [
        // 1º Ano
        [1, 1, 'Inglês I'], [1, 1, 'Informática Educativa'], [1, 1, 'História de Angola I'], [1, 1, 'Pedagogia I'], [1, 1, 'Língua Portuguesa I'], [1, 1, 'Educação Cívica e sua Metodologia'], [1, 1, 'Introdução à Especialidade'], [1, 1, 'Fundamentos da Matemática Básica I'], [1, 1, 'Optativa I'],
        [1, 2, 'Língua Portuguesa II'], [1, 2, 'Inglês II'], [1, 2, 'História de Angola II'], [1, 2, 'Pedagogia II'], [1, 2, 'Psicologia I'], [1, 2, 'Anatomia'], [1, 2, 'Fisiologia e Higiene Escolar'], [1, 2, 'Fundamentos da Matemática Básica II'], [1, 2, 'Optativa II'],
        // 2º Ano
        [2, 1, 'Matemática I'], [2, 1, 'Psicologia II'], [2, 1, 'Didáctica I'], [2, 1, 'Necessidades Educativas Especiais'], [2, 1, 'Optativa III'], [2, 1, 'Estágio I'], [2, 1, 'Inglês III'], [2, 1, 'Didáctica da Matemática I'],
        [2, 2, 'Didáctica II'], [2, 2, 'Geografia'], [2, 2, 'Biologia'], [2, 2, 'Matemática II'], [2, 2, 'Didáctica da Matemática I e II'], [2, 2, 'O Mundo em que Vivemos'], [2, 2, 'Estágio II'], [2, 2, 'Inglês IV'],
        // 3º Ano
        [3, 1, 'Fonética e Fonologia'], [3, 1, 'Ciências Naturais e sua Didáctica'], [3, 1, 'Geografia Física de Angola'], [3, 1, 'Optativa IV'], [3, 1, 'Estágio Supervisionado I'], [3, 1, 'Inglês V'],
        [3, 2, 'Metodologia da Investigação'], [3, 2, 'Educação Plástica e sua Metodologia'], [3, 2, 'Didáctica da Língua Portuguesa'], [3, 2, 'Didática da História de Angola'], [3, 2, 'Didática da Geografia'], [3, 2, 'Estágio Supervisionado II'], [3, 2, 'Inglês VI'],
        // 4º Ano
        [4, 1, 'História da Educação'], [4, 1, 'Estatística Paramétrica'], [4, 1, 'Educação Musical e sua Metodologia'], [4, 1, 'Apreciação das Artes'], [4, 1, 'Panorama Geral da Literatura'], [4, 1, 'Empreendedorismo I'], [4, 1, 'Estágio Supervisionado III'],
        [4, 2, 'Estatística não Paramétrica'], [4, 2, 'Literature Infantil'], [4, 2, 'Educação Laboral e sua Metodologia'], [4, 2, 'Comunicação e Expressão'], [4, 2, 'Trabalho de Curso Integrador'], [4, 2, 'Empreendedorismo II'], [4, 2, 'Estágio Supervisionado IV'],
        // 5º Ano
        [5, 1, 'Estágio Direccionado I'],
        [5, 2, 'Estágio Direccionado II'], [5, 2, 'Trabalho de Fim de Curso'], [5, 2, 'Psicologia da Linguagem'],
    ],
    'MEDICINA DENTÁRIA' => [
        // 1º Ano
        [1, 1, 'Anatomia I'], [1, 1, 'Microbiologia Geral'], [1, 1, 'Biologia Celular'], [1, 1, 'Bioquímica I'], [1, 1, 'Metodologia Científica'], [1, 1, 'Introdução à Odontologia'], [1, 1, 'Bioética e Legislação Odontológica'], [1, 1, 'Histologia e Embriologia Bucal'],
        [1, 2, 'Anatomia II (Cabeça e Pescoço)'], [1, 2, 'Microbiologia Bucal'], [1, 2, 'Materiais Dentários I'], [1, 2, 'Bioquímica II'], [1, 2, 'Escultura Dental'], [1, 2, 'Fisiologia I'], [1, 2, 'Informática'], [1, 2, 'Bioestatística'],
        // 2º Ano
        [2, 1, 'Anestesiologia'], [2, 1, 'Fisiologia II'], [2, 1, 'Cariologia'], [2, 1, 'Materiais Dentários II'], [2, 1, 'Patologia Geral'], [2, 1, 'Biossegurança'], [2, 1, 'Dentística I'], [2, 1, 'Prótese I'],
        [2, 2, 'Farmacologia'], [2, 2, 'Estomatologia'], [2, 2, 'Psicologia Aplicada à Odontologia'], [2, 2, 'Radiologia Odontológica e Imaginologia'], [2, 2, 'Patologia Bucal'], [2, 2, 'Biologia Molecular'], [2, 2, 'Dentística II'], [2, 2, 'Prótese II'],
        // 3º Ano
        [3, 1, 'Cirurgia e Traumatologia I'], [3, 1, 'Dentística III'], [3, 1, 'Endodontia I'], [3, 1, 'Odontologia Social e Preventiva'], [3, 1, 'Periodontia I'], [3, 1, 'Prótese III'], [3, 1, 'Inglês I'], [3, 1, 'Semiologia e Diagnóstico I'],
        [3, 2, 'Cirurgia e Traumatologia II'], [3, 2, 'Dentística IV'], [3, 2, 'Endodontia II'], [3, 2, 'Odontopediatria I'], [3, 2, 'Periodontia II'], [3, 2, 'Saúde Coletiva I'], [3, 2, 'Inglês II'], [3, 2, 'Semiologia e Diagnóstico II'],
        // 4º Ano
        [4, 1, 'Cirúrgia e Traumatologia III'], [4, 1, 'Implantodontia I'], [4, 1, 'Odontopediatria II'], [4, 1, 'Ortodontia I'], [4, 1, 'Língua Portuguesa I'], [4, 1, 'Saúde Coletiva II'], [4, 1, 'Estágio Supervisionado I - Clínica Integrada'], [4, 1, 'Seminário e Desenvolvimento de Projecto I'],
        [4, 2, 'Disfunção Temporomandibular'], [4, 2, 'Implantodontia II'], [4, 2, 'Odontopediatria III'], [4, 2, 'Ortodontia II'], [4, 2, 'Língua Portuguesa II'], [4, 2, 'Saúde Coletiva III'], [4, 2, 'Estágio Supervisionado II - Clínica Integrada'], [4, 2, 'Seminário e Desenvolvimento de Projecto II'],
        // 5º Ano
        [5, 1, 'Estágio Obrigatório'], [5, 1, 'Seminários Temáticos de Odontologia'], [5, 1, 'Administração Hospitalar e Equipamentos'], [5, 1, 'Empreendedorismo e Marketing'], [5, 1, 'Gestão de Recursos Humanos'],
        [5, 2, 'Estágio Obrigatório'], [5, 2, 'Elaboração e Defesa do Trabalho de Fim de Curso'],
    ],
];

foreach ($cursosParaProcessar as $nomeCurso => $disciplinas) {
    $curso = $cursoModelo->consultarUm("SELECT id FROM cursos WHERE nome = ?", [$nomeCurso]);

    if (!$curso) {
        echo "Erro: Curso '$nomeCurso' não encontrado. Pulando...
";
        continue;
    }

    $cursoId = (int)$curso['id'];
    echo "Processando curso: $nomeCurso (ID: $cursoId)...
";

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
}

echo "
Cadastro das grelhas curriculares concluído com sucesso!
";
