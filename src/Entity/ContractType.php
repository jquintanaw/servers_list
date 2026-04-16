<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContractTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractTypeRepository::class)]
#[ORM\Table(name: 'contract_type')]
class ContractType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\OneToMany(targetEntity: Tenant::class, mappedBy: 'contractType')]
    private iterable $tenants;

    public function getTenants(): iterable
    {
        return $this->tenants;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}