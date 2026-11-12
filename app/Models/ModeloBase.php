<?php
/**
 * ============================================================
 * app/Models/ModeloBase.php
 * Classe Base para todos os Modelos
 * ============================================================
 *
 * Fornece métodos reutilizáveis de acesso à BD via PDO.
 * Todos os modelos estendem esta classe.
 * Princípio DRY: lógica de BD centralizada aqui.
 */

namespace App\Models;

use App\Helpers\Bd;
use PDO;

abstract class ModeloBase
{
    /** @var PDO Instância da ligação à BD */
    protected PDO $bd;

    public function __construct()
    {
        $this->bd = Bd::obter();
    }

    /**
     * Executa uma query SELECT e retorna todos os resultados.
     *
     * @param string $sql    Query com placeholders (?)
     * @param array  $params Parâmetros a ligar
     */
    public function consultar(string $sql, array $params = []): array
    {
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Executa uma query SELECT e retorna apenas o primeiro resultado.
     * Retorna null se não encontrar.
     */
    public function consultarUm(string $sql, array $params = []): ?array
    {
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($params);
        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    /**
     * Executa uma query de escrita (INSERT, UPDATE, DELETE).
     * Retorna o número de linhas afectadas.
     */
    public function executar(string $sql, array $params = []): int
    {
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Retorna o ID do último INSERT.
     */
    public function ultimoId(): int
    {
        return (int)$this->bd->lastInsertId();
    }

    /**
     * Retorna um valor escalar (primeiro campo da primeira linha).
     */
    public function escalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Constrói cláusula WHERE dinamicamente a partir de filtros.
     *
     * Exemplo de uso:
     *   $filtros = ['curso_id = ?' => 1, 'ativo = ?' => 1];
     *   [$where, $params] = $this->construirWhere($filtros);
     *
     * @param array $condicoes ['expressao' => valor, ...]
     * @return array [clausula_string, params_array]
     */
    public function construirWhere(array $condicoes): array
    {
        if (empty($condicoes)) {
            return ['', []];
        }
        $clausulas = array_keys($condicoes);
        $params    = array_values($condicoes);
        return [
            'WHERE ' . implode(' AND ', $clausulas),
            $params,
        ];
    }
}
