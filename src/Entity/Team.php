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

    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * @var Collection<int, Iteration>
     */
    #[ORM\OneToMany(targetEntity: Iteration::class, mappedBy: 'team', cascade: ['remove'])]
    #[ORM\OrderBy(['endDate' => 'ASC'])]
    private Collection $iterations;

    /**
     * @var Collection<int, Forecast>
     */
    #[ORM\OneToMany(targetEntity: Forecast::class, mappedBy: 'team', cascade: ['remove'])]
    private Collection $forecasts;

    /**
     * @var Collection<int, Membership>
     */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'team', cascade: ['persist', 'remove'])]
    private Collection $memberships;

    public function __construct()
    {
        $this->iterations = new ArrayCollection();
        $this->forecasts = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
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

    public function addIteration(Iteration $iteration): self
    {
        if (!$this->iterations->contains($iteration)) {
            $this->iterations->add($iteration);
            $iteration->setTeam($this);
        }

        return $this;
    }

    public function removeIteration(Iteration $iteration): self
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

    public function addForecast(Forecast $forecast): self
    {
        if (!$this->forecasts->contains($forecast)) {
            $this->forecasts->add($forecast);
            $forecast->setTeam($this);
        }

        return $this;
    }

    public function removeForecast(Forecast $forecast): self
    {
        if ($this->forecasts->removeElement($forecast)) {
            // set the owning side to null (unless already changed)
            if ($forecast->getTeam() === $this) {
                $forecast->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(Membership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setTeam($this);
        }

        return $this;
    }

    public function removeMembership(Membership $membership): self
    {
        $this->memberships->removeElement($membership);

        return $this;
    }

}
