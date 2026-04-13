<?php

namespace App\Repository;

use App\Entity\Bug;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function countAdmins(): int
    {
        return (int) $this->createQueryBuilder('user')
            ->select('COUNT(user.id)')
            ->where('user.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function queryAll(): QueryBuilder
    {
        return $this->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC');
    }

    public function findWithStats(int $id): array
    {
        $user = $this->createQueryBuilder('user')
            ->where('user.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            return [];
        }

        $bugCount = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(bug.id)')
            ->from(Bug::class, 'bug')
            ->where('bug.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $commentCount = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(comment.id)')
            ->from(Comment::class, 'comment')
            ->where('comment.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'user' => $user,
            'bugCount' => (int) $bugCount,
            'commentCount' => (int) $commentCount,
        ];
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
     * @param User $user User entity
     */
    public function save(User $user): void
    {
        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();
    }

    /**
     * Delete entity.
     *
     * @param User $user User entity
     */
    public function delete(User $user): void
    {
        $em = $this->getEntityManager();

        $em->createQuery(
            'DELETE FROM App\Entity\Comment c WHERE c.author = :user'
        )
            ->setParameter('user', $user)
            ->execute();

        $em->createQuery(
            'DELETE FROM App\Entity\Comment c WHERE c.bug IN (
            SELECT b FROM App\Entity\Bug b WHERE b.author = :user
        )'
        )
            ->setParameter('user', $user)
            ->execute();

        $em->createQuery(
            'DELETE FROM App\Entity\Bug b WHERE b.author = :user'
        )
            ->setParameter('user', $user)
            ->execute();

        $em->remove($user);
        $em->flush();
    }
}
