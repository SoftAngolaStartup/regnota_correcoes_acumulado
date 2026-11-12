-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 03-Jul-2026 às 16:29
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `regnotas`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `anos_letivos`
--

DROP TABLE IF EXISTS `anos_letivos`;
CREATE TABLE `anos_letivos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(20) NOT NULL COMMENT 'Ex: 2024/2025',
  `ativo` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Apenas 1 activo por vez',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Anos letivos com controlo de activo único';

--
-- Extraindo dados da tabela `anos_letivos`
--

INSERT INTO `anos_letivos` (`id`, `nome`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, '2024/2025', 0, '2026-07-02 09:08:47', '2026-07-02 11:20:35'),
(2, '2025/2026', 1, '2026-07-02 09:08:47', '2026-07-02 11:20:35');

-- --------------------------------------------------------

--
-- Estrutura da tabela `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL para acções do sistema',
  `acao` varchar(100) NOT NULL COMMENT 'Ex: login, criar_ocorrencia, mudar_estado',
  `tabela` varchar(60) DEFAULT NULL COMMENT 'Tabela afectada',
  `registo_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID do registo afectado',
  `detalhe` text DEFAULT NULL COMMENT 'Detalhes em JSON ou texto livre',
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoria completa do sistema – todas as acções registadas';

-- --------------------------------------------------------

--
-- Estrutura da tabela `contador_tickets`
--

DROP TABLE IF EXISTS `contador_tickets`;
CREATE TABLE `contador_tickets` (
  `ano` year(4) NOT NULL,
  `ultimo` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Último número de ticket gerado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contador para geração sequencial e única de tickets RN-AAAA-NNNNNN';

--
-- Extraindo dados da tabela `contador_tickets`
--

INSERT INTO `contador_tickets` (`ano`, `ultimo`) VALUES
('2026', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `coordenadores`
--

DROP TABLE IF EXISTS `coordenadores`;
CREATE TABLE `coordenadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL COMMENT 'Ligação ao utilizador de autenticação',
  `curso_id` int(10) UNSIGNED NOT NULL COMMENT 'Curso que coordena (1 para 1)',
  `nome` varchar(200) NOT NULL,
  `bi` varchar(20) NOT NULL COMMENT 'Número do BI',
  `telefone` varchar(20) NOT NULL,
  `email_pessoal` varchar(200) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Coordenadores – um por curso';

--
-- Extraindo dados da tabela `coordenadores`
--

INSERT INTO `coordenadores` (`id`, `usuario_id`, `curso_id`, `nome`, `bi`, `telefone`, `email_pessoal`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 6, 14, 'Ribas', '0000000LG00', '9999995555', 'ribas@gmail.com', 1, '2026-07-03 02:09:32', '2026-07-03 02:09:32');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cursos`
--

DROP TABLE IF EXISTS `cursos`;
CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `faculdade_id` int(10) UNSIGNED NOT NULL COMMENT 'Faculdade a que pertence',
  `codigo` varchar(20) NOT NULL COMMENT 'Código único, ex: COMP, INFO',
  `nome` varchar(200) NOT NULL COMMENT 'Nome completo do curso',
  `duracao_anos` tinyint(3) UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Duração em anos letivos',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cursos disponíveis por faculdade';

--
-- Extraindo dados da tabela `cursos`
--

INSERT INTO `cursos` (`id`, `faculdade_id`, `codigo`, `nome`, `duracao_anos`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'COMP', 'Licenciatura em Engenharia Informática e Computação', 5, 1, '2026-07-02 09:08:47', '2026-07-02 09:08:47'),
(2, 1, 'CUR001', 'ENSINO PRIMÁRIO', 5, 1, '2026-07-02 12:54:08', '2026-07-02 12:54:08'),
(4, 1, 'CUR002', 'HISTÓRIA', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(5, 1, 'CUR003', 'PSICOLOGIA', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(6, 1, 'CUR004', 'ENFERMAGEM GERAL', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(7, 1, 'CUR005', 'ENGENHARIA ELÉCTRICA', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(8, 1, 'CUR006', 'ENGENHARIA CIVIL', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(9, 1, 'CUR007', 'ARQUITECTURA E URBANISMO', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(10, 1, 'CUR008', 'CIÊNCIAS ECONÓMICAS E EMPRESARIAIS', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(11, 1, 'CUR009', 'CIÊNCIAS FARMACÊUTICAS', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(12, 1, 'CUR010', 'ADMINISTRAÇÃO PÚBLICA E GESTÃO DE CIDADE', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(13, 1, 'CUR011', 'GESTÃO DE RECURSOS HUMANOS', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(14, 1, 'CUR012', 'CIÊNCIAS DA COMPUTAÇÃO', 5, 1, '2026-07-02 12:57:39', '2026-07-02 12:57:39'),
(15, 1, 'CUR013', 'ANALISES CLÍNICAS', 5, 1, '2026-07-02 12:57:40', '2026-07-02 12:57:40'),
(16, 1, 'CUR014', 'CIÊNCIAS FARMACÊUTICAS OPÇÃO EM ANALISES CLÍNICAS', 5, 1, '2026-07-02 12:57:40', '2026-07-02 12:57:40'),
(17, 1, 'CUR015', 'DIREITO', 5, 1, '2026-07-02 12:57:40', '2026-07-02 12:57:40'),
(18, 1, 'CUR016', 'MEDICINA DENTÁRIA', 5, 1, '2026-07-02 12:57:40', '2026-07-02 12:57:40'),
(19, 1, 'CUR017', 'PSICOLOGIA CRIMINAL', 5, 1, '2026-07-02 12:57:40', '2026-07-02 12:57:40'),
(20, 1, 'CUR018', 'PSICOLOGIA DA EDUCAÇÃO', 5, 1, '2026-07-02 12:57:40', '2026-07-02 12:57:40'),
(21, 1, 'CUR019', 'PSICOLOGIA DA SAÚDE', 5, 1, '2026-07-02 12:57:40', '2026-07-02 12:57:40');

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplinas`
--

DROP TABLE IF EXISTS `disciplinas`;
CREATE TABLE `disciplinas` (
  `id` int(10) UNSIGNED NOT NULL,
  `grade_id` int(10) UNSIGNED DEFAULT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `turma_id` int(10) UNSIGNED DEFAULT NULL,
  `professor_id` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(200) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL COMMENT 'Código da disciplina, ex: MAT101',
  `ano_nivel` tinyint(3) UNSIGNED NOT NULL COMMENT 'Ano do curso onde é leccionada (1-5)',
  `semestre_num` tinyint(3) UNSIGNED NOT NULL COMMENT '1 ou 2',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Disciplinas por curso, ano e semestre';

-- --------------------------------------------------------

--
-- Estrutura da tabela `faculdades`
--

DROP TABLE IF EXISTS `faculdades`;
CREATE TABLE `faculdades` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL COMMENT 'Nome completo da faculdade',
  `sigla` varchar(20) NOT NULL COMMENT 'Sigla, ex: FEUP, FCUP',
  `ativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activa, 0=inactiva',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Faculdades da instituição (escalabilidade multi-faculdade)';

--
-- Extraindo dados da tabela `faculdades`
--

INSERT INTO `faculdades` (`id`, `nome`, `sigla`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'Faculdade de Ciências e Tecnologia', 'FCT', 1, '2026-07-02 09:08:47', '2026-07-02 09:08:47');

-- --------------------------------------------------------

--
-- Estrutura da tabela `grade_curricular`
--

DROP TABLE IF EXISTS `grade_curricular`;
CREATE TABLE `grade_curricular` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `ano_nivel` tinyint(3) UNSIGNED NOT NULL,
  `semestre_num` tinyint(3) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `ocorrencias`
--

DROP TABLE IF EXISTS `ocorrencias`;
CREATE TABLE `ocorrencias` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket` varchar(20) NOT NULL COMMENT 'Ex: RN-2026-000001 – gerado automaticamente',
  `estudante_nome` varchar(200) NOT NULL,
  `estudante_numero` varchar(30) NOT NULL COMMENT 'Número de estudante',
  `estudante_telefone` varchar(20) NOT NULL,
  `estudante_email` varchar(200) NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `turma_id` int(10) UNSIGNED NOT NULL,
  `semestre_id` int(10) UNSIGNED NOT NULL,
  `disciplina_id` int(10) UNSIGNED NOT NULL,
  `tipo_avaliacao` enum('1a_parcial','2a_parcial','exame','recurso') NOT NULL,
  `descricao` text NOT NULL COMMENT 'Descrição detalhada da ocorrência',
  `estado` enum('submetida','em_analise','encaminhada_professor','respondida_professor','em_revisao_coordenacao','resolvida','encerrada','cancelada') NOT NULL DEFAULT 'submetida',
  `observacao_oficial` text DEFAULT NULL COMMENT 'Visível ao estudante na consulta pública',
  `nota_interna` text DEFAULT NULL COMMENT 'Nota interna, não visível ao estudante',
  `resolvido_por` int(10) UNSIGNED DEFAULT NULL COMMENT 'Utilizador que resolveu',
  `resolvido_em` timestamp NULL DEFAULT NULL,
  `encaminhado_para` int(10) UNSIGNED DEFAULT NULL COMMENT 'Professor para quem foi encaminhado',
  `encaminhado_em` timestamp NULL DEFAULT NULL,
  `ip_origem` varchar(45) DEFAULT NULL COMMENT 'IP da submissão (IPv4/IPv6)',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ocorrências – núcleo do sistema com ciclo de vida completo';

-- --------------------------------------------------------

--
-- Estrutura da tabela `ocorrencias_historico`
--

DROP TABLE IF EXISTS `ocorrencias_historico`;
CREATE TABLE `ocorrencias_historico` (
  `id` int(10) UNSIGNED NOT NULL,
  `ocorrencia_id` int(10) UNSIGNED NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL COMMENT 'Estado antes da mudança',
  `estado_novo` varchar(50) NOT NULL COMMENT 'Novo estado após mudança',
  `observacao` text DEFAULT NULL COMMENT 'Nota sobre a mudança',
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Quem fez a mudança',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico completo de estados das ocorrências';

-- --------------------------------------------------------

--
-- Estrutura da tabela `professores`
--

DROP TABLE IF EXISTS `professores`;
CREATE TABLE `professores` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL,
  `bi` varchar(20) NOT NULL COMMENT 'Número do BI (usado para gerar email)',
  `telefone` varchar(20) NOT NULL,
  `email_pessoal` varchar(200) NOT NULL,
  `especialidade` varchar(150) NOT NULL,
  `fotografia` varchar(300) DEFAULT NULL COMMENT 'Caminho da fotografia (opcional)',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Professores com ligação automática a utilizador';

--
-- Extraindo dados da tabela `professores`
--

INSERT INTO `professores` (`id`, `usuario_id`, `nome`, `bi`, `telefone`, `email_pessoal`, `especialidade`, `fotografia`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(2, 4, 'Tilson', '0000000LG00', '987456321', 'tilson@gmail.com', 'Programação Web', NULL, 1, '2026-07-02 12:19:33', '2026-07-02 12:19:33'),
(3, 5, 'Xavier Benvindo', '1234567890', '912345678', 'xavierbenvindo@gmail.com', 'Redes de Computador', NULL, 1, '2026-07-02 23:58:20', '2026-07-02 23:58:20');

-- --------------------------------------------------------

--
-- Estrutura da tabela `respostas_professor`
--

DROP TABLE IF EXISTS `respostas_professor`;
CREATE TABLE `respostas_professor` (
  `id` int(10) UNSIGNED NOT NULL,
  `ocorrencia_id` int(10) UNSIGNED NOT NULL,
  `professor_id` int(10) UNSIGNED NOT NULL,
  `resposta` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Respostas dos professores às ocorrências';

-- --------------------------------------------------------

--
-- Estrutura da tabela `semestres`
--

DROP TABLE IF EXISTS `semestres`;
CREATE TABLE `semestres` (
  `id` int(10) UNSIGNED NOT NULL,
  `ano_letivo_id` int(10) UNSIGNED NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL COMMENT '1 ou 2',
  `nome` varchar(60) NOT NULL COMMENT 'Ex: 1º Semestre 2024/2025',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Semestres por ano letivo';

--
-- Extraindo dados da tabela `semestres`
--

INSERT INTO `semestres` (`id`, `ano_letivo_id`, `numero`, `nome`, `data_inicio`, `data_fim`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 2, 1, '1º Semestre 2025/2026', '2025-09-01', '2026-01-31', 0, '2026-07-02 09:08:47', '2026-07-02 09:08:47'),
(2, 2, 2, '2º Semestre 2025/2026', '2026-02-01', '2026-07-31', 1, '2026-07-02 09:08:47', '2026-07-02 09:08:47');

-- --------------------------------------------------------

--
-- Estrutura da tabela `turmas`
--

DROP TABLE IF EXISTS `turmas`;
CREATE TABLE `turmas` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `ano_letivo_id` int(10) UNSIGNED NOT NULL,
  `ano_nivel` tinyint(3) UNSIGNED NOT NULL COMMENT 'Ano do curso: 1 a 5',
  `codigo` varchar(10) NOT NULL COMMENT 'Código padronizado: 101,102,201...',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Turmas por curso e ano letivo com código padronizado (101,102...)';

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL COMMENT 'Nome completo',
  `email` varchar(200) NOT NULL COMMENT 'Email único de autenticação',
  `password` varchar(255) NOT NULL COMMENT 'Hash bcrypt',
  `perfil` enum('super_admin','coordenador','professor') NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `primeiro_login` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=deve alterar senha',
  `senha_alterada` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=senha já foi alterada',
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `tentativas_login` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Tentativas falhadas consecutivas',
  `bloqueado_ate` timestamp NULL DEFAULT NULL COMMENT 'Bloqueio temporário por tentativas excessivas',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela única de autenticação para todos os perfis do sistema';

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `password`, `perfil`, `ativo`, `primeiro_login`, `senha_alterada`, `ultimo_login`, `tentativas_login`, `bloqueado_ate`, `criado_em`, `atualizado_em`) VALUES
(1, 'Super Administrador', 'admin@regnotas.ao', '$2y$12$zpU91IRGPlHTf78/nbE5ueDnnn7ZzabhxaImyqKiiRPZRB9U9oxBO', 'super_admin', 1, 0, 1, '2026-07-03 02:45:49', 0, NULL, '2026-07-02 09:08:47', '2026-07-03 02:45:49'),
(2, 'Professor Teste', 'professor@regnotas.ao', '$2y$10$Sbe9Hj.PzG.6mC1PZ.oVoeGfX.1sS.Z9eFzH8YpL2M0gYFfS.H3qK', 'professor', 1, 1, 0, NULL, 2, NULL, '2026-07-02 09:33:20', '2026-07-03 02:11:43'),
(4, 'Tilson', '0000000LG00_prof@RegNota.com', '$2y$12$tBAzIJ.wbrflKhkYMUOip.Fy3xxzj0dC4818y2feaCDOwBOSeALQG', 'professor', 1, 0, 1, '2026-07-02 12:23:00', 0, NULL, '2026-07-02 12:19:33', '2026-07-02 12:24:59'),
(5, 'Xavier Benvindo', '1234567890_prof@RegNota.com', '$2y$12$muguXrpviZyq9YRfJU6SjOHaQNfw3h63m5vnv2pMV51nk7K2v/hsC', 'professor', 1, 1, 0, NULL, 0, NULL, '2026-07-02 23:58:20', '2026-07-02 23:58:20'),
(6, 'Ribas', '0000000LG00_coord@RegNota.com', '$2y$12$.TjzMQwaqZN4M3eVbR8f7OPIryv4wuWsEk5DzOQuiRrHaD92MSPlK', 'coordenador', 1, 0, 1, '2026-07-03 02:30:06', 0, NULL, '2026-07-03 02:09:32', '2026-07-03 02:30:06');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `anos_letivos`
--
ALTER TABLE `anos_letivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ano_letivo_nome` (`nome`);

--
-- Índices para tabela `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_usuario` (`usuario_id`),
  ADD KEY `idx_audit_acao` (`acao`),
  ADD KEY `idx_audit_tabela` (`tabela`,`registo_id`),
  ADD KEY `idx_audit_criado` (`criado_em`);

--
-- Índices para tabela `contador_tickets`
--
ALTER TABLE `contador_tickets`
  ADD PRIMARY KEY (`ano`);

--
-- Índices para tabela `coordenadores`
--
ALTER TABLE `coordenadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_coord_bi` (`bi`),
  ADD UNIQUE KEY `uk_coord_curso` (`curso_id`),
  ADD UNIQUE KEY `uk_coord_usuario` (`usuario_id`);

--
-- Índices para tabela `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_curso_codigo` (`codigo`),
  ADD KEY `fk_curso_faculdade` (`faculdade_id`);

--
-- Índices para tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_disc_curso` (`curso_id`),
  ADD KEY `idx_disc_prof` (`professor_id`),
  ADD KEY `idx_disc_nivel` (`ano_nivel`,`semestre_num`),
  ADD KEY `fk_disc_grade` (`grade_id`),
  ADD KEY `fk_disc_turma` (`turma_id`);

--
-- Índices para tabela `faculdades`
--
ALTER TABLE `faculdades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_faculdade_sigla` (`sigla`);

--
-- Índices para tabela `grade_curricular`
--
ALTER TABLE `grade_curricular`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grade_curso` (`curso_id`);

--
-- Índices para tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ticket` (`ticket`),
  ADD KEY `idx_ocorr_estado` (`estado`),
  ADD KEY `idx_ocorr_curso` (`curso_id`),
  ADD KEY `idx_ocorr_turma` (`turma_id`),
  ADD KEY `idx_ocorr_disciplina` (`disciplina_id`),
  ADD KEY `idx_ocorr_estudante_nome` (`estudante_nome`),
  ADD KEY `idx_ocorr_estudante_num` (`estudante_numero`),
  ADD KEY `idx_ocorr_criado` (`criado_em`),
  ADD KEY `fk_ocorr_semestre` (`semestre_id`),
  ADD KEY `fk_ocorr_resolvido` (`resolvido_por`),
  ADD KEY `fk_ocorr_encaminhado` (`encaminhado_para`);

--
-- Índices para tabela `ocorrencias_historico`
--
ALTER TABLE `ocorrencias_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_ocorr` (`ocorrencia_id`),
  ADD KEY `fk_hist_usuario` (`usuario_id`);

--
-- Índices para tabela `professores`
--
ALTER TABLE `professores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prof_bi` (`bi`),
  ADD UNIQUE KEY `uk_prof_usuario` (`usuario_id`);

--
-- Índices para tabela `respostas_professor`
--
ALTER TABLE `respostas_professor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resp_ocorr` (`ocorrencia_id`),
  ADD KEY `fk_resp_prof` (`professor_id`);

--
-- Índices para tabela `semestres`
--
ALTER TABLE `semestres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_semestre` (`ano_letivo_id`,`numero`);

--
-- Índices para tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_turma` (`curso_id`,`ano_letivo_id`,`codigo`),
  ADD KEY `idx_turma_nivel` (`ano_nivel`),
  ADD KEY `fk_turma_ano` (`ano_letivo_id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_email` (`email`),
  ADD KEY `idx_usuario_perfil` (`perfil`),
  ADD KEY `idx_usuario_ativo` (`ativo`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anos_letivos`
--
ALTER TABLE `anos_letivos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `coordenadores`
--
ALTER TABLE `coordenadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=576;

--
-- AUTO_INCREMENT de tabela `faculdades`
--
ALTER TABLE `faculdades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `grade_curricular`
--
ALTER TABLE `grade_curricular`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=288;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ocorrencias_historico`
--
ALTER TABLE `ocorrencias_historico`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `professores`
--
ALTER TABLE `professores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `respostas_professor`
--
ALTER TABLE `respostas_professor`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `semestres`
--
ALTER TABLE `semestres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_audit_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `coordenadores`
--
ALTER TABLE `coordenadores`
  ADD CONSTRAINT `fk_coord_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_coord_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `fk_curso_faculdade` FOREIGN KEY (`faculdade_id`) REFERENCES `faculdades` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD CONSTRAINT `fk_disc_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disc_grade` FOREIGN KEY (`grade_id`) REFERENCES `grade_curricular` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_disc_prof` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disc_turma` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `grade_curricular`
--
ALTER TABLE `grade_curricular`
  ADD CONSTRAINT `fk_grade_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD CONSTRAINT `fk_ocorr_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `fk_ocorr_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`),
  ADD CONSTRAINT `fk_ocorr_encaminhado` FOREIGN KEY (`encaminhado_para`) REFERENCES `professores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ocorr_resolvido` FOREIGN KEY (`resolvido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ocorr_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`id`),
  ADD CONSTRAINT `fk_ocorr_turma` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`);

--
-- Limitadores para a tabela `ocorrencias_historico`
--
ALTER TABLE `ocorrencias_historico`
  ADD CONSTRAINT `fk_hist_ocorr` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `professores`
--
ALTER TABLE `professores`
  ADD CONSTRAINT `fk_prof_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `respostas_professor`
--
ALTER TABLE `respostas_professor`
  ADD CONSTRAINT `fk_resp_ocorr` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resp_prof` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `semestres`
--
ALTER TABLE `semestres`
  ADD CONSTRAINT `fk_semestre_ano` FOREIGN KEY (`ano_letivo_id`) REFERENCES `anos_letivos` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `fk_turma_ano` FOREIGN KEY (`ano_letivo_id`) REFERENCES `anos_letivos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_turma_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
