<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Membership;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\MembershipStatus;
use App\Enum\TeamRole;
use App\Security\TeamVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TeamVoterTest extends TestCase
{
    private TeamVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TeamVoter();
    }

    public function test_abstains_for_unsupported_attribute(): void
    {
        $this->assertVote(VoterInterface::ACCESS_ABSTAIN, $this->user(), new Team(), 'team.unknown');
    }

    public function test_abstains_for_unsupported_subject(): void
    {
        $this->assertVote(VoterInterface::ACCESS_ABSTAIN, $this->user(), new \stdClass(), TeamVoter::VIEW);
    }

    public function test_denies_when_token_has_no_user(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, new Team(), [TeamVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, new Team(), [TeamVoter::EDIT]));
    }

    public function test_super_admin_can_view_any_team(): void
    {
        $this->assertVote(VoterInterface::ACCESS_GRANTED, $this->superAdmin(), new Team(), TeamVoter::VIEW);
    }

    public function test_super_admin_can_edit_any_team(): void
    {
        $this->assertVote(VoterInterface::ACCESS_GRANTED, $this->superAdmin(), new Team(), TeamVoter::EDIT);
    }

    public function test_non_member_cannot_view(): void
    {
        $team = $this->teamWithMember($this->user(), TeamRole::Admin);

        $this->assertVote(VoterInterface::ACCESS_DENIED, $this->user(), $team, TeamVoter::VIEW);
    }

    public function test_non_member_cannot_edit(): void
    {
        $team = $this->teamWithMember($this->user(), TeamRole::Admin);

        $this->assertVote(VoterInterface::ACCESS_DENIED, $this->user(), $team, TeamVoter::EDIT);
    }

    public function test_user_member_can_view(): void
    {
        $user = $this->user();
        $team = $this->teamWithMember($user, TeamRole::User);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, $user, $team, TeamVoter::VIEW);
    }

    public function test_user_member_cannot_edit(): void
    {
        $user = $this->user();
        $team = $this->teamWithMember($user, TeamRole::User);

        $this->assertVote(VoterInterface::ACCESS_DENIED, $user, $team, TeamVoter::EDIT);
    }

    public function test_admin_member_can_view(): void
    {
        $user = $this->user();
        $team = $this->teamWithMember($user, TeamRole::Admin);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, $user, $team, TeamVoter::VIEW);
    }

    public function test_admin_member_can_edit(): void
    {
        $user = $this->user();
        $team = $this->teamWithMember($user, TeamRole::Admin);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, $user, $team, TeamVoter::EDIT);
    }

    public function test_pending_member_cannot_view(): void
    {
        $user = $this->user();
        $team = $this->teamWithMemberStatus($user, TeamRole::User, MembershipStatus::Pending);

        $this->assertVote(VoterInterface::ACCESS_DENIED, $user, $team, TeamVoter::VIEW);
    }

    public function test_rejected_member_cannot_view(): void
    {
        $user = $this->user();
        $team = $this->teamWithMemberStatus($user, TeamRole::Admin, MembershipStatus::Rejected);

        $this->assertVote(VoterInterface::ACCESS_DENIED, $user, $team, TeamVoter::VIEW);
    }

    private function assertVote(int $expected, User|\stdClass $user, object $subject, string $attribute): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->assertSame($expected, $this->voter->vote($token, $subject, [$attribute]));
    }

    private function user(): User
    {
        $user = new User();
        $user->setEmail(uniqid('user_') . '@example.com');

        return $user;
    }

    private function superAdmin(): User
    {
        $user = $this->user();
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        return $user;
    }

    private function teamWithMember(User $user, TeamRole $role): Team
    {
        return $this->teamWithMemberStatus($user, $role, MembershipStatus::Active);
    }

    private function teamWithMemberStatus(User $user, TeamRole $role, MembershipStatus $status): Team
    {
        $membership = new Membership();
        $membership->setUser($user);
        $membership->setRole($role);
        $membership->setStatus($status);

        $team = new Team();
        $team->addMembership($membership);

        return $team;
    }
}
