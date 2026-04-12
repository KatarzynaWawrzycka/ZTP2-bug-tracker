<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private RequestStack $requestStack,
    ) {}

    public function handle(Request $request, AccessDeniedException $exception): ?RedirectResponse
    {
        $session = $this->requestStack->getSession();

        if ($session) {
            $session->getFlashBag()->add('warning', 'message.access_denied');
        }

        return new RedirectResponse(
            $this->router->generate('bug_index')
        );
    }
}
