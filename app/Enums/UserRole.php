<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Mayor = 'mayor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Mayor => 'Prefeito',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'red',
            self::Mayor => 'blue',
        };
    }
}
