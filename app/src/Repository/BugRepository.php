<?php

namespace App\Repository;

use App\Entity\Bug;
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

    /**
     * Query all records.
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(): QueryBuilder
    {
        return $this->createQueryBuilder('bug')
            ->select('bug', 'category')
            ->join('bug.category', 'category');
    }

    // ODKOMENTUJ JAK DODASZ TAGI
    //    /**
    //     * Query all records.
    //     *
    //     * @return QueryBuilder Query builder
    //     */
    //    public function queryAll(): QueryBuilder
    //    {
    //        return $this->createQueryBuilder('task')
    //            ->select('task', 'category', 'tags')
    //            ->join('task.category', 'category')
    //            ->leftJoin('task.tags', 'tags');
    //    }
}
