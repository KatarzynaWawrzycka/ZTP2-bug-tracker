<?php

/**
 * BUg status.
 */

namespace App\Entity\Enum;

/**
 * Enum BugStatus.
 */
enum BugStatus: int
{
    case OPEN = 0;
    case CLOSED = 1;
    case ARCHIVED = 2;

    /**
     * Get the status label.
     *
     * @return string Status label
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'open',
            self::CLOSED => 'closed',
            self::ARCHIVED => 'archived',
        };
    }
}
