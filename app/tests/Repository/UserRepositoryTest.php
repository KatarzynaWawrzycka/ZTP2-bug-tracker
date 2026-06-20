<?php

/**
 * User repository tests.
 */

namespace App\Tests\Repository;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Class UserRepositoryTest.
 */
class UserRepositoryTest extends KernelTestCase
{
    /**
     * User repository.
     */
    private ?UserRepository $userRepository;

    /**
     * Set up.
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->userRepository = $container->get(UserRepository::class);
    }

    /**
     * Test upgrade password.
     */
    public function testUpgradePassword(): void
    {
        // given
        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setPassword('password');
        $user->setRoles([UserRole::ROLE_USER->value]);

        $this->userRepository->save($user);

        $newPassword = 'new_password';

        // when
        $this->userRepository->upgradePassword($user, $newPassword);

        // then
        $updatedUser = $this->userRepository->find($user->getId());

        $this->assertSame($newPassword, $updatedUser->getPassword());
    }

    /**
     * Test upgrade password with exception.
     */
    public function testUpgradePasswordThrowsException(): void
    {
        // given
        $unsupportedUser = $this->createStub(PasswordAuthenticatedUserInterface::class);

        // then
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Instances of "%s" are not supported.',
                $unsupportedUser::class
            )
        );

        // when
        $this->userRepository->upgradePassword(
            $unsupportedUser,
            'new_hashed_password'
        );
    }
}
