<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(private readonly UserServiceInterface $userService, private readonly TranslatorInterface $translator)
    {

    }

    /**
     * List all users
     */
    #[Route(
        '',
        name: 'admin_user_index',
        methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $pagination = $this->userService->getPaginatedList($page);

        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(
        '/{id}',
        name: 'admin_user_view',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function view(int $id): Response
    {
        $result = $this->userService->findWithStats($id);

        if (!$result) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('admin/user/view.html.twig', [
            'user' => $result['user'],
            'bugCount' => $result['bugCount'],
            'commentCount' => $result['commentCount'],
        ]);
    }

    /**
     * Toggle ROLE_ADMIN
     */
    #[Route(
        '/{id}/toggle-role',
        name: 'admin_user_toggle_role',
        methods: ['POST'])]
    public function toggleRole(User $user): Response
    {
        try {
            $this->userService->toggleAdminRole($user);

            $this->addFlash('success', 'User role updated successfully.');
        } catch (\LogicException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('admin_user_view', [
            'id' => $user->getId(),
        ]);
    }

    #[Route(
        '/{id}/delete',
        name: 'admin_user_delete',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'DELETE']
    )]
    public function delete(Request $request, User $user): Response
    {
        $form = $this->createForm(FormType::class, $user, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('admin_user_delete', [
                'id' => $user->getId()
            ]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userService->delete($user);
                $this->addFlash('success', 'User deleted successfully.');
            } catch (\LogicException $e) {
                $this->addFlash('warning', $e->getMessage());
            }

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/delete.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
