<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Forecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Forecast>
 */
class ForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forecast::class);
    }

    public function save(Forecast $forecast): void
    {
        $this->getEntityManager()->persist($forecast);
    }

    public function delete(Forecast $forecast): void
    {
        $this->getEntityManager()->remove($forecast);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
