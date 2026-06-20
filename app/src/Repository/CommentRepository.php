<?php

/**
 * Comment Repository.
 */

namespace App\Repository;

use App\Entity\Bug;
use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CommentRepository.
 */
class CommentRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Find all comment assigned to bug.
     *
     * @param Bug $bug Bug entity
     *
     * @return array Comments assigned to bug
     */
    public function findByBug(Bug $bug): array
    {
        return $this->createQueryBuilder('comment')
            ->andWhere('comment.bug = :bug')
            ->setParameter('bug', $bug)
            ->orderBy('comment.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save entity.
     *
     * @param Comment $comment Comment entity
     */
    public function save(Comment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete entity.
     *
     * @param Comment $comment Comment entity
     */
    public function delete(Comment $comment): void
    {
        $this->getEntityManager()->remove($comment);
        $this->getEntityManager()->flush();
    }
}
