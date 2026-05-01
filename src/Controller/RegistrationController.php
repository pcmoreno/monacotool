<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\RegistrationRequest;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST'])]
final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly Security $security,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegistrationRequest $registrationRequest): JsonResponse
    {
        if ($this->userService->emailExists($registrationRequest->email)) {
            return new JsonResponse(['errors' => ['An account with this email already exists.']], 422);
        }

        $user = $this->userService->create(
            $registrationRequest->email,
            $registrationRequest->name,
            $registrationRequest->password,
        );

        $this->security->login($user, 'form_login', 'main');

        return new JsonResponse(['redirect' => $this->generateUrl('app_team')], 201);
    }
}
