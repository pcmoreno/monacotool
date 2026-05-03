<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\TeamRole;
use Symfony\Component\HttpFoundation\Response;

class TeamControllerTest extends AbstractControllerTest
{
    public function test_create_team_requires_authentication(): void
    {
        $response = $this->apiPost('/team', ['name' => 'My Team']);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    public function test_create_team_returns_201(): void
    {
        $this->client->loginUser($this->createVerifiedUser());

        $response = $this->apiPost('/team', ['name' => 'My Team']);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('My Team', $data['name']);
    }

    public function test_show_team_forbidden_for_non_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $other = $this->createVerifiedUser('other@example.com');
        $team = $this->createTeamWithAdmin('Alpha', $admin);

        $this->client->loginUser($other);
        $this->client->request('GET', '/team/' . $team->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function test_show_team_accessible_for_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Beta', $admin);

        $this->client->loginUser($admin);
        $this->client->request('GET', '/team/' . $team->getId());

        $this->assertResponseIsSuccessful();
    }

    public function test_show_team_accessible_for_non_admin_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $member = $this->createVerifiedUser('member@example.com');
        $team = $this->createTeamWithAdmin('Gamma', $admin);
        $this->addMember($team, $member, TeamRole::User);

        $this->client->loginUser($member);
        $this->client->request('GET', '/team/' . $team->getId());

        $this->assertResponseIsSuccessful();
    }

    public function test_delete_team_forbidden_for_non_admin_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $member = $this->createVerifiedUser('member@example.com');
        $team = $this->createTeamWithAdmin('Delta', $admin);
        $this->addMember($team, $member, TeamRole::User);

        $this->client->loginUser($member);
        $response = $this->apiDelete('/team/' . $team->getId());

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test_delete_team_forbidden_for_non_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $other = $this->createVerifiedUser('other@example.com');
        $team = $this->createTeamWithAdmin('Epsilon', $admin);

        $this->client->loginUser($other);
        $response = $this->apiDelete('/team/' . $team->getId());

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test_delete_team_as_admin_returns_204(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Zeta', $admin);

        $this->client->loginUser($admin);
        $response = $this->apiDelete('/team/' . $team->getId());

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function test_delete_team_as_superadmin_returns_204(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Eta', $admin);

        $this->client->loginUser($this->createSuperAdmin());
        $response = $this->apiDelete('/team/' . $team->getId());

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
