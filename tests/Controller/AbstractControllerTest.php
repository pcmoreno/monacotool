<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Membership;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\MembershipStatus;
use App\Enum\TeamRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AbstractControllerTest extends WebTestCase
{
    protected KernelBrowser $client;
    private string $csrfToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // GET /login to establish a session and capture the CSRF token from the meta tag.
        // The session cookie is stored in the client's cookie jar and reused by all subsequent
        // requests, so the CSRF token remains valid for the lifetime of this test.
        $crawler = $this->client->request('GET', '/login');
        $this->csrfToken = $crawler->filter('meta[name="csrf-token"]')->attr('content');
    }

    protected function tearDown(): void
    {
        $this->purgeDatabase();
        parent::tearDown();
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function apiPost(string $url, array $data = []): Response
    {
        $this->client->request('POST', $url, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-CSRF-Token' => $this->csrfToken,
        ], json_encode($data));

        return $this->client->getResponse();
    }

    protected function apiDelete(string $url): Response
    {
        $this->client->request('DELETE', $url, [], [], [
            'HTTP_X-CSRF-Token' => $this->csrfToken,
        ]);

        return $this->client->getResponse();
    }

    protected function createVerifiedUser(
        string $email = 'user@example.com',
        string $password = 'password123',
        string $name = 'Test User',
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setIsVerified(true);
        $user->setPassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, $password)
        );

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createUnverifiedUser(
        string $email,
        string $plainVerificationToken,
        string $password = 'password123',
        string $name = 'Unverified User',
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setIsVerified(false);
        $user->setEmailVerificationToken(hash('sha256', $plainVerificationToken));
        $user->setPassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, $password)
        );

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createSuperAdmin(
        string $email = 'super@example.com',
        string $password = 'password123',
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setPassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, $password)
        );

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createTeamWithAdmin(string $name, User $admin): Team
    {
        $team = new Team();
        $team->setName($name);

        $membership = new Membership();
        $membership->setUser($admin);
        $membership->setRole(TeamRole::Admin);
        $team->addMembership($membership);

        $em = $this->em();
        $em->persist($team);
        $em->flush();

        return $team;
    }

    protected function addMember(Team $team, User $user, TeamRole $role): void
    {
        $membership = new Membership();
        $membership->setUser($user);
        $membership->setRole($role);
        $team->addMembership($membership);

        $em = $this->em();
        $em->persist($membership);
        $em->flush();
    }

    protected function createPendingInvitedUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName('Invited User');
        $user->setPassword('');
        $user->setIsVerified(false);

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createPendingMembership(Team $team, User $user, string $plainToken): Membership
    {
        $membership = new Membership();
        $membership->setUser($user);
        $membership->setTeam($team);
        $membership->setRole(TeamRole::User);
        $membership->setStatus(MembershipStatus::Pending);
        $membership->setInviteToken(hash('sha256', $plainToken));
        $membership->setInviteExpiresAt(new \DateTimeImmutable('+7 days'));

        $em = $this->em();
        $em->persist($membership);
        $em->flush();

        return $membership;
    }

    protected function createRejectedMembership(Team $team, User $user): Membership
    {
        $membership = new Membership();
        $membership->setUser($user);
        $membership->setTeam($team);
        $membership->setRole(TeamRole::User);
        $membership->setStatus(MembershipStatus::Rejected);

        $em = $this->em();
        $em->persist($membership);
        $em->flush();

        return $membership;
    }

    protected function createExpiredMembership(Team $team, User $user, string $plainToken): Membership
    {
        $membership = new Membership();
        $membership->setUser($user);
        $membership->setTeam($team);
        $membership->setRole(TeamRole::User);
        $membership->setStatus(MembershipStatus::Pending);
        $membership->setInviteToken(hash('sha256', $plainToken));
        $membership->setInviteExpiresAt(new \DateTimeImmutable('-1 day'));

        $em = $this->em();
        $em->persist($membership);
        $em->flush();

        return $membership;
    }

    protected function createForecast(Team $team, int $targetIterations = 10, int $targetOutput = 100): \App\Entity\Forecast
    {
        $forecast = new \App\Entity\Forecast();
        $forecast->setTeam($team);
        $forecast->setTargetIterations($targetIterations);
        $forecast->setTargetOutput($targetOutput);
        $forecast->setNumberOfSimulations(500);
        $forecast->setResult(0.75);
        $forecast->setTeamStatsSnapshot(['mean' => 10.0, 'std_dev' => 2.0]);

        $em = $this->em();
        $em->persist($forecast);
        $em->flush();

        return $forecast;
    }

    private function purgeDatabase(): void
    {
        $em = $this->em();
        $em->createQuery('DELETE FROM App\Entity\Forecast f')->execute();
        $em->createQuery('DELETE FROM App\Entity\Iteration i')->execute();
        $em->createQuery('DELETE FROM App\Entity\Membership m')->execute();
        $em->createQuery('DELETE FROM App\Entity\Team t')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();
    }
}
