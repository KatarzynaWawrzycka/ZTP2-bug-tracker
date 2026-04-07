<?php
/**
 * Bug controller.
 */

namespace App\Controller;

use App\Entity\Bug;
use App\Repository\BugRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
     * @param  BugRepository $bugRepository Bug repository
     *
     * @return Response HTTP response
     */
    #[Route(
        name: 'bug_index',
        methods: ['GET']
    )]
    public function index(BugRepository $bugRepository): Response
    {
        $bugs = $bugRepository->findAll();

        return $this->render(
            'bug/index.html.twig',
            ['bugs' => $bugs]
        );
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
