<?php
/**
 * public/pesquisar.php
 * API AJAX – Pesquisa Pública de Ocorrências
 *
 * Chamado pelo portal do estudante via fetch().
 * Retorna JSON com dados limitados (ticket, estado, datas, observação oficial).
 * NÃO expõe dados internos (nota_interna, ip_origem, etc.)
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Helpers\Sanitizar;
use App\Models\OcorrenciaModelo;

// Cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// Apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

$ticket = Sanitizar::texto($_GET['ticket'] ?? '');
$nome   = Sanitizar::texto($_GET['q']      ?? '');

// Validação mínima
if (!$ticket && strlen($nome) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $om = new OcorrenciaModelo();

    if ($ticket) {
        // Pesquisa exacta por ticket
        $ocorr = $om->obterPorTicket($ticket);
        $dados = $ocorr ? [$ocorr] : [];
    } else {
        // Pesquisa por nome (limitado a 20 resultados)
        $dados = $om->pesquisarEstudante($nome);
    }

    // Garantir que apenas campos públicos são enviados
    $dadosPublicos = array_map(function (array $o): array {
        return [
            'id'                => $o['id'],
            'ticket'            => $o['ticket'],
            'estudante_nome'    => $o['estudante_nome'],
            'estado'            => $o['estado'],
            'criado_em'         => $o['criado_em'],
            'atualizado_em'     => $o['atualizado_em'],
            'observacao_oficial'=> $o['observacao_oficial'] ?? null,
        ];
    }, $dados);

    echo json_encode($dadosPublicos);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno. Tente novamente.']);
}
