<?php

/**
 * Bug controller.
 */

namespace App\Controller;

use App\Entity\Bug;
use App\Entity\User;
use App\Form\Type\BugType;
use App\Security\Voter\BugVoter;
use App\Service\BugServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BugController.
 */
#[Route('/bug')]
class BugController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct(private readonly BugServiceInterface $bugService, private readonly TranslatorInterface $translator)
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

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route(
        '/create',
        name: 'bug_create',
        methods: ['GET', 'POST'],
    )]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $bug = new Bug();
        $bug->setAuthor($user);
        $form = $this->createForm(BugType::class, $bug);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->bugService->save($bug);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('bug_index');
        }

        return $this->render(
            'bug/create.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param Bug     $bug     Bug entity
     *
     * @return Response HTTP response
     */
    #[Route(
        '/{id}/edit',
        name: 'bug_edit',
        requirements: ['id' => '[1-9]\d*'],
        methods: ['GET', 'PUT']
    )]
    #[IsGranted(BugVoter::EDIT, subject: 'bug')]
    public function edit(Request $request, Bug $bug): Response
    {
        $form = $this->createForm(
            BugType::class,
            $bug,
            [
                'method' => 'PUT',
                'action' => $this->generateUrl('bug_edit', ['id' => $bug->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->bugService->save($bug);

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('bug_index');
        }

        return $this->render(
            'bug/edit.html.twig',
            [
                'form' => $form->createView(),
                'bug' => $bug,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param Bug     $bug     Bug entity
     *
     * @return Response HTTP response
     */
    #[Route(
        '/{id}/delete',
        name: 'bug_delete',
        requirements: ['id' => '[1-9]\d*'],
        methods: ['GET', 'DELETE']
    )]
    #[IsGranted(BugVoter::DELETE, subject: 'bug')]
    public function delete(Request $request, Bug $bug): Response
    {
        $form = $this->createForm(FormType::class, $bug, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('bug_delete', ['id' => $bug->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->bugService->delete($bug);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('bug_index');
        }

        return $this->render(
            'bug/delete.html.twig',
            [
                'form' => $form->createView(),
                'bug' => $bug,
            ]
        );
    }
}
