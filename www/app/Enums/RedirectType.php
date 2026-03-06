<?php

namespace App\Enums;

enum RedirectType: string
{
    case PERMANENT = '301';
    case TEMPORARY = '302';
    case TEMPORARY_PRESERVE = '307';
    case PERMANENT_PRESERVE = '308';

    public function label(): string
    {
        return match ($this) {
            self::PERMANENT => '301 Permanent',
            self::TEMPORARY => '302 Temporary',
            self::TEMPORARY_PRESERVE => '307 Temporary (preserve method)',
            self::PERMANENT_PRESERVE => '308 Permanent (preserve method)',
        };
    }

    public function statusCode(): int
    {
        return (int) $this->value;
    }
}
