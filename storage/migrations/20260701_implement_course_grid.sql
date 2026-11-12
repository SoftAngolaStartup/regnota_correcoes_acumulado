-- Migration: Implement Course Grid (Grade Curricular)
-- Date: 2026-07-01

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create the Grade Curricular table
CREATE TABLE IF NOT EXISTS `grade_curricular` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `curso_id`      INT UNSIGNED    NOT NULL,
    `ano_nivel`     TINYINT UNSIGNED NOT NULL,
    `semestre_num`  TINYINT UNSIGNED NOT NULL,
    `nome`          VARCHAR(200)    NOT NULL,
    `codigo`        VARCHAR(20)     NULL,
    `ativo`         TINYINT(1)      NOT NULL DEFAULT 1,
    `criado_em`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_grade_curso` (`curso_id`),
    INDEX `idx_grade_nivel` (`ano_nivel`, `semestre_num`),
    CONSTRAINT `fk_grade_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Migrate existing disciplines to the grade
INSERT INTO `grade_curricular` (curso_id, ano_nivel, semestre_num, nome, codigo)
SELECT DISTINCT curso_id, ano_nivel, semestre_num, nome, codigo 
FROM `disciplinas`;

-- 3. Refactor the disciplinas table
ALTER TABLE `disciplinas` ADD COLUMN `grade_id` INT UNSIGNED AFTER `id`;

UPDATE `disciplinas` d 
JOIN `grade_curricular` g ON d.curso_id = g.curso_id 
    AND d.ano_nivel = g.ano_nivel 
    AND d.semestre_num = g.semestre_num 
    AND d.nome = g.nome 
SET d.grade_id = g.id;

ALTER TABLE `disciplinas` MODIFY COLUMN `grade_id` INT UNSIGNED NOT NULL;
ALTER TABLE `disciplinas` ADD CONSTRAINT `fk_disc_grade` FOREIGN KEY (`grade_id`) REFERENCES `grade_curricular`(`id`) ON DELETE CASCADE;

ALTER TABLE `disciplinas` DROP COLUMN `nome`;
ALTER TABLE `disciplinas` DROP COLUMN `codigo`;
ALTER TABLE `disciplinas` MODIFY COLUMN `professor_id` INT UNSIGNED NULL;

SET FOREIGN_KEY_CHECKS = 1;
