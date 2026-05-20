<?php
/**
 * Category service tests.
 */

namespace App\Tests\Service;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Service\CategoryService;
use App\Service\CategoryServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CategoryServiceTest.
 */
class CategoryServiceTest extends KernelTestCase
{
    /**
     * Category repository.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Category service.
     */
    private ?CategoryServiceInterface $categoryService;

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
        $this->categoryService = $container->get(CategoryService::class);
    }

    /**
     * Test save.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        // given
        $expectedCategory = new Category();
        $expectedCategory->setTitle('Test Category');

        // when
        $this->categoryService->save($expectedCategory);

        // then
        $expectedCategoryId = $expectedCategory->getId();
        $resultCategory = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.id = :id')
            ->setParameter(':id', $expectedCategoryId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedCategory, $resultCategory);
    }

    /**
     * Test delete.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testDelete(): void
    {
        // given
        $categoryToDelete = new Category();
        $categoryToDelete->setTitle('Test Category');
        $this->entityManager->persist($categoryToDelete);
        $this->entityManager->flush();
        $deletedCategoryId = $categoryToDelete->getId();

        // when
        $this->categoryService->delete($categoryToDelete);

        // then
        $resultCategory = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.id = :id')
            ->setParameter(':id', $deletedCategoryId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultCategory);
    }

    /**
     * Test find by id.
     *
     * @throws ORMException
     */
    public function testFindById(): void
    {
        // given
        $expectedCategory = new Category();
        $expectedCategory->setTitle('Test Category');
        $this->entityManager->persist($expectedCategory);
        $this->entityManager->flush();
        $expectedCategoryId = $expectedCategory->getId();

        // when
        $resultCategory = $this->categoryService->findOneById($expectedCategoryId);

        // then
        $this->assertEquals($expectedCategory, $resultCategory);
    }

    /**
     * Test paginated list.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $page = 1;
        $dataSetSize = 10;
        $expectedResultSize = 10;

        $counter = 0;
        while ($counter < $dataSetSize) {
            $category = new Category();
            $category->setTitle('Test Category #'.$counter);
            $this->categoryService->save($category);

            ++$counter;
        }

        // when
        $result = $this->categoryService->getPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    public function testCanBeDeletedTrue(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Test Category');

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // when
        $result = $this->categoryService->canBeDeleted($category);

        // then
        $this->assertTrue($result);
    }

    public function testCanBeDeletedFalse(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Test Category');

        $user = new User();
        $user ->setEmail('user'.uniqid().'@example.com');
        $user->setPassword('password');
        $user->setRoles([UserRole::ROLE_USER->value]);

        $bug = new Bug();
        $bug->setTitle('Test Bug'.uniqid());
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($user);
        $bug->setCategory($category);


        $this->entityManager->persist($category);
        $this->entityManager->persist($user);
        $this->entityManager->persist($bug);
        $this->entityManager->flush();

        // when
        $result = $this->categoryService->canBeDeleted($category);

        // then
        $this->assertFalse($result);
    }
}
