<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TenantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenant')]
class Tenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $address = '';

    #[ORM\ManyToOne(targetEntity: ContractType::class)]
    #[ORM\JoinColumn(name: 'contract_type_id', nullable: false)]
    private ContractType $contractType;

    #[ORM\OneToMany(targetEntity: Server::class, mappedBy: 'tenant')]
    private iterable $servers;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'tenant')]
    private iterable $users;

    public function getServers(): iterable
    {
        return $this->servers;
    }

    public function getUsers(): iterable
    {
        return $this->users;
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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getContractType(): ContractType
    {
        return $this->contractType;
    }

    public function setContractType(ContractType $contractType): self
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}