-- ============================================================
-- REGNOTAS v3.0 – Schema Completo da Base de Dados
-- Sistema de Gestão do Ciclo de Vida de Ocorrências de Notas
-- Linguagem: Português
-- Codificação: UTF-8 (utf8mb4)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ── Criar e seleccionar a base de dados ──────────────────────
CREATE DATABASE IF NOT EXISTS `regnotas`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
    COMMENT 'Base de dados do sistema Regnotas v3.0';

USE `regnotas`;

-- ============================================================
-- TABELA: faculdades
-- Suporte a multi-faculdade (escalabilidade futura)
-- ============================================================
CREATE TABLE IF NOT EXISTS `faculdades` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`          VARCHAR(200)    NOT NULL                    COMMENT 'Nome completo da faculdade',
    `sigla`         VARCHAR(20)     NOT NULL                    COMMENT 'Sigla, ex: FEUP, FCUP',
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 1          COMMENT '1=activa, 0=inactiva',
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_faculdade_sigla` (`sigla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Faculdades da instituição (escalabilidade multi-faculdade)';

-- ============================================================
-- TABELA: cursos
-- Cada curso pertence a uma faculdade e tem um coordenador
-- ============================================================
CREATE TABLE IF NOT EXISTS `cursos` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `faculdade_id`  INT UNSIGNED    NOT NULL                    COMMENT 'Faculdade a que pertence',
    `codigo`        VARCHAR(20)     NOT NULL                    COMMENT 'Código único, ex: COMP, INFO',
    `nome`          VARCHAR(200)    NOT NULL                    COMMENT 'Nome completo do curso',
    `duracao_anos`  TINYINT UNSIGNED NOT NULL DEFAULT 5         COMMENT 'Duração em anos letivos',
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_curso_codigo` (`codigo`),
    CONSTRAINT `fk_curso_faculdade`
        FOREIGN KEY (`faculdade_id`) REFERENCES `faculdades`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cursos disponíveis por faculdade';

-- ============================================================
-- TABELA: anos_letivos
-- Ano lectivo ex: 2024/2025 com controlo de activo
-- ============================================================
CREATE TABLE IF NOT EXISTS `anos_letivos` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`          VARCHAR(20)     NOT NULL                    COMMENT 'Ex: 2024/2025',
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 0          COMMENT 'Apenas 1 activo por vez',
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ano_letivo_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Anos letivos com controlo de activo único';

-- ============================================================
-- TABELA: turmas
-- Código padronizado: 101,102…201,202…até 501,502...
-- Geradas automaticamente pelo sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS `turmas` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `curso_id`       INT UNSIGNED    NOT NULL,
    `ano_letivo_id`  INT UNSIGNED    NOT NULL,
    `ano_nivel`      TINYINT UNSIGNED NOT NULL                  COMMENT 'Ano do curso: 1 a 5',
    `codigo`         VARCHAR(10)     NOT NULL                   COMMENT 'Código padronizado: 101,102,201...',
    `ativo`          TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_turma` (`curso_id`, `ano_letivo_id`, `codigo`),
    INDEX `idx_turma_nivel` (`ano_nivel`),
    CONSTRAINT `fk_turma_curso`
        FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_turma_ano`
        FOREIGN KEY (`ano_letivo_id`) REFERENCES `anos_letivos`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Turmas por curso e ano letivo com código padronizado (101,102...)';

-- ============================================================
-- TABELA: semestres
-- Semestres ligados ao ano letivo (1º e 2º semestre)
-- ============================================================
CREATE TABLE IF NOT EXISTS `semestres` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ano_letivo_id`  INT UNSIGNED    NOT NULL,
    `numero`         TINYINT UNSIGNED NOT NULL                  COMMENT '1 ou 2',
    `nome`           VARCHAR(60)     NOT NULL                   COMMENT 'Ex: 1º Semestre 2024/2025',
    `data_inicio`    DATE            NULL,
    `data_fim`       DATE            NULL,
    `ativo`          TINYINT(1)      NOT NULL DEFAULT 0,
    `criado_em`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_semestre` (`ano_letivo_id`, `numero`),
    CONSTRAINT `fk_semestre_ano`
        FOREIGN KEY (`ano_letivo_id`) REFERENCES `anos_letivos`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Semestres por ano letivo';

-- ============================================================
-- TABELA: usuarios
-- Tabela única de autenticação para todos os perfis do sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`              VARCHAR(200)    NOT NULL                COMMENT 'Nome completo',
    `email`             VARCHAR(200)    NOT NULL                COMMENT 'Email único de autenticação',
    `password`          VARCHAR(255)    NOT NULL                COMMENT 'Hash bcrypt',
    `perfil`            ENUM('super_admin','coordenador','professor') NOT NULL,
    `ativo`             TINYINT(1)      NOT NULL DEFAULT 1,
    `primeiro_login`    TINYINT(1)      NOT NULL DEFAULT 1      COMMENT '1=deve alterar senha',
    `senha_alterada`    TINYINT(1)      NOT NULL DEFAULT 0      COMMENT '1=senha já foi alterada',
    `ultimo_login`      TIMESTAMP       NULL,
    `tentativas_login`  TINYINT UNSIGNED NOT NULL DEFAULT 0     COMMENT 'Tentativas falhadas consecutivas',
    `bloqueado_ate`     TIMESTAMP       NULL                    COMMENT 'Bloqueio temporário por tentativas excessivas',
    `criado_em`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuario_email` (`email`),
    INDEX `idx_usuario_perfil` (`perfil`),
    INDEX `idx_usuario_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabela única de autenticação para todos os perfis do sistema';

-- ============================================================
-- TABELA: coordenadores
-- Cada coordenador pertence a um único curso
-- ============================================================
CREATE TABLE IF NOT EXISTS `coordenadores` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `usuario_id`    INT UNSIGNED    NOT NULL                    COMMENT 'Ligação ao utilizador de autenticação',
    `curso_id`      INT UNSIGNED    NOT NULL                    COMMENT 'Curso que coordena (1 para 1)',
    `nome`          VARCHAR(200)    NOT NULL,
    `bi`            VARCHAR(20)     NOT NULL                    COMMENT 'Número do BI',
    `telefone`      VARCHAR(20)     NOT NULL,
    `email_pessoal` VARCHAR(200)    NOT NULL,
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_coord_bi`      (`bi`),
    UNIQUE KEY `uk_coord_curso`   (`curso_id`),
    UNIQUE KEY `uk_coord_usuario` (`usuario_id`),
    CONSTRAINT `fk_coord_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_coord_curso`
        FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Coordenadores – um por curso';

-- ============================================================
-- TABELA: professores
-- Pode leccionar em várias disciplinas
-- ============================================================
CREATE TABLE IF NOT EXISTS `professores` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `usuario_id`    INT UNSIGNED    NOT NULL,
    `nome`          VARCHAR(200)    NOT NULL,
    `bi`            VARCHAR(20)     NOT NULL                    COMMENT 'Número do BI (usado para gerar email)',
    `telefone`      VARCHAR(20)     NOT NULL,
    `email_pessoal` VARCHAR(200)    NOT NULL,
    `especialidade` VARCHAR(150)    NOT NULL,
    `fotografia`    VARCHAR(300)    NULL                        COMMENT 'Caminho da fotografia (opcional)',
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_prof_bi`      (`bi`),
    UNIQUE KEY `uk_prof_usuario` (`usuario_id`),
    CONSTRAINT `fk_prof_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Professores com ligação automática a utilizador';

-- ============================================================
-- TABELA: disciplinas
-- Ligada ao curso, nível e semestre do curso
-- ============================================================
CREATE TABLE IF NOT EXISTS `disciplinas` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `curso_id`      INT UNSIGNED    NOT NULL,
    `professor_id`  INT UNSIGNED    NOT NULL                    COMMENT 'Professor responsável',
    `nome`          VARCHAR(200)    NOT NULL,
    `codigo`        VARCHAR(20)     NULL                        COMMENT 'Código da disciplina, ex: MAT101',
    `ano_nivel`     TINYINT UNSIGNED NOT NULL                   COMMENT 'Ano do curso onde é leccionada (1-5)',
    `semestre_num`  TINYINT UNSIGNED NOT NULL                   COMMENT '1 ou 2',
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_disc_curso`    (`curso_id`),
    INDEX `idx_disc_prof`     (`professor_id`),
    INDEX `idx_disc_nivel`    (`ano_nivel`, `semestre_num`),
    CONSTRAINT `fk_disc_curso`
        FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_disc_prof`
        FOREIGN KEY (`professor_id`) REFERENCES `professores`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Disciplinas por curso, ano e semestre';

-- ============================================================
-- TABELA: ocorrencias
-- Núcleo do sistema – cada ocorrência tem um ticket único RN-AAAA-000001
-- ============================================================
CREATE TABLE IF NOT EXISTS `ocorrencias` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    -- Identificação única
    `ticket`            VARCHAR(20)     NOT NULL                COMMENT 'Ex: RN-2026-000001 – gerado automaticamente',
    -- Dados do estudante (não tem conta, por isso guardamos aqui)
    `estudante_nome`    VARCHAR(200)    NOT NULL,
    `estudante_numero`  VARCHAR(30)     NOT NULL                COMMENT 'Número de estudante',
    `estudante_telefone` VARCHAR(20)    NOT NULL,
    `estudante_email`   VARCHAR(200)    NOT NULL,
    -- Contexto académico
    `curso_id`          INT UNSIGNED    NOT NULL,
    `turma_id`          INT UNSIGNED    NOT NULL,
    `semestre_id`       INT UNSIGNED    NOT NULL,
    `disciplina_id`     INT UNSIGNED    NOT NULL,
    -- Dados da avaliação
    `tipo_avaliacao`    ENUM('1a_parcial','2a_parcial','exame','recurso') NOT NULL,
    `descricao`         TEXT            NOT NULL                COMMENT 'Descrição detalhada da ocorrência',
    -- Estado do ciclo de vida
    `estado`            ENUM(
                            'submetida',
                            'em_analise',
                            'encaminhada_professor',
                            'respondida_professor',
                            'em_revisao_coordenacao',
                            'resolvida',
                            'encerrada',
                            'cancelada'
                        ) NOT NULL DEFAULT 'submetida',
    -- Gestão interna
    `observacao_oficial` TEXT           NULL                    COMMENT 'Visível ao estudante na consulta pública',
    `nota_interna`       TEXT           NULL                    COMMENT 'Nota interna, não visível ao estudante',
    `resolvido_por`      INT UNSIGNED   NULL                    COMMENT 'Utilizador que resolveu',
    `resolvido_em`       TIMESTAMP      NULL,
    `encaminhado_para`   INT UNSIGNED   NULL                    COMMENT 'Professor para quem foi encaminhado',
    `encaminhado_em`     TIMESTAMP      NULL,
    -- Rastreabilidade
    `ip_origem`          VARCHAR(45)    NULL                    COMMENT 'IP da submissão (IPv4/IPv6)',
    `criado_em`          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY  `uk_ticket`             (`ticket`),
    INDEX `idx_ocorr_estado`            (`estado`),
    INDEX `idx_ocorr_curso`             (`curso_id`),
    INDEX `idx_ocorr_turma`             (`turma_id`),
    INDEX `idx_ocorr_disciplina`        (`disciplina_id`),
    INDEX `idx_ocorr_estudante_nome`    (`estudante_nome`),
    INDEX `idx_ocorr_estudante_num`     (`estudante_numero`),
    INDEX `idx_ocorr_criado`            (`criado_em`),
    CONSTRAINT `fk_ocorr_curso`
        FOREIGN KEY (`curso_id`)       REFERENCES `cursos`(`id`)       ON DELETE RESTRICT,
    CONSTRAINT `fk_ocorr_turma`
        FOREIGN KEY (`turma_id`)       REFERENCES `turmas`(`id`)       ON DELETE RESTRICT,
    CONSTRAINT `fk_ocorr_semestre`
        FOREIGN KEY (`semestre_id`)    REFERENCES `semestres`(`id`)    ON DELETE RESTRICT,
    CONSTRAINT `fk_ocorr_disciplina`
        FOREIGN KEY (`disciplina_id`)  REFERENCES `disciplinas`(`id`)  ON DELETE RESTRICT,
    CONSTRAINT `fk_ocorr_resolvido`
        FOREIGN KEY (`resolvido_por`)  REFERENCES `usuarios`(`id`)     ON DELETE SET NULL,
    CONSTRAINT `fk_ocorr_encaminhado`
        FOREIGN KEY (`encaminhado_para`) REFERENCES `professores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ocorrências – núcleo do sistema com ciclo de vida completo';

-- ============================================================
-- TABELA: ocorrencias_historico
-- Auditoria completa de cada mudança de estado
-- ============================================================
CREATE TABLE IF NOT EXISTS `ocorrencias_historico` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ocorrencia_id`   INT UNSIGNED    NOT NULL,
    `estado_anterior` VARCHAR(50)     NULL                      COMMENT 'Estado antes da mudança',
    `estado_novo`     VARCHAR(50)     NOT NULL                  COMMENT 'Novo estado após mudança',
    `observacao`      TEXT            NULL                      COMMENT 'Nota sobre a mudança',
    `usuario_id`      INT UNSIGNED    NULL                      COMMENT 'Quem fez a mudança',
    `criado_em`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_hist_ocorr` (`ocorrencia_id`),
    CONSTRAINT `fk_hist_ocorr`
        FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico completo de estados das ocorrências';

-- ============================================================
-- TABELA: respostas_professor
-- Respostas dos professores às ocorrências encaminhadas
-- ============================================================
CREATE TABLE IF NOT EXISTS `respostas_professor` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ocorrencia_id` INT UNSIGNED    NOT NULL,
    `professor_id`  INT UNSIGNED    NOT NULL,
    `resposta`      TEXT            NOT NULL,
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_resp_ocorr` (`ocorrencia_id`),
    CONSTRAINT `fk_resp_ocorr`
        FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resp_prof`
        FOREIGN KEY (`professor_id`)  REFERENCES `professores`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Respostas dos professores às ocorrências';

-- ============================================================
-- TABELA: auditoria
-- Registo completo de todas as acções no sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS `auditoria` (
    `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `usuario_id`  INT UNSIGNED     NULL                         COMMENT 'NULL para acções do sistema',
    `acao`        VARCHAR(100)     NOT NULL                     COMMENT 'Ex: login, criar_ocorrencia, mudar_estado',
    `tabela`      VARCHAR(60)      NULL                         COMMENT 'Tabela afectada',
    `registo_id`  INT UNSIGNED     NULL                         COMMENT 'ID do registo afectado',
    `detalhe`     TEXT             NULL                         COMMENT 'Detalhes em JSON ou texto livre',
    `ip`          VARCHAR(45)      NULL,
    `user_agent`  VARCHAR(500)     NULL,
    `criado_em`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_usuario`  (`usuario_id`),
    INDEX `idx_audit_acao`     (`acao`),
    INDEX `idx_audit_tabela`   (`tabela`, `registo_id`),
    INDEX `idx_audit_criado`   (`criado_em`),
    CONSTRAINT `fk_audit_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Auditoria completa do sistema – todas as acções registadas';

-- ============================================================
-- TABELA: contador_tickets
-- Garante unicidade e sequência dos tickets por ano
-- ============================================================
CREATE TABLE IF NOT EXISTS `contador_tickets` (
    `ano`       YEAR         NOT NULL,
    `ultimo`    INT UNSIGNED NOT NULL DEFAULT 0   COMMENT 'Último número de ticket gerado',
    PRIMARY KEY (`ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Contador para geração sequencial e única de tickets RN-AAAA-NNNNNN';

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Faculdade padrão
INSERT INTO `faculdades` (`nome`, `sigla`) VALUES
('Faculdade de Ciências e Tecnologia', 'FCT');

-- Curso de Computação
INSERT INTO `cursos` (`faculdade_id`, `codigo`, `nome`, `duracao_anos`) VALUES
(1, 'COMP', 'Licenciatura em Engenharia Informática e Computação', 5);

-- Ano letivo activo
INSERT INTO `anos_letivos` (`nome`, `ativo`) VALUES
('2024/2025', 0),
('2025/2026', 1);

-- Semestres do ano activo (2025/2026)
INSERT INTO `semestres` (`ano_letivo_id`, `numero`, `nome`, `data_inicio`, `data_fim`, `ativo`) VALUES
(2, 1, '1º Semestre 2025/2026', '2025-09-01', '2026-01-31', 0),
(2, 2, '2º Semestre 2025/2026', '2026-02-01', '2026-07-31', 1);

-- Turmas automáticas para 2025/2026 (padrão: X01, X02 por nível)
-- 1º Ano: 101, 102  |  2º Ano: 201, 202  |  3º Ano: 301, 302
-- 4º Ano: 401, 402  |  5º Ano: 501, 502
INSERT INTO `turmas` (`curso_id`, `ano_letivo_id`, `ano_nivel`, `codigo`) VALUES
(1, 2, 1, '101'), (1, 2, 1, '102'),
(1, 2, 2, '201'), (1, 2, 2, '202'),
(1, 2, 3, '301'), (1, 2, 3, '302'),
(1, 2, 4, '401'), (1, 2, 4, '402'),
(1, 2, 5, '501'), (1, 2, 5, '502');

-- Utilizador Super Administrador
-- Senha: Admin@RegNotas2024
INSERT INTO `usuarios` (`nome`, `email`, `password`, `perfil`, `primeiro_login`, `senha_alterada`) VALUES
('Super Administrador', 'admin@regnotas.ao',
 '$2y$12$zpU91IRGPlHTf78/nbE5ueDnnn7ZzabhxaImyqKiiRPZRB9U9oxBO',
 'super_admin', 0, 1);

-- Senha do admin: Admin@RegNotas2024 (altere imediatamente após instalação!)

-- Contador de tickets para o ano corrente
INSERT INTO `contador_tickets` (`ano`, `ultimo`) VALUES (YEAR(NOW()), 0);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- STORED PROCEDURE: gerar_ticket
-- Gera o próximo ticket RN-AAAA-NNNNNN de forma atómica (thread-safe)
-- ============================================================
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `gerar_ticket`(OUT p_ticket VARCHAR(20))
BEGIN
    DECLARE v_ano YEAR DEFAULT YEAR(NOW());
    DECLARE v_num INT UNSIGNED DEFAULT 0;

    -- Inserir o ano se não existir, senão incrementar atomicamente
    INSERT INTO `contador_tickets` (`ano`, `ultimo`) VALUES (v_ano, 1)
    ON DUPLICATE KEY UPDATE `ultimo` = `ultimo` + 1;

    -- Obter o número gerado
    SELECT `ultimo` INTO v_num
    FROM `contador_tickets`
    WHERE `ano` = v_ano;

    -- Formatar: RN-2026-000001
    SET p_ticket = CONCAT('RN-', v_ano, '-', LPAD(v_num, 6, '0'));
END$$

DELIMITER ;
