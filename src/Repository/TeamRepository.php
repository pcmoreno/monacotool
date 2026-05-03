<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Team;
use App\Entity\User;
use App\Enum\TeamRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function save(Team $team): void
    {
        $this->getEntityManager()->persist($team);
    }

    public function delete(Team $team): void
    {
        $this->getEntityManager()->remove($team);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function countAdminTeamsByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.memberships', 'm')
            ->where('m.user = :user')
            ->andWhere('m.role = :role')
            ->setParameter('user', $user)
            ->setParameter('role', TeamRole::Admin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Team[] */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('i')
            ->leftJoin('t.iterations', 'i')
            ->join('t.memberships', 'm')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /** @return Team[] */
    public function findAllWithIterations(): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('i')
            ->leftJoin('t.iterations', 'i')
            ->getQuery()
            ->getResult();
    }
}
