<?php
namespace App\Entity\Enum;

enum BugStatus: int
{
    case OPEN = 0;
    case CLOSED = 1;
    case ARCHIVED = 2;

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'label.status_open',
            self::CLOSED => 'label.status_closed',
            self::ARCHIVED => 'label.status_archived',
        };
    }
}
