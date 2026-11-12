<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Models\OcorrenciaModelo;
use App\Helpers\Sanitizar;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!\App\Helpers\Sessao::verificarCsrf($_POST['_token'] ?? '')) {
        echo json_encode(['sucesso' => false, 'erro' => 'Token CSRF inválido']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $feedback = Sanitizar::texto($_POST['feedback'] ?? '');

    if (!$id || !$feedback) {
        echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
        exit;
    }

    try {
        $om = new OcorrenciaModelo();
        $resultado = $om->registarFeedback($id, $feedback);
        
        if ($resultado) {
            echo json_encode(['sucesso' => true]);
        } else {
            error_log("Falha na execução do SQL para feedback ID=$id");
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao gravar']);
        }
    } catch (\Exception $e) {
        error_log("Erro na API de Feedback: " . $e->getMessage());
        echo json_encode(['sucesso' => false, 'erro' => 'Erro interno. Tente novamente.']);
    }
    exit;
}
echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
