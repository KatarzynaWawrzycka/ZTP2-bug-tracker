<?php

namespace App\Repository;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bug>
 */
class BugRepository extends ServiceEntityRepository
{
    /**
     * Items per page.
     *
     * Use constants to define configuration options that rarely change instead
     * of specifying them in configuration files.
     * See https://symfony.com/doc/current/best_practices.html#configuration
     *
     * @var int
     */
    public const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bug::class);
    }

    public function queryAll(): QueryBuilder
    {
        return $this->createQueryBuilder('bug')
            ->select(
                'partial bug.{id, createdAt, updatedAt, title, description, status, assignedTo}',
                'partial category.{id, title}',
                'partial tags.{id, title}'
            )
            ->join('bug.category', 'category')
            ->leftJoin('bug.tags', 'tags');
    }

    public function queryByAuthor(User $author): QueryBuilder
    {
        return $this->createQueryBuilder('bug')
            ->select(
                'partial bug.{id, createdAt, updatedAt, title, description, status, assignedTo}',
                'partial category.{id, title}',
                'partial tags.{id, title}'
            )
            ->join('bug.category', 'category')
            ->leftJoin('bug.tags', 'tags')
            ->andWhere('bug.author = :author')
            ->setParameter('author', $author);
    }

    /**
     * Count bugs by category.
     *
     * @param Category $category Category
     *
     * @return int Number of bugs in category
     */
    public function countByCategory(Category $category): int
    {
        $qb = $this->createQueryBuilder('bug');

        return $qb->select($qb->expr()->countDistinct('bug.id'))
            ->where('bug.category = :category')
            ->setParameter(':category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAdmins(): array
    {
        return $this->createQueryBuilder('user')
            ->where('user.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save entity.
     *
     * @param Bug $bug Bug entity
     */
    public function save(Bug $bug): void
    {
        $this->getEntityManager()->persist($bug);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete entity.
     *
     * @param Bug $bug Bug entity
     */
    public function delete(Bug $bug): void
    {
        $this->getEntityManager()->remove($bug);
        $this->getEntityManager()->flush();
    }
}
