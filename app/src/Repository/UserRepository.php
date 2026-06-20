<?php

/**
 * User repository.
 */

namespace App\Repository;

use App\Entity\Bug;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/** * @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @param PasswordAuthenticatedUserInterface $user              User
     * @param string                             $newHashedPassword New password
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

    /**
     * Count admins.
     *
     * @return int Admin count
     */
    public function countAdmins(): int
    {
        return (int) $this->createQueryBuilder('user')
            ->select('COUNT(user.id)')
            ->where('user.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Query all users.
     *
     * @return QueryBuilder Query
     */
    public function queryAll(): QueryBuilder
    {
        return $this->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC');
    }

    /**
     * FInd user stats.
     *
     * @param int $id User id
     *
     * @return array Stats
     */
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

        $bugCount = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(bug.id)')
            ->from(Bug::class, 'bug')
            ->where('bug.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $commentCount = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(comment.id)')
            ->from(Comment::class, 'comment')
            ->where('comment.author = :user')
            ->setParameter('user', $user)->getQuery()
            ->getSingleScalarResult();

        return ['user' => $user, 'bugCount' => (int) $bugCount, 'commentCount' => (int) $commentCount];
    }

    /**
     * Find admins.
     *
     * @return array Admins
     */
    public function findAdmins(): array
    {
        return $this->createQueryBuilder('user')
            ->where('user.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()->getResult();
    }

    /**
     * Unassign bug.
     *
     * @param User $user User to remove
     */
    public function unassignBugs(User $user): void
    {
        $this->getEntityManager()->createQuery(
            'UPDATE App\Entity\Bug bug
            SET bug.assignedTo = NULL
            WHERE bug.assignedTo = :user'
        )
            ->setParameter('user', $user)
            ->execute();
    }

    /**
     * Save entity.
     *
     * @param User $user User entity
     **/
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
        $em->createQuery('UPDATE App\Entity\Bug b SET b.assignedTo = NULL WHERE b.assignedTo = :user')
            ->setParameter('user', $user)
            ->execute();

        $em->createQuery('DELETE FROM App\Entity\Comment c WHERE c.author = :user')
            ->setParameter('user', $user)
            ->execute();

        $em->createQuery('DELETE FROM App\Entity\Comment c WHERE c.bug IN ( SELECT b FROM App\Entity\Bug b WHERE b.author = :user )')
            ->setParameter('user', $user)
            ->execute();

        $em->createQuery('DELETE FROM App\Entity\Bug b WHERE b.author = :user')
            ->setParameter('user', $user)
            ->execute();

        $em->remove($user);
        $em->flush();
    }
}
