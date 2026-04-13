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

#[Route('/user')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserController extends AbstractController
{
    public function __construct(private readonly UserServiceInterface $userService)
    {

    }

    /**
     * User profile
     */
    #[Route(
        '',
        name: 'user_profile',
        methods: ['GET'])]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = $this->userService->findWithStats($user->getId());

        return $this->render('user/index.html.twig', [
            'user' => $data['user'],
            'bugCount' => $data['bugCount'],
            'commentCount' => $data['commentCount'],
        ]);
    }

    /**
     * Change email
     */
    #[Route(
        '/change-email',
        name: 'user_change_email',
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

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/change_email.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Change password
     */
    #[Route(
        '/change-password',
        name: 'user_change_password',
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

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
