<?php
/**
 * ============================================================
 * app/Helpers/Resposta.php
 * Utilitários: Sanitização, Labels de domínio, Formatação
 * ============================================================
 */

namespace App\Helpers;

// ── Sanitização de dados ─────────────────────────────────────

class Sanitizar
{
    /** Escapa caracteres HTML para output seguro (anti-XSS) */
    public static function html(?string $valor): string
    {
        return htmlspecialchars(
            (string)($valor ?? ''),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }

    /** Remove espaços e tags HTML de um input de texto */
    public static function texto(?string $valor): string
    {
        return trim(strip_tags((string)($valor ?? '')));
    }

    /** Garante um inteiro positivo seguro */
    public static function inteiro($valor): int
    {
        return max(0, (int)$valor);
    }

    /** Valida e-mail */
    public static function email(?string $email): string|false
    {
        return filter_var(trim((string)($email ?? '')), FILTER_VALIDATE_EMAIL) ?: false;
    }
}

// ── Labels e mapeamentos do domínio ──────────────────────────

class Etiqueta
{
    /** Tipos de avaliação */
    public static function tipoAvaliacao(string $tipo): string
    {
        return match($tipo) {
            '1a_parcial' => '1ª Parcial',
            '2a_parcial' => '2ª Parcial',
            'exame'      => 'Exame',
            'recurso'    => 'Recurso',
            default      => $tipo,
        };
    }

    /** Todos os tipos de avaliação */
    public static function todosTipos(): array
    {
        return [
            '1a_parcial' => '1ª Parcial',
            '2a_parcial' => '2ª Parcial',
            'exame'      => 'Exame',
            'recurso'    => 'Recurso',
        ];
    }

    /** Estados possíveis de uma ocorrência */
    public static function estado(string $estado): string
    {
        return match($estado) {
            'submetida'              => 'Submetida',
            'em_analise'             => 'Em Análise',
            'encaminhada_professor'  => 'Encaminhada ao Professor',
            'respondida_professor'   => 'Respondida pelo Professor',
            'em_revisao_coordenacao' => 'Em Revisão da Coordenação',
            'resolvida'              => 'Resolvida',
            'encerrada'              => 'Encerrada',
            'cancelada'              => 'Cancelada',
            default                  => $estado,
        };
    }

    /** Todos os estados */
    public static function todosEstados(): array
    {
        return [
            'submetida'              => 'Submetida',
            'em_analise'             => 'Em Análise',
            'encaminhada_professor'  => 'Encaminhada ao Professor',
            'respondida_professor'   => 'Respondida pelo Professor',
            'em_revisao_coordenacao' => 'Em Revisão da Coordenação',
            'resolvida'              => 'Resolvida',
            'encerrada'              => 'Encerrada',
            'cancelada'              => 'Cancelada',
        ];
    }

    /** Classe CSS do badge de estado */
    public static function badgeEstado(string $estado): string
    {
        return match($estado) {
            'submetida'              => 'badge-submetida',
            'em_analise'             => 'badge-analise',
            'encaminhada_professor'  => 'badge-encaminhada',
            'respondida_professor'   => 'badge-respondida',
            'em_revisao_coordenacao' => 'badge-revisao',
            'resolvida'              => 'badge-resolvida',
            'encerrada'              => 'badge-encerrada',
            'cancelada'              => 'badge-cancelada',
            default                  => 'badge-secundario',
        };
    }

    /** Ícone Bootstrap Icons para o estado */
    public static function iconeEstado(string $estado): string
    {
        return match($estado) {
            'submetida'              => 'bi-send',
            'em_analise'             => 'bi-hourglass-split',
            'encaminhada_professor'  => 'bi-arrow-right-circle',
            'respondida_professor'   => 'bi-chat-left-text',
            'em_revisao_coordenacao' => 'bi-eye',
            'resolvida'              => 'bi-check-circle-fill',
            'encerrada'              => 'bi-lock-fill',
            'cancelada'              => 'bi-x-circle-fill',
            default                  => 'bi-circle',
        };
    }

    /** Label do perfil de utilizador */
    public static function perfil(string $perfil): string
    {
        return match($perfil) {
            'super_admin'  => 'Super Administrador',
            'coordenador'  => 'Coordenador',
            'professor'    => 'Professor',
            default        => $perfil,
        };
    }

    /** Label do ano de nível */
    public static function anoNivel(int $nivel): string
    {
        return match($nivel) {
            1 => '1º Ano', 2 => '2º Ano', 3 => '3º Ano',
            4 => '4º Ano', 5 => '5º Ano',
            default => $nivel . 'º Ano',
        };
    }
}

// ── Formatação de datas e números ────────────────────────────

class Formatar
{
    /** Formata data: dd/mm/aaaa */
    public static function data(?string $data): string
    {
        if (!$data) return '—';
        return date('d/m/Y', strtotime($data));
    }

    /** Formata data e hora: dd/mm/aaaa HH:MM */
    public static function dataHora(?string $dt): string
    {
        if (!$dt) return '—';
        return date('d/m/Y H:i', strtotime($dt));
    }

    /** Tempo relativo: "há 5 min", "há 2 dias" */
    public static function tempoRelativo(?string $dt): string
    {
        if (!$dt) return '—';
        $diff = time() - strtotime($dt);
        if ($diff < 60)      return 'agora mesmo';
        if ($diff < 3600)    return 'há ' . floor($diff / 60) . ' min';
        if ($diff < 86400)   return 'há ' . floor($diff / 3600) . 'h';
        if ($diff < 2592000) return 'há ' . floor($diff / 86400) . ' dias';
        return self::data($dt);
    }

    /** Número com zeros à esquerda */
    public static function numeroComZeros(int $num, int $casas = 6): string
    {
        return str_pad((string)$num, $casas, '0', STR_PAD_LEFT);
    }
}
