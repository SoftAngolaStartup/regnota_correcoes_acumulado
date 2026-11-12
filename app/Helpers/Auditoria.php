<?php
/**
 * ============================================================
 * app/Helpers/Auditoria.php
 * Registo de Auditoria do Sistema
 * ============================================================
 *
 * Regista todas as acções importantes: login, logout,
 * criação, actualização, eliminação, mudança de estado, etc.
 */

namespace App\Helpers;

class Auditoria
{
    /**
     * Regista uma acção na tabela de auditoria.
     *
     * @param string   $acao       Nome da acção (ex: 'login', 'criar_ocorrencia')
     * @param string|null $tabela  Tabela afectada (opcional)
     * @param int|null $registoId  ID do registo afectado (opcional)
     * @param string|null $detalhe Detalhes adicionais (texto ou JSON)
     * @param int|null $usuarioId  ID do utilizador (null=sistema)
     */
    public static function registar(
        string  $acao,
        ?string $tabela    = null,
        ?int    $registoId = null,
        ?string $detalhe   = null,
        ?int    $usuarioId = null
    ): void {
        try {
            $pdo = Bd::obter();

            // Se não fornecido, tentar obter da sessão
            if ($usuarioId === null && isset($_SESSION['utilizador_id'])) {
                $usuarioId = (int)$_SESSION['utilizador_id'];
            }

            $stmt = $pdo->prepare("
                INSERT INTO auditoria
                    (usuario_id, acao, tabela, registo_id, detalhe, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $usuarioId ?: null,
                $acao,
                $tabela,
                $registoId,
                $detalhe,
                self::obterIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Auditoria nunca deve quebrar o fluxo principal
            @error_log('[Auditoria] Falha ao registar: ' . $e->getMessage());
        }
    }

    /**
     * Obtém o IP real do visitante (considera proxies comuns)
     */
    private static function obterIp(): string
    {
        $cabecalhos = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($cabecalhos as $cab) {
            if (!empty($_SERVER[$cab])) {
                $ip = trim(explode(',', $_SERVER[$cab])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
