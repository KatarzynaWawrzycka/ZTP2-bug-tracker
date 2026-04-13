<?php

/**
 * Bug service interface.
 */

namespace App\Service;

use App\Entity\Bug;
use App\Entity\User;
use App\Entity\Enum\BugStatus;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface BugServiceInterface.
 */
interface BugServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface<string, mixed> Paginated list
     */
    public function getPaginatedList(int $page, ?User $author = null): PaginationInterface;

    public function changeStatus(Bug $bug, BugStatus $targetStatus): void;

    public function assign(Bug $bug, ?User $user): void;

    /**
     * Save entity.
     *
     * @param Bug $bug Bug entity
     */
    public function save(Bug $bug): void;

    /**
     * Delete entity.
     *
     * @param Bug $bug Bug entity
     */
    public function delete(Bug $bug): void;
}
