<?php

/**
 * Category controller.
 */

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class CategoryController.
 */
#[Route('/category')]
class CategoryController extends AbstractController
{
    /**
     * Index action.
     *
     * @param CategoryRepository $categoryRepository Category repository
     * @param PaginatorInterface $paginator Paginator
     * @param int $page
     * @return Response HTTP response
     */
    #[Route(
        name: 'category_index',
        methods: ['GET']
    )]
    public function index(CategoryRepository $categoryRepository, PaginatorInterface $paginator, #[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $paginator->paginate(
            $categoryRepository->queryAll(),
            $page,
            CategoryRepository::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['category.id', 'category.createdAt', 'category.updatedAt', 'category.title'],
                'defaultSortFieldName' => 'category.updatedAt',
                'defaultSortDirection' => 'desc',
            ]
        );

        return $this->render('category/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * View action.
     *
     * @param Category $category Category entity
     *
     * @return Response HTTP response
     */
    #[Route(
        '/{id}',
        name: 'category_view',
        requirements: ['id' => '[1-9]\d*'],
        methods: ['GET']
    )]
    public function view(Category $category): Response
    {
        return $this->render(
            'category/view.html.twig',
            ['category' => $category]
        );
    }
}
