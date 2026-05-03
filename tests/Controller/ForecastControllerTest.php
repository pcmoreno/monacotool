<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\TeamRole;
use Symfony\Component\HttpFoundation\Response;

class ForecastControllerTest extends AbstractControllerTest
{
    // --- POST /team/{id}/forecast ---

    public function test_request_forecast_requires_authentication(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Alpha', $admin);

        $response = $this->apiPost('/team/' . $team->getId() . '/forecast', [
            'targetOutput' => 100,
            'targetIterations' => 10,
        ]);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    public function test_request_forecast_forbidden_for_non_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $other = $this->createVerifiedUser('other@example.com');
        $team = $this->createTeamWithAdmin('Beta', $admin);

        $this->client->loginUser($other);
        $response = $this->apiPost('/team/' . $team->getId() . '/forecast', [
            'targetOutput' => 100,
            'targetIterations' => 10,
        ]);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test_request_forecast_returns_201_with_snapshot(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Gamma', $admin);

        $this->client->loginUser($admin);
        $response = $this->apiPost('/team/' . $team->getId() . '/forecast', [
            'targetOutput' => 100,
            'targetIterations' => 10,
        ]);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('teamStatsSnapshot', $data);
        $this->assertArrayHasKey('numberOfSimulations', $data);
        $this->assertNull($data['sensitivityTable']);
    }

    // --- POST /forecast/{id}/sensitivity ---

    public function test_sensitivity_requires_authentication(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Delta', $admin);
        $forecast = $this->createForecast($team);

        $response = $this->apiPost('/forecast/' . $forecast->getId() . '/sensitivity');

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    public function test_sensitivity_forbidden_for_non_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $other = $this->createVerifiedUser('other@example.com');
        $team = $this->createTeamWithAdmin('Epsilon', $admin);
        $forecast = $this->createForecast($team);

        $this->client->loginUser($other);
        $response = $this->apiPost('/forecast/' . $forecast->getId() . '/sensitivity');

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test_sensitivity_returns_table_for_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Zeta', $admin);
        $forecast = $this->createForecast($team, targetIterations: 10);

        $this->client->loginUser($admin);
        $response = $this->apiPost('/forecast/' . $forecast->getId() . '/sensitivity');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $table = json_decode($response->getContent(), true);
        $this->assertIsArray($table);
        $this->assertArrayNotHasKey('10', $table);
        $this->assertArrayHasKey('11', $table);
        $this->assertArrayHasKey('9', $table);
    }

    public function test_sensitivity_returns_table_for_non_admin_member(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $member = $this->createVerifiedUser('member@example.com');
        $team = $this->createTeamWithAdmin('Eta', $admin);
        $this->addMember($team, $member, TeamRole::User);
        $forecast = $this->createForecast($team);

        $this->client->loginUser($member);
        $response = $this->apiPost('/forecast/' . $forecast->getId() . '/sensitivity');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function test_sensitivity_is_cached_on_second_call(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Theta', $admin);
        $forecast = $this->createForecast($team);

        $this->client->loginUser($admin);
        $first = json_decode(
            $this->apiPost('/forecast/' . $forecast->getId() . '/sensitivity')->getContent(),
            true
        );
        $second = json_decode(
            $this->apiPost('/forecast/' . $forecast->getId() . '/sensitivity')->getContent(),
            true
        );

        $this->assertSame($first, $second);
    }
}
