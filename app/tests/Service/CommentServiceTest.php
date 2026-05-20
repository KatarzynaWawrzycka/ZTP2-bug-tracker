<?php
/**
 * Comment service tests.
 */

namespace App\Tests\Service;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\BugRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\CommentService;
use App\Service\CommentServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CommentServiceTest.
 */
class CommentServiceTest extends KernelTestCase
{
    /**
     * Comment repository.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Comment service.
     */
    private ?CommentServiceInterface $commentService;

    /**
     * Set up test.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->commentService = $container->get(CommentService::class);
    }

    /**
     * Create user helper.
     */
    private function createUser(): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');

        $repo = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles([UserRole::ROLE_USER->value]);

        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $repo->save($user);

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
        $category->setTitle('Category '.uniqid());

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
        $bug->setTitle('Bug Title'.uniqid());
        $bug->setAuthor($this->createUser());
        $bug->setDescription('Bug Description');
        $bug->setCategory($this->createCategory());

        $repo->save($bug);

        return $bug;
    }

    /**
     * Test save.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        $expectedComment = new Comment();
        $expectedComment->setContent('Test Comment Content');
        $expectedComment->setAuthor($this->createUser());
        $expectedComment->setBug($this->createBug());

        // when
        $this->commentService->save($expectedComment);

        // then
        $expectedCommentId = $expectedComment->getId();
        $resultComment = $this->entityManager->createQueryBuilder()
            ->select('comment')
            ->from(Comment::class, 'comment')
            ->where('comment.id = :id')
            ->setParameter(':id', $expectedCommentId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedComment, $resultComment);
    }

    /**
     * Test delete.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testDelete(): void
    {
        // given
        $commentToDelete = new Comment();
        $commentToDelete->setContent('Test Comment Content');
        $commentToDelete->setAuthor($this->createUser());
        $commentToDelete->setBug($this->createBug());

        $this->entityManager->persist($commentToDelete);
        $this->entityManager->flush();
        $deletedCommentId = $commentToDelete->getId();

        // when
        $this->commentService->delete($commentToDelete);

        // then
        $resultComment = $this->entityManager->createQueryBuilder()
            ->select('comment')
            ->from(Comment::class, 'comment')
            ->where('comment.id = :id')
            ->setParameter(':id', $deletedCommentId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultComment);
    }

    /**
     * Test find one by bug.
     */
    public function testFindByBug(): void
    {
        // given
        $comment = new Comment();
        $comment->setContent('Test Comment Content');
        $comment->setBug($this->createBug());
        $comment->setAuthor($this->createUser());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        // when
        $result = $this->commentService->findByBug($comment->getBug());

        // then
        $this->assertCount(1, $result);
        $this->assertEquals('Test Comment Content', $result[0]->getContent());
    }
}
