<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Iteration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Iteration>
 */
class IterationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Iteration::class);
    }

    public function save(Iteration $iteration): void
    {
        $this->getEntityManager()->persist($iteration);
    }

    public function delete(Iteration $iteration): void
    {
        $this->getEntityManager()->remove($iteration);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
