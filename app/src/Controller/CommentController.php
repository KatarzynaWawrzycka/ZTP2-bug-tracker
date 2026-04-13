<?php

namespace App\Controller;

use App\Entity\Bug;
use App\Entity\Comment;
use App\Form\Type\CommentType;
//use App\Security\Voter\CommentVoter;
use App\Security\Voter\CommentVoter;
use App\Service\CommentServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/bug/{bugId}/comment')]
class CommentController extends AbstractController
{
    public function __construct(
        private readonly CommentServiceInterface $commentService,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Edit action.
     */
    #[Route(
        '/{id}/edit',
        name: 'comment_edit',
        requirements: ['bugId' => '[1-9]\d*', 'id' => '[1-9]\d*'],
        methods: ['GET', 'PUT']
    )]
    #[IsGranted(CommentVoter::EDIT, subject: 'comment')]
    public function edit(#[MapEntity(id: 'bugId')] Bug $bug, Comment $comment, Request $request): Response
    {
        if ($comment->getBug()->getId() !== $bug->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(
            CommentType::class,
            $comment,
            [
                'method' => 'PUT',
                'action' => $this->generateUrl('comment_edit', [
                    'bugId' => $bug->getId(),
                    'id' => $comment->getId(),
                ]),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentService->save($comment);

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('bug_view', [
                'id' => $bug->getId(),
            ]);
        }

        return $this->render('comment/edit.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
            'bug' => $bug,
        ]);
    }

    /**
     * Delete action.
     */
    #[Route(
        '/{id}/delete',
        name: 'comment_delete',
        requirements: ['bugId' => '[1-9]\d*', 'id' => '[1-9]\d*'],
        methods: ['GET', 'DELETE']
    )]
    #[IsGranted(CommentVoter::DELETE, subject: 'comment')]
    public function delete(#[MapEntity(id: 'bugId')] Bug $bug, Comment $comment, Request $request): Response
    {
        // 🔒 ensure comment belongs to this bug
        if ($comment->getBug()->getId() !== $bug->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(FormType::class, $comment, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('comment_delete', [
                'bugId' => $bug->getId(),
                'id' => $comment->getId(),
            ]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentService->delete($comment);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('bug_view', [
                'id' => $bug->getId(),
            ]);
        }

        return $this->render('comment/delete.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
            'bug' => $bug,
        ]);
    }
}
