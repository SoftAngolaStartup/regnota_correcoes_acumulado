<?php
/**
 * ============================================================
 * app/Models/Modelos.php
 * Todos os Modelos de Domínio do Sistema Regnotas v3.0
 * ============================================================
 *
 * Cada classe representa uma entidade da base de dados.
 * Nenhuma query SQL existe fora deste ficheiro.
 * Segue princípios SOLID e Clean Architecture.
 */

namespace App\Models;

// ============================================================
// FaculdadeModelo
// ============================================================
class FaculdadeModelo extends ModeloBase
{
    public function listarTodas(): array
    {
        return $this->consultar("SELECT * FROM faculdades ORDER BY nome");
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("SELECT * FROM faculdades WHERE id = ?", [$id]);
    }
}

// ============================================================
// CursoModelo
// ============================================================
class CursoModelo extends ModeloBase
{
    /** Lista todos os cursos com dados da faculdade e coordenador */
    public function listarTodos(bool $apenasAtivos = false): array
    {
        $onde = $apenasAtivos ? 'WHERE c.ativo = 1' : '';
        return $this->consultar("
            SELECT c.*,
                   f.nome  AS faculdade_nome,
                   co.nome AS coordenador_nome,
                   co.id   AS coordenador_id
            FROM cursos c
            JOIN faculdades f      ON c.faculdade_id  = f.id
            LEFT JOIN coordenadores co ON co.curso_id = c.id
            $onde
            ORDER BY c.nome
        ");
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("
            SELECT c.*, f.nome AS faculdade_nome
            FROM cursos c
            JOIN faculdades f ON c.faculdade_id = f.id
            WHERE c.id = ?
        ", [$id]);
    }

    public function criar(string $nome, string $codigo, int $faculdadeId, int $duracaoAnos): int
    {
        $this->executar("
            INSERT INTO cursos (faculdade_id, codigo, nome, duracao_anos)
            VALUES (?, ?, ?, ?)
        ", [$faculdadeId, $codigo, $nome, $duracaoAnos]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, string $nome, string $codigo, int $duracaoAnos, int $ativo): bool
    {
        return (bool)$this->executar("
            UPDATE cursos SET nome=?, codigo=?, duracao_anos=?, ativo=? WHERE id=?
        ", [$nome, $codigo, $duracaoAnos, $ativo, $id]);
    }
}

// ============================================================
// AnoLetivoModelo
// ============================================================
class AnoLetivoModelo extends ModeloBase
{
    public function listarTodos(): array
    {
        return $this->consultar("SELECT * FROM anos_letivos ORDER BY nome DESC");
    }

    public function obterAtivo(): ?array
    {
        return $this->consultarUm("SELECT * FROM anos_letivos WHERE ativo = 1 LIMIT 1");
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("SELECT * FROM anos_letivos WHERE id = ?", [$id]);
    }

    public function criar(string $nome, int $ativo = 0): int
    {
        if ($ativo) {
            $this->executar("UPDATE anos_letivos SET ativo = 0");
        }
        $this->executar("INSERT INTO anos_letivos (nome, ativo) VALUES (?, ?)", [$nome, $ativo]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, string $nome, int $ativo): bool
    {
        if ($ativo) {
            $this->executar("UPDATE anos_letivos SET ativo = 0 WHERE id != ?", [$id]);
        }
        return (bool)$this->executar(
            "UPDATE anos_letivos SET nome=?, ativo=? WHERE id=?", [$nome, $ativo, $id]
        );
    }

    public function eliminar(int $id): bool
    {
        $dep = $this->escalar("SELECT COUNT(*) FROM semestres WHERE ano_letivo_id=?", [$id]);
        if ($dep > 0) return false;
        return (bool)$this->executar("DELETE FROM anos_letivos WHERE id=?", [$id]);
    }
}

// ============================================================
// SemestreModelo
// ============================================================
class SemestreModelo extends ModeloBase
{
    public function listarTodos(): array
    {
        return $this->consultar("
            SELECT s.*, a.nome AS ano_nome
            FROM semestres s
            JOIN anos_letivos a ON s.ano_letivo_id = a.id
            ORDER BY a.nome DESC, s.numero
        ");
    }

    public function listarPorAno(int $anoId): array
    {
        return $this->consultar("
            SELECT s.*, a.nome AS ano_nome
            FROM semestres s
            JOIN anos_letivos a ON s.ano_letivo_id = a.id
            WHERE s.ano_letivo_id = ?
            ORDER BY s.numero
        ", [$anoId]);
    }

    public function listarAtivos(): array
    {
        return $this->consultar("
            SELECT s.*, a.nome AS ano_nome
            FROM semestres s
            JOIN anos_letivos a ON s.ano_letivo_id = a.id
            WHERE s.ativo = 1
            ORDER BY a.nome DESC, s.numero
        ");
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("
            SELECT s.*, a.nome AS ano_nome
            FROM semestres s
            JOIN anos_letivos a ON s.ano_letivo_id = a.id
            WHERE s.id = ?
        ", [$id]);
    }

    public function criar(int $anoId, int $numero, string $nome, ?string $inicio, ?string $fim, int $ativo): int
    {
        $this->executar("
            INSERT INTO semestres (ano_letivo_id, numero, nome, data_inicio, data_fim, ativo)
            VALUES (?,?,?,?,?,?)
        ", [$anoId, $numero, $nome, $inicio ?: null, $fim ?: null, $ativo]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, string $nome, ?string $inicio, ?string $fim, int $ativo): bool
    {
        return (bool)$this->executar(
            "UPDATE semestres SET nome=?, data_inicio=?, data_fim=?, ativo=? WHERE id=?",
            [$nome, $inicio ?: null, $fim ?: null, $ativo, $id]
        );
    }

    public function eliminar(int $id): bool
    {
        $dep = $this->escalar("SELECT COUNT(*) FROM ocorrencias WHERE semestre_id=?", [$id]);
        if ($dep > 0) return false;
        return (bool)$this->executar("DELETE FROM semestres WHERE id=?", [$id]);
    }
}

// ============================================================
// TurmaModelo
// ============================================================
class TurmaModelo extends ModeloBase
{
    /**
     * Lista turmas com filtros opcionais
     */
    public function listarTodas(array $filtros = []): array
    {
        $onde = []; $params = [];

        if (!empty($filtros['curso_id'])) {
            $onde[] = 't.curso_id = ?'; $params[] = $filtros['curso_id'];
        }
        if (!empty($filtros['ano_letivo_id'])) {
            $onde[] = 't.ano_letivo_id = ?'; $params[] = $filtros['ano_letivo_id'];
        }
        if (!empty($filtros['ano_nivel'])) {
            $onde[] = 't.ano_nivel = ?'; $params[] = $filtros['ano_nivel'];
        }

        $clausula = $onde ? 'WHERE ' . implode(' AND ', $onde) : '';

        return $this->consultar("
            SELECT t.*,
                   c.nome AS curso_nome, c.codigo AS curso_codigo,
                   a.nome AS ano_nome
            FROM turmas t
            JOIN cursos c       ON t.curso_id      = c.id
            JOIN anos_letivos a ON t.ano_letivo_id = a.id
            $clausula
            ORDER BY t.ano_nivel, t.codigo
        ", $params);
    }

    public function listarDoAnoAtivo(int $cursoId = 0): array
    {
        $extra = $cursoId ? 'AND t.curso_id = ?' : '';
        $params = $cursoId ? [$cursoId] : [];
        return $this->consultar("
            SELECT t.*, c.nome AS curso_nome, a.nome AS ano_nome
            FROM turmas t
            JOIN cursos c       ON t.curso_id      = c.id
            JOIN anos_letivos a ON t.ano_letivo_id = a.id
            WHERE a.ativo = 1 AND t.ativo = 1 $extra
            ORDER BY t.ano_nivel, t.codigo
        ", $params);
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("
            SELECT t.*, c.nome AS curso_nome, a.nome AS ano_nome
            FROM turmas t
            JOIN cursos c       ON t.curso_id      = c.id
            JOIN anos_letivos a ON t.ano_letivo_id = a.id
            WHERE t.id = ?
        ", [$id]);
    }

    public function criar(int $cursoId, int $anoLetivoId, int $anoNivel, string $codigo): int
    {
        $this->executar("
            INSERT INTO turmas (curso_id, ano_letivo_id, ano_nivel, codigo, ativo)
            VALUES (?, ?, ?, ?, 1)
        ", [$cursoId, $anoLetivoId, $anoNivel, $codigo]);
        $turmaId = $this->ultimoId();
        $this->preCadastrarDisciplinas($turmaId, 1);
        return $turmaId;
    }

    /**
     * Geração automática de turmas para um curso/ano letivo.
     * Código padronizado: Nível*100 + sequência (ex: 101, 102, 201...)
     *
     * @param int $cursoId      ID do curso
     * @param int $anoLetivoId  ID do ano letivo
     * @param int $anoNivel     Nível (1 a 5)
     * @param int $quantidade   Quantidade de turmas a criar
     * @return int              Número de turmas criadas
     */
    public function gerarAutomaticamente(int $cursoId, int $anoLetivoId, int $anoNivel, int $semestreNum, int $quantidade): int
    {
        $criadas = 0;
        for ($i = 1; $i <= $quantidade; $i++) {
            $codigo = ($anoNivel * 100) + $i; // Ex: 1º Ano → 101, 102, 103...
            $codigoStr = (string)$codigo;

            // Verificar se já existe
            $existe = $this->escalar("
                SELECT COUNT(*) FROM turmas
                WHERE curso_id=? AND ano_letivo_id=? AND codigo=?
            ", [$cursoId, $anoLetivoId, $codigoStr]);

            if (!$existe) {
                $this->executar("
                    INSERT INTO turmas (curso_id, ano_letivo_id, ano_nivel, codigo)
                    VALUES (?,?,?,?)
                ", [$cursoId, $anoLetivoId, $anoNivel, $codigoStr]);
                $turmaId = $this->ultimoId();
                $this->preCadastrarDisciplinas($turmaId, $semestreNum);
                $criadas++;
            }
        }
        return $criadas;
    }

    /**
     * Cria automaticamente as instâncias de disciplinas para uma turma
     * com base na grade curricular do curso, nível e semestre.
     */
    public function preCadastrarDisciplinas(int $turmaId, int $semestreNum): void
    {
        // Obter dados da turma para saber o curso e nível
        $turma = $this->obterPorId($turmaId);
        if (!$turma) return;

        $cursoId = $turma['curso_id'];
        $anoNivel = $turma['ano_nivel'];

        // Buscar todas as matérias da grade para este curso, nível e semestre
        $grade = (new GradeCurricularModelo())->listarTodas([
            'curso_id' => $cursoId,
            'ano_nivel' => $anoNivel,
            'semestre_num' => $semestreNum,
            'ativo' => 1
        ]);

        $dm = new DisciplinaModelo();
        foreach ($grade as $item) {
            $dm->criar([
                'curso_id'     => $cursoId,
                'turma_id'     => $turmaId,
                'grade_id'     => $item['id'],
                'professor_id' => null,
                'ano_nivel'    => $item['ano_nivel'],
                'semestre_num' => $item['semestre_num'],
            ]);
        }
    }


    public function actualizar(int $id, int $ativo): bool
    {
        return (bool)$this->executar("UPDATE turmas SET ativo=? WHERE id=?", [$ativo, $id]);
    }

    public function eliminar(int $id): bool
    {
        $dep = $this->escalar("SELECT COUNT(*) FROM ocorrencias WHERE turma_id=?", [$id]);
        if ($dep > 0) return false;
        return (bool)$this->executar("DELETE FROM turmas WHERE id=?", [$id]);
    }
}

// ============================================================
// UsuarioModelo
// ============================================================
class UsuarioModelo extends ModeloBase
{
    public function listarTodos(): array
    {
        return $this->consultar("
            SELECT id, nome, email, perfil, ativo, primeiro_login,
                   ultimo_login, tentativas_login, criado_em
            FROM usuarios ORDER BY nome
        ");
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm(
            "SELECT * FROM usuarios WHERE id = ?", [$id]
        );
    }

    public function obterPorEmail(string $email): ?array
    {
        return $this->consultarUm(
            "SELECT * FROM usuarios WHERE email = ? AND ativo = 1", [$email]
        );
    }

    /**
     * Cria um utilizador com senha hasheada automaticamente.
     * Usado na criação automática de professor/coordenador.
     * Email formato: BI@RegNota.com | Senha inicial: RegNota@123
     */
    public function criarUsuario(string $nome, string $email, string $senha, string $perfil): int
    {
        $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->bd->prepare("
            INSERT INTO usuarios (nome, email, password, perfil, primeiro_login, senha_alterada)
            VALUES (?,?,?,?,1,0)
        ");
        $stmt->execute([$nome, $email, $hash, $perfil]);
        return (int)$this->bd->lastInsertId();
    }

    public function actualizarSenha(int $id, string $novaSenha): bool
    {
        $hash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
        return (bool)$this->executar("
            UPDATE usuarios
            SET password=?, primeiro_login=0, senha_alterada=1
            WHERE id=?
        ", [$hash, $id]);
    }

    public function registarLogin(int $id): void
    {
        $this->executar("
            UPDATE usuarios
            SET ultimo_login=NOW(), tentativas_login=0, bloqueado_ate=NULL
            WHERE id=?
        ", [$id]);
    }

    public function incrementarTentativas(int $id): void
    {
        $this->executar("
            UPDATE usuarios SET tentativas_login = tentativas_login + 1 WHERE id=?
        ", [$id]);

        // Verificar se deve bloquear
        $tentativas = (int)$this->escalar(
            "SELECT tentativas_login FROM usuarios WHERE id=?", [$id]
        );
        if ($tentativas >= MAX_TENTATIVAS_LOGIN) {
            $ate = date('Y-m-d H:i:s', time() + BLOQUEIO_MINUTOS * 60);
            $this->executar(
                "UPDATE usuarios SET bloqueado_ate=? WHERE id=?", [$ate, $id]
            );
        }
    }

    public function verificarBloqueio(int $id): bool
    {
        $bloqueadoAte = $this->escalar(
            "SELECT bloqueado_ate FROM usuarios WHERE id=?", [$id]
        );
        if (!$bloqueadoAte) return false;
        return strtotime($bloqueadoAte) > time();
    }

    public function actualizar(int $id, string $nome, string $email, string $perfil, int $ativo): bool
    {
        return (bool)$this->executar("
            UPDATE usuarios SET nome=?, email=?, perfil=?, ativo=? WHERE id=?
        ", [$nome, $email, $perfil, $ativo, $id]);
    }

    public function eliminar(int $id): bool
    {
        return (bool)$this->executar("DELETE FROM usuarios WHERE id=?", [$id]);
    }
}

// ============================================================
// CoordenadorModelo
// ============================================================
class CoordenadorModelo extends ModeloBase
{
    public function listarTodos(): array
    {
        return $this->consultar("
            SELECT co.*, u.email, u.ativo AS usuario_ativo, u.ultimo_login,
                   c.nome AS curso_nome, c.codigo AS curso_codigo
            FROM coordenadores co
            JOIN usuarios u ON co.usuario_id = u.id
            JOIN cursos c   ON co.curso_id   = c.id
            ORDER BY co.nome
        ");
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("
            SELECT co.*, u.email, u.ativo AS usuario_ativo,
                   c.nome AS curso_nome, c.codigo AS curso_codigo
            FROM coordenadores co
            JOIN usuarios u ON co.usuario_id = u.id
            JOIN cursos c   ON co.curso_id   = c.id
            WHERE co.id = ?
        ", [$id]);
    }

    public function obterPorUsuario(int $usuarioId): ?array
    {
        return $this->consultarUm("
            SELECT co.*, c.nome AS curso_nome, c.id AS curso_id,
                   c.duracao_anos
            FROM coordenadores co
            JOIN cursos c ON co.curso_id = c.id
            WHERE co.usuario_id = ?
        ", [$usuarioId]);
    }

    /**
     * Cria coordenador e cria automaticamente o utilizador de sistema.
     * Email: BI_coord@RegNota.com  |  Senha: RegNota@123
     */
    public function criar(array $dados): int
    {
        $um = new UsuarioModelo();
        $emailSistema = $dados['bi'] . '_coord@RegNota.com';
        
        $usuarioId = $um->criarUsuario($dados['nome'], $emailSistema, SENHA_INICIAL, 'coordenador');

        $this->executar("
            INSERT INTO coordenadores
                (usuario_id, curso_id, nome, bi, telefone, email_pessoal)
            VALUES (?,?,?,?,?,?)
        ", [
            $usuarioId, $dados['curso_id'], $dados['nome'],
            $dados['bi'], $dados['telefone'], $dados['email_pessoal'],
        ]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, array $dados): bool
    {
        return (bool)$this->executar("
            UPDATE coordenadores
            SET nome=?, bi=?, telefone=?, email_pessoal=?, ativo=?
            WHERE id=?
        ", [
            $dados['nome'], $dados['bi'], $dados['telefone'],
            $dados['email_pessoal'], $dados['ativo'], $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $coord = $this->obterPorId($id);
        if (!$coord) return false;
        $this->executar("DELETE FROM coordenadores WHERE id=?", [$id]);
        $this->executar("UPDATE usuarios SET ativo=0 WHERE id=?", [$coord['usuario_id']]);
        return true;
    }
}

// ============================================================
// ProfessorModelo
// ============================================================
class ProfessorModelo extends ModeloBase
{
    public function listarTodos(bool $apenasAtivos = false): array
    {
        $onde = $apenasAtivos ? 'WHERE p.ativo = 1' : '';
        return $this->consultar("
            SELECT p.*, u.email, u.ativo AS usuario_ativo, u.ultimo_login
            FROM professores p
            JOIN usuarios u ON p.usuario_id = u.id
            $onde
            ORDER BY p.nome
        ");
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("
            SELECT p.*, u.email, u.ativo AS usuario_ativo
            FROM professores p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = ?
        ", [$id]);
    }

    public function obterPorUsuario(int $usuarioId): ?array
    {
        return $this->consultarUm("
            SELECT p.*, u.email, u.ativo AS usuario_ativo
            FROM professores p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.usuario_id = ?
        ", [$usuarioId]);
    }

    /**
     * Cria professor e cria automaticamente o utilizador de sistema.

     * Email: BI_prof@RegNota.com  |  Senha: RegNota@123
     */
    public function criar(array $dados): int
    {
        $um = new UsuarioModelo();
        $emailSistema = $dados['bi'] . '_prof@RegNota.com';
        
        $usuarioId = $um->criarUsuario($dados['nome'], $emailSistema, SENHA_INICIAL, 'professor');

        $this->executar("
            INSERT INTO professores
                (usuario_id, nome, bi, telefone, email_pessoal, especialidade, fotografia)
            VALUES (?,?,?,?,?,?,?)
        ", [
            $usuarioId, $dados['nome'], $dados['bi'],
            $dados['telefone'], $dados['email_pessoal'],
            $dados['especialidade'], $dados['fotografia'] ?? null,
        ]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, array $dados): bool
    {
        return (bool)$this->executar("
            UPDATE professores
            SET nome=?, bi=?, telefone=?, email_pessoal=?, especialidade=?, ativo=?
            WHERE id=?
        ", [
            $dados['nome'], $dados['bi'], $dados['telefone'],
            $dados['email_pessoal'], $dados['especialidade'],
            $dados['ativo'], $id,
        ]);
    }

    /** Lista disciplinas atribuídas a este professor */
    public function listarDisciplinas(int $professorId): array
    {
        return $this->consultar("
            SELECT d.*, c.nome AS curso_nome
            FROM disciplinas d
            JOIN cursos c ON d.curso_id = c.id
            WHERE d.professor_id = ? AND d.ativo = 1
            ORDER BY d.ano_nivel, d.semestre_num, d.nome
        ", [$professorId]);
    }

    /** Verifica se o professor tem qualquer ocorrência associada via suas disciplinas */
    public function temOcorrencias(int $professorId): bool
    {
        $count = $this->escalar("
            SELECT COUNT(*) 
            FROM ocorrencias o 
            JOIN disciplinas d ON o.disciplina_id = d.id 
            WHERE d.professor_id = ?
        ", [$professorId]);
        return (int)$count > 0;
    }

    public function eliminar(int $id): bool
    {
        if ($this->temOcorrencias($id)) {
            return false;
        }

        $prof = $this->obterPorId($id);
        if (!$prof) return false;

        // Para evitar erros de FK, eliminamos primeiro as disciplinas associadas
        $this->executar("DELETE FROM disciplinas WHERE professor_id = ?", [$id]);
        
        $this->executar("DELETE FROM professores WHERE id = ?", [$id]);
        $this->executar("UPDATE usuarios SET ativo = 0 WHERE id = ?", [$prof['usuario_id']]);
        return true;
    }
}

// ============================================================
// GradeCurricularModelo
// ============================================================
class GradeCurricularModelo extends ModeloBase
{
    public function listarTodas(array $filtros = []): array
    {
        $onde = []; $params = [];
        if (!empty($filtros['curso_id']))     { $onde[] = 'curso_id=?';     $params[] = $filtros['curso_id']; }
        if (!empty($filtros['ano_nivel']))    { $onde[] = 'ano_nivel=?';    $params[] = $filtros['ano_nivel']; }
        if (isset($filtros['ativo']))         { $onde[] = 'g.ativo=?';        $params[] = $filtros['ativo']; }

        $clausula = $onde ? 'WHERE ' . implode(' AND ', $onde) : '';

        return $this->consultar("
            SELECT g.*, c.nome AS curso_nome
            FROM grade_curricular g
            JOIN cursos c ON g.curso_id = c.id
            $clausula
            ORDER BY g.ano_nivel, g.semestre_num, g.nome
        ", $params);
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("SELECT * FROM grade_curricular WHERE id = ?", [$id]);
    }

    public function criar(array $dados): int
    {
        $this->executar("
            INSERT INTO grade_curricular (curso_id, ano_nivel, semestre_num, nome, codigo, ativo)
            VALUES (?,?,?,?,?,?)
        ", [
            $dados['curso_id'], $dados['ano_nivel'], $dados['semestre_num'],
            $dados['nome'], $dados['codigo'] ?? null, $dados['ativo'] ?? 1,
        ]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, array $dados): bool
    {
        return (bool)$this->executar("
            UPDATE grade_curricular
            SET nome=?, codigo=?, ano_nivel=?, semestre_num=?, ativo=?
            WHERE id=?
        ", [
            $dados['nome'], $dados['codigo'] ?? null, $dados['ano_nivel'],
            $dados['semestre_num'], $dados['ativo'] ?? 1, $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        return (bool)$this->executar("DELETE FROM grade_curricular WHERE id=?", [$id]);
    }
}

// ============================================================
// DisciplinaModelo
// ============================================================
class DisciplinaModelo extends ModeloBase
{
    public function listarTodas(array $filtros = []): array
    {
        $onde = []; $params = [];
        if (!empty($filtros['curso_id']))     { $onde[] = 'd.curso_id=?';     $params[] = $filtros['curso_id']; }
        if (!empty($filtros['turma_id']))     { $onde[] = 'd.turma_id=?';     $params[] = $filtros['turma_id']; }
        if (!empty($filtros['professor_id'])) { $onde[] = 'd.professor_id=?'; $params[] = $filtros['professor_id']; }
        if (!empty($filtros['ano_nivel']))    { $onde[] = 'd.ano_nivel=?';    $params[] = $filtros['ano_nivel']; }
        if (!empty($filtros['semestre_num'])) { $onde[] = 'd.semestre_num=?'; $params[] = $filtros['semestre_num']; }
        if (isset($filtros['ativo']))         { $onde[] = 'd.ativo=?';        $params[] = $filtros['ativo']; }

        $clausula = $onde ? 'WHERE ' . implode(' AND ', $onde) : '';

        return $this->consultar("
            SELECT d.*, COALESCE(g.nome, d.nome) AS nome, COALESCE(g.codigo, d.codigo) AS codigo, c.nome AS curso_nome, p.nome AS professor_nome, t.codigo AS turma_codigo
            FROM disciplinas d
            LEFT JOIN grade_curricular g ON d.grade_id = g.id
            LEFT JOIN cursos c           ON d.curso_id = c.id
            LEFT JOIN professores p      ON d.professor_id = p.id
            LEFT JOIN turmas t           ON d.turma_id = t.id
            $clausula
            ORDER BY d.ano_nivel, d.semestre_num, g.nome
        ", $params);
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("
            SELECT d.*, COALESCE(g.nome, d.nome) AS nome, COALESCE(g.codigo, d.codigo) AS codigo, c.nome AS curso_nome, p.nome AS professor_nome, t.codigo AS turma_codigo
            FROM disciplinas d
            LEFT JOIN grade_curricular g ON d.grade_id = g.id
            LEFT JOIN cursos c      ON d.curso_id     = c.id
            LEFT JOIN professores p ON d.professor_id = p.id
            LEFT JOIN turmas t      ON d.turma_id     = t.id
            WHERE d.id = ?
        ", [$id]);
    }

    /** Para o formulário do estudante: disciplinas da turma específica */
    public function listarParaEstudante(int $turmaId): array
    {
        return $this->consultar("
            SELECT d.id, g.nome AS nome, g.codigo AS codigo, p.nome AS professor_nome, p.id AS professor_id
            FROM disciplinas d
            LEFT JOIN grade_curricular g ON d.grade_id = g.id
            LEFT JOIN professores p ON d.professor_id = p.id
            WHERE d.turma_id=? AND d.ativo=1
            ORDER BY g.nome
        ", [$turmaId]);
    }

    public function criar(array $dados): int
    {
        // Verificar se já existe uma disciplina para esta turma e esta grade curricular
        $existe = $this->escalar("
            SELECT id FROM disciplinas 
            WHERE turma_id=? AND grade_id=?
        ", [$dados['turma_id'], $dados['grade_id']]);

        if ($existe) {
            return (int)$existe;
        }

        // Se o nome ou código não forem fornecidos, tentamos recuperar da grade_curricular
        if (empty($dados['nome']) && !empty($dados['grade_id'])) {
            $grade = (new GradeCurricularModelo())->obterPorId($dados['grade_id']);
            if ($grade) {
                $dados['nome'] = $grade['nome'];
                $dados['codigo'] = $grade['codigo'];
            }
        }

        $this->executar("
            INSERT INTO disciplinas (curso_id, turma_id, grade_id, professor_id, ano_nivel, semestre_num, nome, codigo)
            VALUES (?,?,?,?,?,?,?,?)
        ", [
            $dados['curso_id'], $dados['turma_id'], $dados['grade_id'], 
            $dados['professor_id'] ?? null, $dados['ano_nivel'], $dados['semestre_num'],
            $dados['nome'] ?? 'Disciplina Sem Nome',
            $dados['codigo'] ?? null,
        ]);
        return $this->ultimoId();
    }

    public function actualizar(int $id, array $dados): bool
    {
        $sets = [];
        $params = [];
        foreach ($dados as $key => $value) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;

        return (bool)$this->executar("
            UPDATE disciplinas
            SET " . implode(', ', $sets) . "
            WHERE id=?
        ", $params);
    }

    public function eliminar(int $id): bool
    {
        $dep = $this->escalar("SELECT COUNT(*) FROM ocorrencias WHERE disciplina_id=?", [$id]);
        if ($dep > 0) return false;
        return (bool)$this->executar("DELETE FROM disciplinas WHERE id=?", [$id]);
    }

    public function atribuirProfessor(int $disciplinaId, int $professorId): bool
    {
        return (bool)$this->executar("UPDATE disciplinas SET professor_id = ? WHERE id = ?", [$professorId, $disciplinaId]);
    }
}

// ============================================================
// OcorrenciaModelo
// ============================================================
class OcorrenciaModelo extends ModeloBase
{
    public function listarFeedbacks(?int $cursoId = null): array
    {
        $filtroCurso = $cursoId ? "AND o.curso_id = $cursoId" : "";
        return $this->consultar("
            SELECT o.*,
                   c.nome  AS curso_nome,
                   t.codigo AS turma_codigo,
                   g.nome  AS disciplina_nome,
                   p.nome  AS professor_nome
            FROM ocorrencias o
            JOIN cursos c      ON o.curso_id      = c.id
            JOIN turmas t      ON o.turma_id      = t.id
            JOIN disciplinas d ON o.disciplina_id = d.id
            JOIN grade_curricular g ON d.grade_id = g.id
            JOIN professores p ON d.professor_id  = p.id
            WHERE o.feedback IS NOT NULL AND o.feedback != '' $filtroCurso
            ORDER BY o.feedback_at DESC
        ");
    }

    /**
     * Lista ocorrências com todos os filtros possíveis.
     * Suporta paginação.
     */
    public function listarTodas(array $filtros = [], int $limite = 0, int $offset = 0): array
    {
        [$onde, $params] = $this->construirFiltros($filtros);
        $limiteClausula  = $limite > 0 ? "LIMIT $limite OFFSET $offset" : '';

        return $this->consultar("
            SELECT o.*,
                   c.nome  AS curso_nome,
                   t.codigo AS turma_codigo, t.ano_nivel,
                   s.nome  AS semestre_nome, s.numero AS semestre_numero,
                   g.nome  AS disciplina_nome, g.codigo AS disciplina_codigo,
                   p.nome  AS professor_nome,
                   u.nome  AS resolvido_por_nome
            FROM ocorrencias o
            JOIN cursos c      ON o.curso_id      = c.id
            JOIN turmas t      ON o.turma_id      = t.id
            JOIN semestres s   ON o.semestre_id   = s.id
            JOIN disciplinas d ON o.disciplina_id = d.id
            JOIN grade_curricular g ON d.grade_id = g.id
            JOIN professores p ON d.professor_id  = p.id
            LEFT JOIN usuarios u ON o.resolvido_por = u.id
            $onde
            ORDER BY o.criado_em DESC
            $limiteClausula
        ", $params);
    }

    public function contarTodas(array $filtros = []): int
    {
        [$onde, $params] = $this->construirFiltros($filtros);
        return (int)$this->escalar("
            SELECT COUNT(*) FROM ocorrencias o
            JOIN cursos c      ON o.curso_id      = c.id
            JOIN disciplinas d ON o.disciplina_id = d.id
            $onde
        ", $params);
    }

    public function obterPorId(int $id): ?array
    {
        return $this->consultarUm("
            SELECT o.*,
                   c.nome  AS curso_nome,
                   t.codigo AS turma_codigo, t.ano_nivel,
                   s.nome  AS semestre_nome,
                   d.nome  AS disciplina_nome, d.codigo AS disciplina_codigo,
                   p.nome  AS professor_nome, p.id AS professor_id_resp,
                   u.nome  AS resolvido_por_nome
            FROM ocorrencias o
            JOIN cursos c      ON o.curso_id      = c.id
            JOIN turmas t      ON o.turma_id      = t.id
            JOIN semestres s   ON o.semestre_id   = s.id
            JOIN disciplinas d ON o.disciplina_id = d.id
            JOIN professores p ON d.professor_id  = p.id
            LEFT JOIN usuarios u ON o.resolvido_por = u.id
            WHERE o.id = ?
        ", [$id]);
    }

    public function obterPorTicket(string $ticket): ?array
    {
        return $this->consultarUm("
            SELECT o.id, o.ticket, o.estado, o.criado_em, o.atualizado_em,
                   o.observacao_oficial, o.estudante_nome
            FROM ocorrencias o
            WHERE o.ticket = ?
        ", [$ticket]);
    }

    public function pesquisarEstudante(string $nome): array
    {
        return $this->consultar("
            SELECT o.id, o.ticket, o.estado, o.criado_em, o.atualizado_em,
                   o.observacao_oficial, o.estudante_nome
            FROM ocorrencias o
            WHERE o.estudante_nome LIKE ?
            ORDER BY o.criado_em DESC
            LIMIT 50
        ", ['%' . $nome . '%']);
    }

    public function obterTempoMedioResolucao(): float {
        return (float)$this->escalar("
            SELECT AVG(TIMESTAMPDIFF(SECOND, criado_em, resolvido_em)) / 3600
            FROM ocorrencias WHERE estado = 'resolvida' AND resolvido_em IS NOT NULL
        ") ?? 0.0;
    }

    public function registarFeedback(int $id, string $feedback): bool {
        return (bool)$this->executar("
            UPDATE ocorrencias 
            SET feedback = ?, feedback_at = NOW() 
            WHERE id = ?
        ", [$feedback, $id]);
    }

    /**
     * Cria nova ocorrência com geração automática do ticket.
     * A lógica foi movida do Stored Procedure para o PHP para compatibilidade com hosting.
     */
    public function criar(array $dados): array
    {
        // 1. Gerar ticket via PHP
        $ano = date('Y');
        
        // Incrementa o contador no banco de dados
        $this->bd->prepare("
            INSERT INTO contador_tickets (ano, ultimo) 
            VALUES (?, 1) 
            ON DUPLICATE KEY UPDATE ultimo = ultimo + 1
        ")->execute([$ano]);

        // Recupera o número atualizado
        $stmtNum = $this->bd->prepare("SELECT ultimo FROM contador_tickets WHERE ano = ?");
        $stmtNum->execute([$ano]);
        $num = $stmtNum->fetchColumn();

        $ticket = "RN-{$ano}-" . str_pad($num, 6, '0', STR_PAD_LEFT);

        // 2. Inserir a ocorrência
        $stmt = $this->bd->prepare("
            INSERT INTO ocorrencias (
                ticket, estudante_nome, estudante_numero, estudante_telefone, estudante_email,
                curso_id, turma_id, semestre_id, disciplina_id,
                tipo_avaliacao, descricao, ip_origem
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $ticket,
            $dados['estudante_nome'], $dados['estudante_numero'],
            $dados['estudante_telefone'], $dados['estudante_email'],
            $dados['curso_id'], $dados['turma_id'], $dados['semestre_id'],
            $dados['disciplina_id'], $dados['tipo_avaliacao'], $dados['descricao'],
            $dados['ip_origem'] ?? null,
        ]);

        return ['id' => (int)$this->bd->lastInsertId(), 'ticket' => $ticket];
    }

    public function actualizar(int $id, array $dados): bool
    {
        return (bool)$this->executar("
            UPDATE ocorrencias
            SET estudante_nome=?, estudante_numero=?, estudante_telefone=?,
                estudante_email=?, curso_id=?, turma_id=?, semestre_id=?,
                disciplina_id=?, tipo_avaliacao=?, descricao=?,
                estado=?, observacao_oficial=?, nota_interna=?
            WHERE id=?
        ", [
            $dados['estudante_nome'], $dados['estudante_numero'],
            $dados['estudante_telefone'], $dados['estudante_email'],
            $dados['curso_id'], $dados['turma_id'], $dados['semestre_id'],
            $dados['disciplina_id'], $dados['tipo_avaliacao'], $dados['descricao'],
            $dados['estado'], $dados['observacao_oficial'] ?? null,
            $dados['nota_interna'] ?? null, $id,
        ]);
    }

    /** Actualiza apenas o estado + campos relacionados */
    public function actualizarEstado(int $id, string $estado, ?string $obs, ?string $notaInterna, int $usuarioId): bool
    {
        $resolvido    = in_array($estado, ['resolvida', 'encerrada']) ? date('Y-m-d H:i:s') : null;
        $resolvidoPor = in_array($estado, ['resolvida', 'encerrada']) ? $usuarioId : null;

        return (bool)$this->executar("
            UPDATE ocorrencias
            SET estado=?, observacao_oficial=?, nota_interna=?,
                resolvido_por=?, resolvido_em=?
            WHERE id=?
        ", [$estado, $obs, $notaInterna, $resolvidoPor, $resolvido, $id]);
    }

    /** Encaminhar ao professor */
    public function encaminharProfessor(int $id, int $professorId, int $usuarioId): bool
    {
        return (bool)$this->executar("
            UPDATE ocorrencias
            SET estado='encaminhada_professor',
                encaminhado_para=?, encaminhado_em=NOW()
            WHERE id=?
        ", [$professorId, $id]);
    }

    public function encaminharMultiplos(array $ids, int $responsavelId, string $estado, int $usuarioId): int
    {
        if (empty($ids)) return 0;
        $idsStr = implode(',', array_map('intval', $ids));
        $coluna = ($estado === 'encaminhada_professor') ? 'encaminhado_para' : 'resolvido_por';
        $campoData = ($estado === 'encaminhada_professor') ? 'encaminhado_em' : 'resolvido_em';
        
        return $this->executar("
            UPDATE ocorrencias 
            SET estado=?, $coluna=?, $campoData=NOW() 
            WHERE id IN ($idsStr)
        ", [$estado, $responsavelId]);
    }

    public function eliminar(int $id): bool
    {
        return (bool)$this->executar("DELETE FROM ocorrencias WHERE id=?", [$id]);
    }

    // ── Estatísticas ──────────────────────────────────────────

    public function estatisticasGerais(): array
    {
        return $this->consultarUm("
            SELECT
                COUNT(*) AS total,
                SUM(estado='submetida')              AS submetidas,
                SUM(estado='em_analise')             AS em_analise,
                SUM(estado='encaminhada_professor')  AS encaminhadas,
                SUM(estado='respondida_professor')   AS respondidas,
                SUM(estado='em_revisao_coordenacao') AS em_revisao,
                SUM(estado='resolvida')              AS resolvidas,
                SUM(estado='encerrada')              AS encerradas,
                SUM(estado='cancelada')              AS canceladas
            FROM ocorrencias
        ") ?? array_fill_keys(['total','submetidas','em_analise','encaminhadas',
                               'respondidas','em_revisao','resolvidas','encerradas','canceladas'], 0);
    }

    public function estatisticasPorCurso(): array
    {
        return $this->consultar("
            SELECT c.nome AS curso, COUNT(*) AS total
            FROM ocorrencias o JOIN cursos c ON o.curso_id=c.id
            GROUP BY o.curso_id ORDER BY total DESC
        ");
    }

    public function estatisticasPorTipoAvaliacao(): array
    {
        return $this->consultar("
            SELECT tipo_avaliacao, COUNT(*) AS total
            FROM ocorrencias GROUP BY tipo_avaliacao ORDER BY total DESC
        ");
    }

    public function estatisticasPorDisciplina(int $limite = 10): array
    {
        return $this->consultar("
            SELECT g.nome AS disciplina, COUNT(*) AS total
            FROM ocorrencias o JOIN disciplinas d ON o.disciplina_id=d.id
            JOIN grade_curricular g ON d.grade_id=g.id
            GROUP BY g.id ORDER BY total DESC LIMIT $limite
        ");
    }

    public function obterRecentes(int $n = 10): array
    {
        return $this->listarTodas([], $n, 0);
    }

    // ── Filtros internos ──────────────────────────────────────

    private function construirFiltros(array $filtros): array
    {
        $onde = []; $params = [];

        if (!empty($filtros['ticket'])) {
            $onde[] = 'o.ticket LIKE ?'; $params[] = '%' . $filtros['ticket'] . '%';
        }
        if (!empty($filtros['estudante_nome'])) {
            $onde[] = 'o.estudante_nome LIKE ?'; $params[] = '%' . $filtros['estudante_nome'] . '%';
        }
        if (!empty($filtros['estudante_numero'])) {
            $onde[] = 'o.estudante_numero LIKE ?'; $params[] = '%' . $filtros['estudante_numero'] . '%';
        }
        if (!empty($filtros['curso_id'])) {
            $onde[] = 'o.curso_id=?'; $params[] = $filtros['curso_id'];
        }
        if (!empty($filtros['turma_id'])) {
            $onde[] = 'o.turma_id=?'; $params[] = $filtros['turma_id'];
        }
        if (!empty($filtros['semestre_id'])) {
            $onde[] = 'o.semestre_id=?'; $params[] = $filtros['semestre_id'];
        }
        if (!empty($filtros['disciplina_id'])) {
            $onde[] = 'o.disciplina_id=?'; $params[] = $filtros['disciplina_id'];
        }
        if (!empty($filtros['professor_id'])) {
            $onde[] = 'd.professor_id=?'; $params[] = $filtros['professor_id'];
        }
        if (!empty($filtros['tipo_avaliacao'])) {
            $onde[] = 'o.tipo_avaliacao=?'; $params[] = $filtros['tipo_avaliacao'];
        }
        if (!empty($filtros['estado'])) {
            $onde[] = 'o.estado=?'; $params[] = $filtros['estado'];
        }
        if (!empty($filtros['data_inicio'])) {
            $onde[] = 'DATE(o.criado_em)>=?'; $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $onde[] = 'DATE(o.criado_em)<=?'; $params[] = $filtros['data_fim'];
        }

        return [
            $onde ? 'WHERE ' . implode(' AND ', $onde) : '',
            $params,
        ];
    }
}

// ============================================================
// HistoricoModelo
// ============================================================
class HistoricoModelo extends ModeloBase
{
    public function listarPorOcorrencia(int $ocorrenciaId): array
    {
        return $this->consultar("
            SELECT h.*, u.nome AS usuario_nome
            FROM ocorrencias_historico h
            LEFT JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.ocorrencia_id = ?
            ORDER BY h.criado_em ASC
        ", [$ocorrenciaId]);
    }

    public function registar(
        int     $ocorrenciaId,
        ?string $estadoAnterior,
        string  $estadoNovo,
        ?string $observacao,
        ?int    $usuarioId
    ): void {
        $this->executar("
            INSERT INTO ocorrencias_historico
                (ocorrencia_id, estado_anterior, estado_novo, observacao, usuario_id)
            VALUES (?,?,?,?,?)
        ", [$ocorrenciaId, $estadoAnterior, $estadoNovo, $observacao, $usuarioId]);
    }
}

// ============================================================
// RespostaProfessorModelo
// ============================================================
class RespostaProfessorModelo extends ModeloBase
{
    public function listarPorOcorrencia(int $ocorrenciaId): array
    {
        return $this->consultar("
            SELECT r.*, p.nome AS professor_nome
            FROM respostas_professor r
            JOIN professores p ON r.professor_id = p.id
            WHERE r.ocorrencia_id = ?
            ORDER BY r.criado_em ASC
        ", [$ocorrenciaId]);
    }

    public function criar(int $ocorrenciaId, int $professorId, string $resposta): int
    {
        $this->executar("
            INSERT INTO respostas_professor (ocorrencia_id, professor_id, resposta)
            VALUES (?,?,?)
        ", [$ocorrenciaId, $professorId, $resposta]);
        return $this->ultimoId();
    }
}

// ============================================================
// AuditoriaModelo
// ============================================================
class AuditoriaModelo extends ModeloBase
{
    public function listarRecentes(int $limite = 50): array
    {
        return $this->consultar("
            SELECT a.*, u.nome AS usuario_nome
            FROM auditoria a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            ORDER BY a.criado_em DESC
            LIMIT $limite
        ");
    }

    public function listarPorUsuario(int $usuarioId, int $limite = 30): array
    {
        return $this->consultar("
            SELECT * FROM auditoria
            WHERE usuario_id = ?
            ORDER BY criado_em DESC LIMIT $limite
        ", [$usuarioId]);
    }
}
