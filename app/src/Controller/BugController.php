<?php

/**
 * Bug controller.
 */

namespace App\Controller;

use App\Entity\Bug;
use App\Service\BugServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class BugController.
 */
#[Route('/bug')]
class BugController extends AbstractController
{
    /**
     * Constructor.
     *
     */
    public function __construct(private readonly BugServiceInterface $bugService)
    {
    }

    /**
     * Index action.
     *
     * @param int $page Page number
     *
     * @return Response HTTP response
     */
    #[Route(
        name: 'bug_index',
        methods: ['GET']
    )]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $this->bugService->getPaginatedList($page);

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
