<?php
/**
 * ============================================================
 * app/Helpers/Bd.php
 * Ligação à Base de Dados – Padrão Singleton com PDO
 * ============================================================
 *
 * Garante uma única ligação PDO por requisição.
 * Usa prepared statements para segurança total contra SQL Injection.
 */

namespace App\Helpers;

use PDO;
use PDOException;
use RuntimeException;

class Bd
{
    /** @var PDO|null Instância única da ligação */
    private static ?PDO $instancia = null;

    /** Impede instanciação directa */
    private function __construct() {}

    /**
     * Obtém a instância PDO, criando-a na primeira chamada.
     *
     * @throws RuntimeException Se a ligação falhar
     */
    public static function obter(): PDO
    {
        if (self::$instancia === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                BD_HOST, BD_PORTA, BD_NOME, BD_CHARSET
            );

            $opcoes = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND =>
                    "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instancia = new PDO(
                    $dsn, BD_USUARIO, BD_SENHA, $opcoes
                );
            } catch (PDOException $e) {
                if (APP_AMBIENTE === 'desenvolvimento') {
                    throw new RuntimeException(
                        'Erro de ligação à base de dados: ' . $e->getMessage()
                    );
                }
                throw new RuntimeException(
                    'Não foi possível ligar à base de dados. Contacte o administrador.'
                );
            }
        }

        return self::$instancia;
    }
}
