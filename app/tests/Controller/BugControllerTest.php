<?php
/**
 * User service tests.
 */

namespace App\Tests\Controller;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Enum\UserRole;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\BugRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Service\UserServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserServiceTest.
 */
class BugControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/bug';

    private KernelBrowser $httpClient;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /*
     * HELPERS
     */

    /**
     * Create user helper.
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
     * Create category helper.
     */
    private function createCategory(): Category
    {
        $repo = static::getContainer()
            ->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Category ' . uniqid());

        $repo->save($category);

        return $category;
    }

    /**
     * Create bug helper.
     */
    private function createBug(): Bug
    {
        $repo = static::getContainer()
            ->get(BugRepository::class);

        $bug = new Bug();
        $bug->setTitle('Bug Title' . uniqid());
        $bug->setAuthor($this->createUser([
            UserRole::ROLE_USER->value,
        ]));
        $bug->setDescription('Bug Description');
        $bug->setCategory($this->createCategory());

        $repo->save($bug);

        return $bug;
    }

    /**
     * Create comment helper.
     */
    private function createComment(): Comment
    {
        $repo = static::getContainer()
            ->get(CommentRepository::class);

        $comment = new Comment();
        $comment->setAuthor($this->createUser([
            UserRole::ROLE_USER->value,
        ]));
        $comment->setContent('Comment Content');

        $repo->save($comment);

        return $comment;
    }

    /**
     * Create tag helper.
     */
    private function createTag(): Tag
    {
        $repo = static::getContainer()
            ->get(CommentRepository::class);

        $tag = new Tag();
        $tag->setTitle('Test Tag ' . uniqid());
        $tag->setAuthor($this->createUser([
            UserRole::ROLE_USER->value,
        ]));

        $repo->save($tag);

        return $tag;
    }

    /**
     * INDEX
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
     * VIEW
     */
    public function testViewBugAnonymous(): void
    {
        // given
        $expectedStatusCode = 200;
        $bug = $this->createBug();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$bug->getId()
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testViewBugUser(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 200;
        $bug = $this->createBug();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$bug->getId()
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testViewBugAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $bug = $this->createBug();

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$bug->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }
}

