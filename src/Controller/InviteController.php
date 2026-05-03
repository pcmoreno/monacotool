<?php

declare(strict_types=1);

namespace App\Controller;

use App\Services\AccountService;
use App\Services\InviteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

final class InviteController extends AbstractController
{
    public function __construct(
        private readonly InviteService $inviteService,
        private readonly AccountService $accountService,
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly FormLoginAuthenticator $formLoginAuthenticator,
    ) {
    }

    #[Route('/invite/{token}', name: 'app_invite_setup', methods: ['GET'])]
    public function setup(string $token): Response
    {
        $membership = $this->inviteService->findPendingByToken($token);

        if (!$membership || $membership->isInviteExpired()) {
            return $this->render('invite/invalid.html.twig');
        }

        $user = $membership->getUser();

        if ($user->isVerified()) {
            return $this->redirectToRoute('app_invite_accept', ['token' => $token]);
        }

        return $this->render('invite/setup.html.twig', [
            'token' => $token,
            'user' => $user,
            'team' => $membership->getTeam(),
        ]);
    }

    #[Route('/invite/{token}', name: 'app_invite_finish', methods: ['POST'])]
    public function finish(string $token, Request $request): Response
    {
        $membership = $this->inviteService->findPendingByToken($token);

        if (!$membership || $membership->isInviteExpired() || $membership->getUser()->isVerified()) {
            return $this->render('invite/invalid.html.twig');
        }

        $name = trim($request->request->getString('name'));
        $password = $request->request->getString('password');
        $errors = [];

        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            $errors['name'] = 'Name must be between 2 and 60 characters.';
        }
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($errors) {
            return $this->render('invite/setup.html.twig', [
                'token' => $token,
                'user' => $membership->getUser(),
                'team' => $membership->getTeam(),
                'errors' => $errors,
                'name' => $name,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $team = $membership->getTeam();
        $user = $membership->getUser();

        $this->accountService->finishAccountSetup($membership, $name, $password);

        try {
            $response = $this->userAuthenticator->authenticateUser(
                $user,
                $this->formLoginAuthenticator,
                $request,
            );
            $this->inviteService->accept($membership);

            return $response ?? $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        } catch (\Throwable) {
            $this->inviteService->accept($membership);

            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/invite/{token}/accept', name: 'app_invite_accept', methods: ['GET'])]
    public function accept(string $token): Response
    {
        $membership = $this->inviteService->findPendingByToken($token);

        if (!$membership || $membership->isInviteExpired()) {
            return $this->render('invite/invalid.html.twig');
        }

        return $this->render('invite/accept.html.twig', [
            'token' => $token,
            'team' => $membership->getTeam(),
        ]);
    }

    #[Route('/invite/{token}/accept', name: 'app_invite_accept_post', methods: ['POST'])]
    public function acceptPost(string $token): Response
    {
        $membership = $this->inviteService->findPendingByToken($token);

        if (!$membership || $membership->isInviteExpired()) {
            return $this->render('invite/invalid.html.twig');
        }

        $this->inviteService->accept($membership);

        return $this->redirectToRoute('app_team_show', ['id' => $membership->getTeam()->getId()]);
    }

    #[Route('/invite/{token}/reject', name: 'app_invite_reject', methods: ['GET'])]
    public function reject(string $token): Response
    {
        $membership = $this->inviteService->findPendingByToken($token);

        if (!$membership || $membership->isInviteExpired()) {
            return $this->render('invite/invalid.html.twig');
        }

        $this->inviteService->reject($membership);

        return $this->render('invite/rejected.html.twig', [
            'team' => $membership->getTeam(),
        ]);
    }
}
