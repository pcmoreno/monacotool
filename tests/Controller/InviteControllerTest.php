<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Membership;
use App\Entity\User;
use App\Enum\MembershipStatus;
use App\Enum\TeamRole;
use Symfony\Component\HttpFoundation\Response;

class InviteControllerTest extends AbstractControllerTest
{
    // --- GET /invite/{token} ---

    public function test_setup_returns_invalid_for_unknown_token(): void
    {
        $this->client->request('GET', '/invite/no-such-token');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'invalid or has expired');
    }

    public function test_setup_shows_form_for_unverified_user(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Alpha', $admin);
        $invitedUser = $this->createPendingInvitedUser('invited@example.com');
        $this->createPendingMembership($team, $invitedUser, 'setup-token');

        $this->client->request('GET', '/invite/setup-token');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function test_setup_redirects_to_accept_for_verified_user(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Beta', $admin);
        $member = $this->createVerifiedUser('member@example.com');
        $this->createPendingMembership($team, $member, 'verified-token');

        $this->client->request('GET', '/invite/verified-token');

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/accept', $this->client->getResponse()->headers->get('Location'));
    }

    // --- POST /invite/{token} ---

    public function test_finish_returns_422_on_validation_error(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Gamma', $admin);
        $invitedUser = $this->createPendingInvitedUser('finish@example.com');
        $this->createPendingMembership($team, $invitedUser, 'finish-token');

        $this->client->request('POST', '/invite/finish-token', [
            'name' => 'Ok',
            'password' => 'short',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function test_finish_sets_up_account_and_activates_membership(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Delta', $admin);
        $invitedUser = $this->createPendingInvitedUser('newmember@example.com');
        $membership = $this->createPendingMembership($team, $invitedUser, 'complete-token');

        $this->client->request('POST', '/invite/complete-token', [
            'name' => 'New Member',
            'password' => 'securepassword123',
        ]);

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $this->em()->clear();
        $user = $this->em()->find(User::class, $invitedUser->getId());
        $this->assertTrue($user->isVerified());
        $this->assertSame('New Member', $user->getName());

        $fresh = $this->em()->find(Membership::class, $membership->getId());
        $this->assertSame(MembershipStatus::Active, $fresh->getStatus());
        $this->assertNull($fresh->getInviteToken());
    }

    // --- GET /invite/{token}/accept ---

    public function test_accept_returns_invalid_for_unknown_token(): void
    {
        $this->client->request('GET', '/invite/no-such-token/accept');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'invalid or has expired');
    }

    public function test_accept_shows_page_for_valid_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Epsilon', $admin);
        $member = $this->createVerifiedUser('member@example.com');
        $this->createPendingMembership($team, $member, 'accept-view-token');

        $this->client->request('GET', '/invite/accept-view-token/accept');

        $this->assertResponseIsSuccessful();
    }

    // --- POST /invite/{token}/accept ---

    public function test_accept_post_activates_membership(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Zeta', $admin);
        $member = $this->createVerifiedUser('zeta@example.com');
        $membership = $this->createPendingMembership($team, $member, 'accept-post-token');

        $this->client->request('POST', '/invite/accept-post-token/accept');

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $this->em()->clear();
        $fresh = $this->em()->find(Membership::class, $membership->getId());
        $this->assertSame(MembershipStatus::Active, $fresh->getStatus());
        $this->assertNull($fresh->getInviteToken());
    }

    // --- GET /invite/{token}/reject ---

    public function test_reject_rejects_membership_and_shows_page(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Eta', $admin);
        $member = $this->createVerifiedUser('eta@example.com');
        $membership = $this->createPendingMembership($team, $member, 'reject-token');

        $this->client->request('GET', '/invite/reject-token/reject');

        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $fresh = $this->em()->find(Membership::class, $membership->getId());
        $this->assertSame(MembershipStatus::Rejected, $fresh->getStatus());
        $this->assertNull($fresh->getInviteToken());
    }

    // --- pending membership with non-admin member should not grant team access ---

    public function test_pending_member_cannot_access_team(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $pending = $this->createVerifiedUser('pending@example.com');
        $team = $this->createTeamWithAdmin('Theta', $admin);
        $this->createPendingMembership($team, $pending, 'some-token');

        $this->client->loginUser($pending);
        $this->client->request('GET', '/team/' . $team->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
