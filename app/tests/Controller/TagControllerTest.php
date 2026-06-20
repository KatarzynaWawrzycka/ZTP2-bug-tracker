<?php

/**
 * Tag Controller Test.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TagControllerTest.
 */
class TagControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/tag';

    private KernelBrowser $httpClient;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test view tag list by anonymous user.
     */
    public function testIndexAnonymous(): void
    {
        // given
        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view tag list by user.
     */
    public function testIndexUser(): void
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
     * Test view tag list by admin.
     */
    public function testIndexAdmin(): void
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
     * Test view tag details by anonymous user.
     */
    public function testViewTagAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;
        $tag = $this->createTag();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId()
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test view tag details by user.
     */
    public function testViewTagUserForbidden(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;
        $tag = $this->createTag();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId()
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test view tag details by admin.
     */
    public function testViewTagAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $tag = $this->createTag();

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$tag->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test create tag by anonymous user.
     */
    public function testCreateTagAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test create tag by user.
     */
    public function testCreateTagUserForbidden(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test create tag by admin.
     */
    public function testCreateTagAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'tag[title]' => 'New Tag '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit tag by anonymous user.
     */
    public function testEditTagAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;
        $tag = $this->createTag();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/edit'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test edit tag by user.
     */
    public function testEditTagForbiddenForUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $tag = $this->createTag();

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test edit tag by admin.
     */
    public function testEditTagAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $tag = $this->createTag();

        $expectedStatusCode = 200;

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'tag[title]' => 'Updated '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete tag by anonymous user.
     */
    public function testDeleteTagAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;
        $tag = $this->createTag();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/delete'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test delete tag by user.
     */
    public function testDeleteTagForbiddenForUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $tag = $this->createTag();

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test delete tag by admin.
     */
    public function testDeleteTagAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $tag = $this->createTag();

        $expectedStatusCode = 200;

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Create user helper.
     *
     * @param array $roles user roles
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

    /**
     * Create tag helper.
     *
     * @return Tag Tag entity
     */
    private function createTag(): Tag
    {
        $container = static::getContainer();

        $repository = $container->get(TagRepository::class);

        $tag = new Tag();

        $tag->setTitle('Tag '.uniqid());

        $repository->save($tag);

        return $tag;
    }
}
