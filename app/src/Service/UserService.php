<?php

/**
 * User service.
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserService.
 */
class UserService implements UserServiceInterface
{
    private const PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param UserRepository              $userRepository User repository
     * @param PaginatorInterface          $paginator      Paginator
     * @param UserPasswordHasherInterface $passwordHasher Password hasher
     */
    public function __construct(private readonly UserRepository $userRepository, private readonly PaginatorInterface $paginator, private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->userRepository->queryAll(),
            $page,
            self::PER_PAGE
        );
    }

    /**
     * Get single user details.
     *
     * @param int $id User identifier
     *
     * @return User|null User entity
     */
    public function getUserDetails(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Count all admin users.
     *
     * @return int Number of admins
     */
    public function countAdmins(): int
    {
        return $this->userRepository->countAdmins();
    }

    /**
     * Find user id for stats.
     *
     * @param int $id User id
     *
     * @return array Stats
     */
    public function findWithStats(int $id): array
    {
        return $this->userRepository->findWithStats($id);
    }

    /**
     * Toggle ROLE_ADMIN for user.
     *
     * @param User $user User entity
     *
     * @throws \LogicException When trying to remove admin role from the last admin
     */
    public function toggleAdminRole(User $user): void
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            if ($this->countAdmins() <= 1) {
                throw new \LogicException('You are the last admin.');
            }

            $this->userRepository->unassignBugs($user);

            $roles = array_filter($roles, fn ($r) => 'ROLE_ADMIN' !== $r);
        } else {
            $roles[] = 'ROLE_ADMIN';
        }

        $user->setRoles(array_values(array_unique($roles)));
        $this->userRepository->save($user);
    }

    /**
     * Delete user.
     *
     * @param User $user User entity
     *
     * @throws \LogicException When trying to delete the last admin
     */
    public function delete(User $user): void
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true) && $this->countAdmins() <= 1) {
            throw new \LogicException('You are the last admin.');
        }

        $this->userRepository->delete($user);
    }

    /**
     * Change password.
     *
     * @param User   $user          User entity
     * @param string $plainPassword New password
     */
    public function changePassword(User $user, string $plainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $this->userRepository->save($user);
    }

    /**
     * Change email.
     *
     * @param User   $user  User email
     * @param string $email New email
     */
    public function changeEmail(User $user, string $email): void
    {
        $user->setEmail($email);
        $this->userRepository->save($user);
    }

    /**
     * Find admins.
     *
     * @return array Admins
     */
    public function findAdmins(): array
    {
        return $this->userRepository->findAdmins();
    }

    /**
     * Register new user.
     *
     * @param User   $user          User entity
     * @param string $plainPassword Password
     */
    public function register(User $user, string $plainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        $this->userRepository->save($user);
    }

    /**
     * Save entity.
     *
     * @param User $user User entity
     */
    public function save(User $user): void
    {
        $this->userRepository->save($user);
    }
}
