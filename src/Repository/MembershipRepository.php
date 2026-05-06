<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Membership;
use App\Entity\Team;
use App\Enum\MembershipStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membership>
 */
class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    public function findPendingByInviteToken(string $hashedToken): ?Membership
    {
        return $this->findOneBy(['inviteToken' => $hashedToken, 'status' => MembershipStatus::Pending]);
    }

    public function findActiveOrPendingByTeamAndEmail(Team $team, string $email): ?Membership
    {
        return $this->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->where('m.team = :team')
            ->andWhere('u.email = :email')
            ->andWhere('m.status IN (:statuses)')
            ->setParameter('team', $team)
            ->setParameter('email', $email)
            ->setParameter('statuses', [MembershipStatus::Active, MembershipStatus::Pending])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Membership $membership): void
    {
        $this->getEntityManager()->persist($membership);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
