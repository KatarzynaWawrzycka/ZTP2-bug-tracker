<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserEmailType;
use App\Form\Type\UserPasswordType;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(private readonly UserServiceInterface $userService, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Admin dashboard.
     */
    #[Route('', name: 'admin_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route(
        '/change-email',
        name: 'admin_change_email',
        methods: ['GET', 'POST'])]
    public function changeEmail(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserEmailType::class, null, [
            'data' => ['email' => $user->getEmail()],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $this->userService->changeEmail($user, $email);

            $this->addFlash('success', 'Email updated successfully.');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/change_email.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Change password (current admin).
     */
    #[Route(
        '/change-password',
        name: 'admin_change_password',
        methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $this->userService->changePassword(
                $user,
                $plainPassword
            );

            $this->addFlash('success', 'Password changed successfully.');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
