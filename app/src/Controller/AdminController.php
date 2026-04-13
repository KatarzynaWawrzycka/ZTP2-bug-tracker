<?php

namespace App\Controller;

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
     * Admin dashboard
     */
    #[Route('', name: 'admin_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
//
//    /**
//     * Change email (current admin)
//     */
//    #[Route('/change-email', name: 'admin_change_email', methods: ['GET', 'POST'])]
//    public function changeEmail(Request $request): Response
//    {
//        $user = $this->getUser();
//
//        // form handling here (your UserType or custom form)
//
//        return $this->render('admin/change_email.html.twig', [
//            'form' => $form->createView(),
//        ]);
//    }
//
//    /**
//     * Change password (current admin)
//     */
//    #[Route('/change-password', name: 'admin_change_password', methods: ['GET', 'POST'])]
//    public function changePassword(Request $request): Response
//    {
//        $user = $this->getUser();
//
//        // form handling here
//
//        return $this->render('admin/change_password.html.twig', [
//            'form' => $form->createView(),
//        ]);
//    }
}
