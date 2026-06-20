<?php

/**
 * Admin Controller Test.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class AdminControllerTest.
 */
class AdminControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/admin';

    private KernelBrowser $httpClient;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test admin panel view for anonymous user.
     */
    public function testAdminIndexAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view admin panel for user.
     */
    public function testAdminIndexUserForbidden(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view admin panel for admin.
     */
    public function testAdminIndexAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change admin email for anonymous user.
     */
    public function testChangeEmailAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/change-email');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change admin email for user.
     */
    public function testChangeEmailUserForbidden(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/change-email');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change admin email for admin.
     */
    public function testChangeEmailAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/change-email');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'user_email[email]' => 'new'.uniqid().'@example.com',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change admin password for anonymous user.
     */
    public function testChangePasswordAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/change-password');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change admin password for user.
     */
    public function testChangePasswordUserForbidden(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/change-password');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change admin password for admin.
     */
    public function testChangePasswordAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/change-password');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'user_password[plainPassword][first]' => 'newPassword123!',
            'user_password[plainPassword][second]' => 'newPassword123!',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Create user helper.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');

        $userRepository = $container->get(UserRepository::class);

        $user = new User();

        $user->setEmail('user'.uniqid().'@example.com');

        $user->setRoles($roles);

        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'password'
            )
        );

        $userRepository->save($user);

        return $user;
    }
}
