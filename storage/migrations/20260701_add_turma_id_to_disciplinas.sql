-- Migration: Adicionar turma_id a disciplinas (Safe Version)
-- Objetivo: Implementar a nova regra de negócio onde cada disciplina deve estar associada a uma turma.

-- 1. Adicionar a coluna como NULL inicialmente para permitir a migração de dados
ALTER TABLE `disciplinas` 
ADD COLUMN `turma_id` INT UNSIGNED NULL AFTER `curso_id`;

-- 2. Preencher as disciplinas existentes com uma turma válida baseada no curso e nível
-- Tentamos associar cada disciplina à primeira turma encontrada que combine com seu curso e nível.
UPDATE `disciplinas` d
JOIN `turmas` t ON d.curso_id = t.curso_id AND d.ano_nivel = t.ano_nivel
SET d.turma_id = t.id;

-- 3. Agora que os dados foram migrados, tornamos a coluna NOT NULL
ALTER TABLE `disciplinas` 
MODIFY COLUMN `turma_id` INT UNSIGNED NOT NULL;

-- 4. Adicionar a constraint de chave estrangeira
ALTER TABLE `disciplinas` 
ADD CONSTRAINT `fk_disc_turma` 
FOREIGN KEY (`turma_id`) REFERENCES `turmas`(`id`) 
ON DELETE RESTRICT ON UPDATE CASCADE;
