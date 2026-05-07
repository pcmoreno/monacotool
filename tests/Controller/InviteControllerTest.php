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
        $this->assertSelectorTextContains('p', 'invalid');
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

    public function test_finish_returns_422_on_short_password(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Gamma', $admin);
        $invitedUser = $this->createPendingInvitedUser('finish@example.com');
        $this->createPendingMembership($team, $invitedUser, 'finish-token');

        $crawler = $this->client->request('GET', '/invite/finish-token');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/finish-token', [
            'name' => 'Ok',
            'password' => 'short',
            '_token' => $csrfToken,
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function test_finish_sets_up_account_and_activates_membership(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Delta', $admin);
        $invitedUser = $this->createPendingInvitedUser('newmember@example.com');
        $membership = $this->createPendingMembership($team, $invitedUser, 'complete-token');

        $crawler = $this->client->request('GET', '/invite/complete-token');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/complete-token', [
            'name' => 'New Member',
            'password' => 'securepassword123',
            '_token' => $csrfToken,
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
        $this->assertSelectorTextContains('p', 'invalid');
    }

    public function test_accept_shows_page_for_valid_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Epsilon', $admin);
        $member = $this->createVerifiedUser('member@example.com');
        $this->createPendingMembership($team, $member, 'accept-view-token');

        $this->client->loginUser($member);
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

        $this->client->loginUser($member);
        $crawler = $this->client->request('GET', '/invite/accept-post-token/accept');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/accept-post-token/accept', ['_token' => $csrfToken]);

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $this->em()->clear();
        $fresh = $this->em()->find(Membership::class, $membership->getId());
        $this->assertSame(MembershipStatus::Active, $fresh->getStatus());
        $this->assertNull($fresh->getInviteToken());
    }

    // --- GET /invite/{token}/reject ---

    public function test_reject_shows_confirmation_page_without_rejecting(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Eta', $admin);
        $member = $this->createVerifiedUser('eta@example.com');
        $membership = $this->createPendingMembership($team, $member, 'reject-token');

        $this->client->loginUser($member);
        $this->client->request('GET', '/invite/reject-token/reject');

        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $fresh = $this->em()->find(Membership::class, $membership->getId());
        $this->assertSame(MembershipStatus::Pending, $fresh->getStatus());
    }

    // --- POST /invite/{token}/reject ---

    public function test_reject_post_rejects_membership(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Iota', $admin);
        $member = $this->createVerifiedUser('iota@example.com');
        $membership = $this->createPendingMembership($team, $member, 'reject-post-token');

        $this->client->loginUser($member);
        $crawler = $this->client->request('GET', '/invite/reject-post-token/reject');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/reject-post-token/reject', ['_token' => $csrfToken]);

        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $fresh = $this->em()->find(Membership::class, $membership->getId());
        $this->assertSame(MembershipStatus::Rejected, $fresh->getStatus());
        $this->assertNull($fresh->getInviteToken());
    }

    // --- Security path tests ---

    public function test_accept_redirects_unauthenticated_user_to_login(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Kappa', $admin);
        $member = $this->createVerifiedUser('kappa@example.com');
        $this->createPendingMembership($team, $member, 'unauthed-token');

        $this->client->request('GET', '/invite/unauthed-token/accept');

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/login', $location);
    }

    public function test_accept_shows_invalid_when_wrong_user_is_logged_in(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Lambda', $admin);
        $invitedUser = $this->createVerifiedUser('invited@example.com');
        $wrongUser = $this->createVerifiedUser('wrong@example.com');
        $this->createPendingMembership($team, $invitedUser, 'wrong-user-token');

        $this->client->loginUser($wrongUser);
        $this->client->request('GET', '/invite/wrong-user-token/accept');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'invalid');
    }

    public function test_setup_shows_expired_message_for_expired_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Mu', $admin);
        $invitedUser = $this->createPendingInvitedUser('expired@example.com');
        $this->createExpiredMembership($team, $invitedUser, 'expired-token');

        $this->client->request('GET', '/invite/expired-token');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'expired');
    }

    public function test_accept_post_shows_invalid_after_token_is_consumed(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Nu', $admin);
        $member = $this->createVerifiedUser('nu@example.com');
        $this->createPendingMembership($team, $member, 'replay-token');

        $this->client->loginUser($member);
        $crawler = $this->client->request('GET', '/invite/replay-token/accept');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        // First accept — should succeed
        $this->client->request('POST', '/invite/replay-token/accept', ['_token' => $csrfToken]);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        // Replay — token already consumed
        $this->client->request('GET', '/invite/replay-token/accept');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'invalid');
    }

    public function test_accept_post_shows_invalid_when_wrong_user_posts(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Omicron', $admin);
        $invitedUser = $this->createVerifiedUser('invited@example.com');
        $wrongUser = $this->createVerifiedUser('wrong@example.com');
        $this->createPendingMembership($team, $invitedUser, 'wrong-post-token');

        $this->client->loginUser($invitedUser);
        $crawler = $this->client->request('GET', '/invite/wrong-post-token/accept');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->loginUser($wrongUser);
        $this->client->request('POST', '/invite/wrong-post-token/accept', ['_token' => $csrfToken]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'invalid');
    }

    public function test_reject_post_shows_invalid_when_wrong_user_posts(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Pi', $admin);
        $invitedUser = $this->createVerifiedUser('invited@example.com');
        $wrongUser = $this->createVerifiedUser('wrong@example.com');
        $this->createPendingMembership($team, $invitedUser, 'reject-wrong-token');

        $this->client->loginUser($invitedUser);
        $crawler = $this->client->request('GET', '/invite/reject-wrong-token/reject');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->loginUser($wrongUser);
        $this->client->request('POST', '/invite/reject-wrong-token/reject', ['_token' => $csrfToken]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'invalid');
    }

    public function test_reject_redirects_unauthenticated_user_to_login(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Rho', $admin);
        $member = $this->createVerifiedUser('rho@example.com');
        $this->createPendingMembership($team, $member, 'unauthed-reject-token');

        $this->client->request('GET', '/invite/unauthed-reject-token/reject');

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/login', $this->client->getResponse()->headers->get('Location'));
    }

    public function test_finish_returns_422_on_short_name(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Sigma', $admin);
        $invitedUser = $this->createPendingInvitedUser('sigma@example.com');
        $this->createPendingMembership($team, $invitedUser, 'short-name-token');

        $crawler = $this->client->request('GET', '/invite/short-name-token');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/short-name-token', [
            'name' => 'A',
            'password' => 'validpassword123',
            '_token' => $csrfToken,
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    // --- Expired token tests ---

    public function test_finish_returns_invalid_for_expired_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Tau', $admin);
        $validUser = $this->createPendingInvitedUser('tau-valid@example.com');
        $this->createPendingMembership($team, $validUser, 'tau-valid-token');
        $expiredUser = $this->createPendingInvitedUser('tau-expired@example.com');
        $this->createExpiredMembership($team, $expiredUser, 'tau-expired-token');

        $crawler = $this->client->request('GET', '/invite/tau-valid-token');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/tau-expired-token', [
            'name' => 'Test User',
            'password' => 'validpass123',
            '_token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'expired');
    }

    public function test_accept_returns_invalid_for_expired_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Upsilon', $admin);
        $member = $this->createVerifiedUser('upsilon@example.com');
        $this->createExpiredMembership($team, $member, 'upsilon-expired-token');

        $this->client->request('GET', '/invite/upsilon-expired-token/accept');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'expired');
    }

    public function test_accept_post_returns_invalid_for_expired_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Phi', $admin);
        $validMember = $this->createVerifiedUser('phi-valid@example.com');
        $this->createPendingMembership($team, $validMember, 'phi-valid-token');
        $expiredMember = $this->createVerifiedUser('phi-expired@example.com');
        $this->createExpiredMembership($team, $expiredMember, 'phi-expired-token');

        $this->client->loginUser($validMember);
        $crawler = $this->client->request('GET', '/invite/phi-valid-token/accept');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/phi-expired-token/accept', ['_token' => $csrfToken]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'expired');
    }

    public function test_reject_returns_invalid_for_expired_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Chi', $admin);
        $member = $this->createVerifiedUser('chi@example.com');
        $this->createExpiredMembership($team, $member, 'chi-expired-token');

        $this->client->request('GET', '/invite/chi-expired-token/reject');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'expired');
    }

    public function test_reject_post_returns_invalid_for_expired_token(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Psi', $admin);
        $validMember = $this->createVerifiedUser('psi-valid@example.com');
        $this->createPendingMembership($team, $validMember, 'psi-valid-token');
        $expiredMember = $this->createVerifiedUser('psi-expired@example.com');
        $this->createExpiredMembership($team, $expiredMember, 'psi-expired-token');

        $this->client->loginUser($validMember);
        $crawler = $this->client->request('GET', '/invite/psi-valid-token/reject');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/psi-expired-token/reject', ['_token' => $csrfToken]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'expired');
    }

    // --- finish() with verified user ---

    public function test_finish_returns_invalid_for_verified_user(): void
    {
        $admin = $this->createVerifiedUser('admin@example.com');
        $team = $this->createTeamWithAdmin('Omega', $admin);
        $verifiedMember = $this->createVerifiedUser('omega-verified@example.com');
        $this->createPendingMembership($team, $verifiedMember, 'omega-verified-token');
        $unverifiedUser = $this->createPendingInvitedUser('omega-unverified@example.com');
        $this->createPendingMembership($team, $unverifiedUser, 'omega-setup-token');

        $crawler = $this->client->request('GET', '/invite/omega-setup-token');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/invite/omega-verified-token', [
            'name' => 'Verified User',
            'password' => 'validpass123',
            '_token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'invalid');
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
