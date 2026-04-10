<?php

/**
 * Bug controller.
 */

namespace App\Controller;

use App\Entity\Bug;
use App\Repository\BugRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class BugController.
 */
#[Route('/bug')]
class BugController extends AbstractController
{
    /**
     * Index action.
     *
     * @param BugRepository      $bugRepository Bug repository
     * @param PaginatorInterface $paginator     Paginator
     *
     * @return Response HTTP response
     */
    #[Route(
        name: 'bug_index',
        methods: ['GET']
    )]
    public function index(BugRepository $bugRepository, PaginatorInterface $paginator, #[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $paginator->paginate(
            $bugRepository->queryAll(),
            $page,
            BugRepository::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['bug.id', 'bug.createdAt', 'bug.updatedAt', 'bug.title', 'bug.description'],
                'defaultSortFieldName' => 'bug.updatedAt',
                'defaultSortDirection' => 'desc',
            ]
        );

        return $this->render('bug/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * View action.
     *
     * @param Bug $bug Bug entity
     *
     * @return Response HTTP response
     */
    #[Route(
        '/{id}',
        name: 'bug_view',
        requirements: ['id' => '[1-9]\d*'],
        methods: ['GET']
    )]
    public function view(Bug $bug): Response
    {
        return $this->render(
            'bug/view.html.twig',
            ['bug' => $bug]
        );
    }
}
