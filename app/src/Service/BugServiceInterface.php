<?php
/**
 * Bug service interface.
 */

namespace App\Service;

use App\Entity\Bug;
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
    public function getPaginatedList(int $page): PaginationInterface;

}
