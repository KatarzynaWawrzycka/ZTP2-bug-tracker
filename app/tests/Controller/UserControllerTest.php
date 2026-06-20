<?php

/**
 * User Controller Test.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Enum\UserRole;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserControllerTest.
 */
class UserControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/user';

    private KernelBrowser $httpClient;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test view account details by anonymous user.
     */
    public function testProfileAnonymous(): void
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
     * Test view account details by user.
     */
    public function testProfileUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view account details by admin.
     */
    public function testProfileAdmin(): void
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
     * Test change email by anonymous user.
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
     * Test change email form by user.
     */
    public function testChangeEmailUserGet(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/change-email');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change email form by admin.
     */
    public function testChangeEmailAdminGet(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/change-email');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test change email submission by user.
     */
    public function testChangeEmailUserPost(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/change-email');

        $form = $crawler->filter('form')->form([
            'user_email[email]' => 'new'.uniqid().'@example.com',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    /**
     * Test change email submission by admin.
     */
    public function testChangeEmailAdminPost(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($user);

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/change-email');

        $form = $crawler->filter('form')->form([
            'user_email[email]' => 'new'.uniqid().'@example.com',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    /**
     * Test change password by anonymous user.
     */
    public function testChangePasswordlAnonymous(): void
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
     * Test change password form for user.
     */
    public function testChangePasswordUserGet(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', '/user/change-password');

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(200, $statusCode);
    }

    /**
     * Test change password form for user.
     */
    public function testChangePasswordAdminGet(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        // when
        $this->httpClient->request('GET', '/user/change-password');

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(200, $statusCode);
    }

    /**
     * Test change password submission for user.
     */
    public function testChangePasswordUserPost(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $crawler = $this->httpClient->request('GET', '/user/change-password');

        $form = $crawler->filter('form')->form([
            'user_password[plainPassword][first]' => 'newpass123',
            'user_password[plainPassword][second]' => 'newpass123',
        ]);

        // when
        $this->httpClient->submit($form);

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(302, $statusCode);
    }

    /**
     * Test change password submission for admin.
     */
    public function testChangePasswordAdminPost(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request('GET', '/user/change-password');

        $form = $crawler->filter('form')->form([
            'user_password[plainPassword][first]' => 'adminpass123',
            'user_password[plainPassword][second]' => 'adminpass123',
        ]);

        // when
        $this->httpClient->submit($form);

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(302, $statusCode);
    }

    /**
     * Test delete user page for user.
     */
    public function testDeleteUserGet(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', '/user/delete');

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(200, $statusCode);
    }

    /**
     * Test delete user page for admin.
     */
    public function testDeleteAdminGet(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        // when
        $this->httpClient->request('GET', '/user/delete');

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(200, $statusCode);
    }

    /**
     * Test delete user submission for user.
     */
    public function testDeleteUserPost(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $crawler = $this->httpClient->request('GET', '/user/delete');

        $form = $crawler->filter('form')->form();

        // when
        $this->httpClient->submit($form);

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(302, $statusCode);
        $this->assertStringContainsString('/login', $this->httpClient->getResponse()->headers->get('Location') ?? '');
    }

    /**
     * Test delete user submission for admin.
     */
    public function testDeleteAdminPost(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request('GET', '/user/delete');

        $form = $crawler->filter('form')->form();

        // when
        $this->httpClient->submit($form);

        $statusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(302, $statusCode);
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
            $passwordHasher->hashPassword($user, 'password')
        );

        $userRepository->save($user);

        return $user;
    }
}
