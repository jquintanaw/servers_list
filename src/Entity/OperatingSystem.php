<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OsRepository::class)]
#[ORM\Table(name: 'operating_system')]
class OperatingSystem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name = '';

    #[ORM\Column(length: 20)]
    private string $color = 'info';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'operatingSystem', targetEntity: OperatingSystemVersion::class, cascade: ['persist', 'remove'])]
    private Collection $versions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->versions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, OperatingSystemVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(OperatingSystemVersion $version): static
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
            $version->setOperatingSystem($this);
        }
        return $this;
    }

    public function removeVersion(OperatingSystemVersion $version): static
    {
        if ($this->versions->removeElement($version)) {
            if ($version->getOperatingSystem() === $this) {
                $version->setOperatingSystem(null);
            }
        }
        return $this;
    }
}