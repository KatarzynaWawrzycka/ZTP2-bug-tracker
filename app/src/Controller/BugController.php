<?php

/**
 * Bug controller.
 */

namespace App\Controller;

use App\Entity\Bug;
use App\Entity\Comment;
use App\Entity\Enum\BugStatus;
use App\Entity\User;
use App\Form\Type\BugAssignType;
use App\Form\Type\BugType;
use App\Form\Type\CommentType;
use App\Security\Voter\BugVoter;
use App\Service\BugServiceInterface;
use App\Service\CommentServiceInterface;
use App\Service\UserServiceInterface;
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
    public function __construct(private readonly BugServiceInterface $bugService, private readonly TranslatorInterface $translator, private readonly CommentServiceInterface $commentService, private readonly UserServiceInterface $userService)
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
        methods: ['GET', 'POST']
    )]
    public function view(Bug $bug, Request $request): Response
    {
        $comment = new Comment();
        $securityUser = $this->getUser();

        /** @var User|null $user */
        $user = $securityUser instanceof User ? $securityUser : null;

        if ($user) {
            $comment->setAuthor($user);
        }

        $comment->setBug($bug);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user) {
                throw $this->createAccessDeniedException('You must be logged in to comment.');
            }

            if ($bug->getStatusEnum() !== BugStatus::OPEN) {
                throw $this->createAccessDeniedException('You can only comment on open bugs.');
            }

            $comment->setAuthor($user);
            $comment->setBug($bug);

            $this->commentService->save($comment);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('bug_view', [
                'id' => $bug->getId(),
            ]);
        }

        return $this->render('bug/view.html.twig', [
            'bug' => $bug,
            'form' => $form->createView(),
            'comments' => $this->commentService->findByBug($bug),
        ]);
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

    #[Route(
        '/{id}/status/{status}',
        name: 'bug_change_status',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function changeStatus(Bug $bug, string $status): Response
    {
        $this->bugService->changeStatus(
            $bug,
            BugStatus::from((int) $status)
        );

        return $this->redirectToRoute('bug_view', ['id' => $bug->getId()]);
    }

    #[Route(
        '/{id}/assign',
        name: 'bug_assign',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function assign(Bug $bug, Request $request): Response
    {
        if ($bug->getStatusEnum() !== BugStatus::OPEN) {
            $this->addFlash(
                'warning',
                'You can only assign open bugs.'
            );

            return $this->redirectToRoute('bug_view', [
                'id' => $bug->getId(),
            ]);
        }

        $admins = $this->userService->findAdmins();

        $form = $this->createForm(BugAssignType::class, $bug, [
            'admins' => $admins,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->bugService->assign(
                $bug,
                $form->get('assignedTo')->getData()
            );

            return $this->redirectToRoute('bug_view', [
                'id' => $bug->getId(),
            ]);
        }

        return $this->render('bug/assign.html.twig', [
            'form' => $form->createView(),
            'bug' => $bug,
        ]);
    }
}
