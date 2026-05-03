<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Membership;
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

    public function findByInviteToken(string $hashedToken): ?Membership
    {
        return $this->findOneBy(['inviteToken' => $hashedToken]);
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
