<?php
/**
 * public/admin/api_professores.php
 * API Endpoint para gestão de associações de professores
 */
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Helpers\{Sessao, Sanitizar, Auditoria};

// Garantir que a resposta seja sempre JSON e sem erros de PHP vazados
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

if (!Sessao::autenticado() || !Sessao::temPerfil('super_admin')) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

switch ($acao) {
    case 'set_selected_prof':
        $profId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($profId) {
            $_SESSION['selected_prof_id'] = $profId;
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'ID de professor inválido.']);
        }
        break;

    case 'listar_disciplinas_turma':
        $turmaId = (int)($_GET['turma_id'] ?? $_POST['turma_id'] ?? 0);
        $profId = (int)($_SESSION['selected_prof_id'] ?? 0);
        
        if (!$turmaId) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Turma não especificada.']);
            exit;
        }

        $dm = new \App\Models\DisciplinaModelo();
        $disciplinas = $dm->listarTodas(['turma_id' => $turmaId]);
        
        $resultado = [];
        foreach ($disciplinas as $d) {
            $resultado[] = [
                'id' => $d['id'],
                'nome' => $d['nome'],
                'codigo' => $d['codigo'],
                'associado' => ($d['professor_id'] == $profId)
            ];
        }
        echo json_encode(['sucesso' => true, 'dados' => $resultado]);
        break;

    case 'associar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
            exit;
        }
        
        if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada (CSRF).']);
            exit;
        }

        $profId = (int)($_POST['professor_id'] ?? 0);
        $discId = (int)($_POST['disciplina_id'] ?? 0);
        
        if ($profId && $discId) {
            $dm = new \App\Models\DisciplinaModelo();
            if ($dm->atribuirProfessor($discId, $profId)) {
                Auditoria::registar('associar_professor', 'disciplinas', $discId, "Professor $profId associado", 0);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Professor associado com sucesso!']);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao associar no banco de dados.']);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos.']);
        }
        break;

    case 'desassociar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
            exit;
        }

        if (!Sessao::verificarCsrf($_POST['_token'] ?? '')) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada (CSRF).']);
            exit;
        }

        $discId = (int)($_POST['disciplina_id'] ?? 0);
        if ($discId) {
            $dm = new \App\Models\DisciplinaModelo();
            if ($dm->executar("UPDATE disciplinas SET professor_id = NULL WHERE id = ?", [$discId])) {
                Auditoria::registar('desassociar_professor', 'disciplinas', $discId, "Professor removido", 0);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Professor removido com sucesso!']);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao remover no banco de dados.']);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'ID de disciplina inválido.']);
        }
        break;

    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não reconhecida.']);
        break;
}
exit;
