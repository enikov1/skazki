<?php

namespace App\Enums;

enum EditorialStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Review => 'На проверке',
            self::Published => 'Опубликовано',
            self::Archived => 'В архиве',
        };
    }

    public static function labelFor(?string $status): string
    {
        return self::tryFrom($status ?? '')?->label() ?? 'Неизвестный статус';
    }
}
