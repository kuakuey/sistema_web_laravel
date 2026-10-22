<?php

namespace App\Support;

class Texto
{
    public static function titulo(?string $texto): string
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    public static function email(?string $email): string
    {
        return mb_strtolower(trim((string) $email), 'UTF-8');
    }
}
