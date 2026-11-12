<?php
/**
 * app/Helpers/Etiqueta.php
 * Etiquetas e formatação de estados, perfis e tipos.
 */
namespace App\Helpers;

class Etiqueta
{
    public static function perfil(string $perfil): string
    {
        return match($perfil) {
            'super_admin' => 'Super Admin',
            'coordenador' => 'Coordenador',
            'professor'   => 'Professor',
            default       => 'Utilizador',
        };
    }

    public static function tipoAvaliacao(string $tipo): string
    {
        return match($tipo) {
            'prova'      => 'Prova',
            'trabalho'   => 'Trabalho',
            'participacao'=> 'Participação',
            'outro'      => 'Outro',
            default      => $tipo,
        };
    }

    public static function todosTipos(): array
    {
        return [
            'prova'       => 'Prova',
            'trabalho'    => 'Trabalho',
            'participacao'=> 'Participação',
            'outro'       => 'Outro',
        ];
    }

    public static function anoNivel(int $n): string
    {
        return $n . 'º Ano';
    }

    public static function badgeEstado(string $estado): string
    {
        return 'badge-' . $estado;
    }

    public static function estado(string $estado): string
    {
        return match($estado) {
            'submetida'              => 'Submetida',
            'em_analise'             => 'Em Análise',
            'encaminhada_professor'  => 'Encaminhada ao Professor',
            'respondida_professor'   => 'Respondida pelo Professor',
            'em_revisao_coordenacao' => 'Em Revisão',
            'resolvida'              => 'Resolvida',
            'encerrada'              => 'Encerrada',
            'cancelada'              => 'Cancelada',
            default                  => $estado,
        };
    }

    public static function iconeEstado(string $estado): string
    {
        return match($estado) {
            'submetida'              => 'bi-send',
            'em_analise'             => 'bi-hourglass',
            'encaminhada_professor'  => 'bi-arrow-right-circle',
            'respondida_professor'   => 'bi-chat-left-text',
            'em_revisao_coordenacao' => 'bi-clipboard-check',
            'resolvida'              => 'bi-check-circle',
            'encerrada'              => 'bi-x-circle',
            'cancelada'              => 'bi-x-circle-fill',
            default                  => 'bi-question-circle',
        };
    }
}
