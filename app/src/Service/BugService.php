<?php

/**
 * Bug service.
 */

namespace App\Service;

use App\Dto\BugListFiltersDto;
use App\Dto\BugListInputFiltersDto;
use App\Entity\Bug;
use App\Entity\Enum\BugStatus;
use App\Entity\User;
use App\Repository\BugRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class BugService.
 */
class BugService implements BugServiceInterface
{
    /**
     * Items per page.
     *
     * Use constants to define configuration options that rarely change instead
     * of specifying them in app/config/config.yml.
     * See https://symfony.com/doc/current/best_practices.html#configuration
     *
     * @varant int
     */
    private const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param CategoryServiceInterface $categoryService Category service
     * @param TagServiceInterface      $tagService      Tag service
     * @param BugRepository            $bugRepository   Bug repository
     * @param PaginatorInterface       $paginator       Paginator
     */
    public function __construct(private readonly CategoryServiceInterface $categoryService, private readonly TagServiceInterface $tagService, private readonly BugRepository $bugRepository, private readonly PaginatorInterface $paginator)
    {
    }

    /**
     * Get paginated list.
     *
     * @param int                    $page    Page number
     * @param BugListInputFiltersDto $filters Filters
     *
     * @return PaginationInterface<SlidingPagination> Paginated list
     */
    public function getPaginatedList(int $page, BugListInputFiltersDto $filters): PaginationInterface
    {
        $filters = $this->prepareFilters($filters);

        return $this->paginator->paginate(
            $this->bugRepository->queryAll($filters),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['bug.id', 'bug.createdAt', 'bug.updatedAt', 'bug.title', 'bug.description', 'category.title', 'bug.status', 'bug.assignedTo'],
                'defaultSortFieldName' => 'bug.updatedAt',
                'defaultSortDirection' => 'desc',
            ]
        );
    }

    public function changeStatus(Bug $bug, BugStatus $targetStatus): void
    {
        $current = $bug->getStatusEnum();

        if ($current === $targetStatus) {
            return;
        }

        $allowedTransitions = match ($current) {
            BugStatus::OPEN => [
                BugStatus::CLOSED,
            ],

            BugStatus::CLOSED => [
                BugStatus::OPEN,
                BugStatus::ARCHIVED,
            ],

            BugStatus::ARCHIVED => [
                BugStatus::OPEN,
            ],
        };

        if (!in_array($targetStatus, $allowedTransitions, true)) {
            throw new \LogicException(sprintf('Invalid status transition from %s to %s', $current?->name, $targetStatus->name));
        }

        $bug->setStatusEnum($targetStatus);

        $this->bugRepository->save($bug);
    }

    public function assign(Bug $bug, ?User $user): void
    {
        $bug->setAssignedTo($user);
        $this->bugRepository->save($bug);
    }

    /**
     * Save entity.
     *
     * @param Bug $bug Bug entity
     */
    public function save(Bug $bug): void
    {
        $this->bugRepository->save($bug);
    }

    /**
     * Delete entity.
     *
     * @param Bug $bug Bug entity
     */
    public function delete(Bug $bug): void
    {
        $this->bugRepository->delete($bug);
    }

    /**
     * Prepare filters for the bug list.
     *
     * @param BugListInputFiltersDto $filters Raw filters from request
     *
     * @return BugListFiltersDto Result filters
     */
    private function prepareFilters(BugListInputFiltersDto $filters): BugListFiltersDto
    {
        return new BugListFiltersDto(
            null !== $filters->categoryId ? $this->categoryService->findOneById($filters->categoryId) : null,
            null !== $filters->tagId ? $this->tagService->findOneById($filters->tagId) : null
        );
    }
}
