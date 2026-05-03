<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class AccountControllerTest extends AbstractControllerTest
{
    public function test_register_success(): void
    {
        $response = $this->apiPost('/register', [
            'email' => 'new@example.com',
            'name' => 'New User',
            'password' => 'password123',
            'confirmPassword' => 'password123',
        ]);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('new@example.com', $data['email']);
    }

    public function test_register_returns_422_for_duplicate_email(): void
    {
        $this->createVerifiedUser('existing@example.com');

        $response = $this->apiPost('/register', [
            'email' => 'existing@example.com',
            'name' => 'Another',
            'password' => 'password123',
            'confirmPassword' => 'password123',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function test_register_returns_422_for_invalid_email(): void
    {
        $response = $this->apiPost('/register', [
            'email' => 'not-an-email',
            'name' => 'Test',
            'password' => 'password123',
            'confirmPassword' => 'password123',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function test_register_returns_422_for_password_mismatch(): void
    {
        $response = $this->apiPost('/register', [
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'password123',
            'confirmPassword' => 'different456',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function test_register_returns_422_for_short_password(): void
    {
        $response = $this->apiPost('/register', [
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'short',
            'confirmPassword' => 'short',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function test_verify_email_with_invalid_token_redirects_to_login(): void
    {
        $this->client->request('GET', '/verify-email?token=invalidtoken');

        $this->assertResponseRedirects('/login');
    }

    public function test_verify_email_with_valid_token_redirects_to_team(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $this->createUnverifiedUser('unverified@example.com', $plainToken);

        $this->client->request('GET', '/verify-email?token=' . $plainToken);

        $this->assertResponseRedirects('/team');
    }

    public function test_forgot_password_returns_200_regardless_of_email(): void
    {
        $response = $this->apiPost('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function test_reset_password_with_invalid_token_returns_422(): void
    {
        $response = $this->apiPost('/reset-password', [
            'token' => 'invalidtoken',
            'password' => 'newpassword123',
            'confirmPassword' => 'newpassword123',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function test_reset_password_with_valid_token_returns_200(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $user = $this->createVerifiedUser('resetme@example.com');
        $user->setPasswordResetToken(hash('sha256', $plainToken));
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->em()->flush();

        $response = $this->apiPost('/reset-password', [
            'token' => $plainToken,
            'password' => 'newpassword123',
            'confirmPassword' => 'newpassword123',
        ]);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function test_reset_password_with_expired_token_returns_422(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $user = $this->createVerifiedUser('expired@example.com');
        $user->setPasswordResetToken(hash('sha256', $plainToken));
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('-1 hour'));
        $this->em()->flush();

        $response = $this->apiPost('/reset-password', [
            'token' => $plainToken,
            'password' => 'newpassword123',
            'confirmPassword' => 'newpassword123',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }
}
