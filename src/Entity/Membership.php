<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MembershipStatus;
use App\Enum\TeamRole;
use App\Repository\MembershipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MembershipRepository::class)]
#[ORM\UniqueConstraint(fields: ['user', 'team'])]
class Membership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(type: 'string', enumType: TeamRole::class)]
    private TeamRole $role;

    #[ORM\Column(type: 'string', enumType: MembershipStatus::class)]
    private MembershipStatus $status = MembershipStatus::Active;

    #[ORM\Column(nullable: true)]
    private ?string $inviteToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $inviteExpiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $inviterName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): self
    {
        $this->team = $team;

        return $this;
    }

    public function getRole(): TeamRole
    {
        return $this->role;
    }

    public function setRole(TeamRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getStatus(): MembershipStatus
    {
        return $this->status;
    }

    public function setStatus(MembershipStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getInviteToken(): ?string
    {
        return $this->inviteToken;
    }

    public function setInviteToken(?string $inviteToken): self
    {
        $this->inviteToken = $inviteToken;

        return $this;
    }

    public function getInviteExpiresAt(): ?\DateTimeImmutable
    {
        return $this->inviteExpiresAt;
    }

    public function setInviteExpiresAt(?\DateTimeImmutable $inviteExpiresAt): self
    {
        $this->inviteExpiresAt = $inviteExpiresAt;

        return $this;
    }

    public function getInviterName(): ?string
    {
        return $this->inviterName;
    }

    public function setInviterName(?string $inviterName): self
    {
        $this->inviterName = $inviterName;

        return $this;
    }

    public function isInviteExpired(): bool
    {
        return $this->inviteExpiresAt !== null && $this->inviteExpiresAt < new \DateTimeImmutable();
    }
}
