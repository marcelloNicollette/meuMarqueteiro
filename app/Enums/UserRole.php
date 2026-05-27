<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Mayor = 'mayor';
    case Secretary = 'secretary';
    case Advisor = 'advisor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Mayor => 'Prefeito',
            self::Secretary => 'Secretário',
            self::Advisor => 'Assessor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'red',
            self::Mayor => 'blue',
            self::Secretary => 'green',
            self::Advisor => 'yellow',
        };
    }
}
