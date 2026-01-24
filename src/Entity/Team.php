<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, Iteration>
     */
    #[ORM\OneToMany(targetEntity: Iteration::class, mappedBy: 'team')]
    private Collection $iterations;

    /**
     * @var Collection<int, Forecast>
     */
    #[ORM\OneToMany(targetEntity: Forecast::class, mappedBy: 'team')]
    private Collection $forecasts;

    public function __construct()
    {
        $this->iterations = new ArrayCollection();
        $this->forecasts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Iteration>
     */
    public function getIterations(): Collection
    {
        return $this->iterations;
    }

    public function addIteration(Iteration $iteration): static
    {
        if (!$this->iterations->contains($iteration)) {
            $this->iterations->add($iteration);
            $iteration->setTeam($this);
        }

        return $this;
    }

    public function removeIteration(Iteration $iteration): static
    {
        if ($this->iterations->removeElement($iteration)) {
            // set the owning side to null (unless already changed)
            if ($iteration->getTeam() === $this) {
                $iteration->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Forecast>
     */
    public function getForecasts(): Collection
    {
        return $this->forecasts;
    }

    public function addForecast(Forecast $forecast): static
    {
        if (!$this->forecasts->contains($forecast)) {
            $this->forecasts->add($forecast);
            $forecast->setTeam($this);
        }

        return $this;
    }

    public function removeForecast(Forecast $forecast): static
    {
        if ($this->forecasts->removeElement($forecast)) {
            // set the owning side to null (unless already changed)
            if ($forecast->getTeam() === $this) {
                $forecast->setTeam(null);
            }
        }

        return $this;
    }

    public function getOutputAverage(): float
    {
        if ($this->iterations->isEmpty()) {
            return 0;
        }

        $count = $this->iterations->count();
        $sum = 0;
        foreach ($this->iterations as $iteration) {
            $sum += $iteration->getOutput();
        }

        return $sum / $count;
    }

    public function getStandardDeviation(): float
    {
        $iterations = $this->iterations;
        $mean = $this->getOutputAverage();
        $sumOfVarianceForAllDataPoints = 0;
        foreach ($iterations as $iteration) {
            $variance = $iteration->getOutput() - $mean;
            $squaredVariance = $variance * $variance;
            $sumOfVarianceForAllDataPoints += $squaredVariance;
        }

        return sqrt($sumOfVarianceForAllDataPoints / ($iterations->count() - 1));
    }
}
