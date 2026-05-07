<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\EmailAlreadyExistsException;
use App\Request\ForgotPasswordRequest;
use App\Request\RegistrationRequest;
use App\Request\ResendVerificationRequest;
use App\Request\ResetPasswordRequest;
use App\Services\AccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST'])]
final class AccountController extends AbstractController
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly RateLimiterFactory $registerLimiter,
        private readonly RateLimiterFactory $resendVerificationLimiter,
        private readonly RateLimiterFactory $forgotPasswordLimiter,
        private readonly RateLimiterFactory $resetPasswordLimiter,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegistrationRequest $request,
        Request $httpRequest,
    ): JsonResponse {
        // getClientIp() relies on framework.trusted_proxies being set correctly when
        // the app runs behind a load balancer; without it, X-Forwarded-For can be spoofed.
        if (!$this->registerLimiter->create($httpRequest->getClientIp())->consume(1)->isAccepted()) {
            return new JsonResponse(['errors' => ['Too many attempts. Please try again later.']], 429);
        }

        try {
            $this->accountService->register($request->email, $request->name, $request->password);
        } catch (EmailAlreadyExistsException $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], 422);
        }

        return new JsonResponse(['email' => $request->email], 201);
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(
        #[MapRequestPayload] ResendVerificationRequest $request,
        Request $httpRequest,
    ): JsonResponse {
        if (!$this->resendVerificationLimiter->create($httpRequest->getClientIp())->consume(1)->isAccepted()) {
            return new JsonResponse(['errors' => ['Too many attempts. Please try again later.']], 429);
        }

        $this->accountService->resendVerification($request->email);

        return new JsonResponse(['message' => 'If that email exists and is unverified, a new link has been sent.']);
    }

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): RedirectResponse
    {
        $token = $request->query->getString('token');
        $user = $this->accountService->verifyEmailByToken($token);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification link.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Your email has been verified. You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        #[MapRequestPayload] ForgotPasswordRequest $request,
        Request $httpRequest,
    ): JsonResponse {
        if (!$this->forgotPasswordLimiter->create($httpRequest->getClientIp())->consume(1)->isAccepted()) {
            return new JsonResponse(['errors' => ['Too many attempts. Please try again later.']], 429);
        }

        $this->accountService->sendPasswordResetEmail($request->email);

        return new JsonResponse(['message' => 'If that email is registered, a reset link has been sent.']);
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword(
        #[MapRequestPayload] ResetPasswordRequest $request,
        Request $httpRequest,
    ): JsonResponse {
        if (!$this->resetPasswordLimiter->create($httpRequest->getClientIp())->consume(1)->isAccepted()) {
            return new JsonResponse(['errors' => ['Too many attempts. Please try again later.']], 429);
        }

        if (!$this->accountService->resetPassword($request->token, $request->password)) {
            return new JsonResponse(['errors' => ['Invalid or expired reset link.']], 422);
        }

        return new JsonResponse(['message' => 'Password updated. You can now log in.']);
    }
}
