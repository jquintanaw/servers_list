<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ServerRepository;
use App\Entity\ServerGroup;
use App\Entity\ServerTag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ORM\Table(name: 'server')]
class Server
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 50)]
    private string $os = '';

    #[ORM\Column(length: 50)]
    private string $osVersion = '';

    #[ORM\ManyToOne(targetEntity: OperatingSystemVersion::class)]
    #[ORM\JoinColumn(name: 'operating_system_version_id', nullable: true)]
    private ?OperatingSystemVersion $operatingSystemVersion = null;

    #[ORM\Column(length: 100)]
    private string $managementIp = '';

    #[ORM\Column(length: 50)]
    private string $sshUser = '';

    #[ORM\Column(length: 255)]
    private string $sshPassword = '';

    #[ORM\Column(length: 20)]
    private string $cpu = '';

    #[ORM\Column(length: 20)]
    private string $ram = '';

    #[ORM\Column(length: 20)]
    private string $hd = '';

    #[ORM\Column(length: 100)]
    private string $provider = '';

    #[ORM\Column(length: 100)]
    private string $site = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $status = 'active';

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: 'servers', cascade: ['remove'])]
    #[ORM\JoinTable(name: 'server_service')]
    private Collection $services;

    #[ORM\ManyToMany(targetEntity: ServerGroup::class, inversedBy: 'servers', cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'server_server_group')]
    private Collection $groups;

    #[ORM\ManyToMany(targetEntity: ServerTag::class, inversedBy: 'servers', cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'server_server_tag')]
    private Collection $tags;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', nullable: true)]
    private ?Tenant $tenant = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->tags = new ArrayCollection();
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

    public function getOs(): string
    {
        return $this->operatingSystemVersion?->getOperatingSystem()?->getName() ?? $this->os;
    }

    public function setOs(string $os): static
    {
        $this->os = $os;

        return $this;
    }

    public function getOsVersion(): string
    {
        return $this->operatingSystemVersion?->getVersion() ?? $this->osVersion;
    }

    public function setOsVersion(string $osVersion): static
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    public function getOperatingSystemVersion(): ?OperatingSystemVersion
    {
        return $this->operatingSystemVersion;
    }

    public function setOperatingSystemVersion(?OperatingSystemVersion $operatingSystemVersion): static
    {
        $this->operatingSystemVersion = $operatingSystemVersion;

        return $this;
    }

    public function getManagementIp(): string
    {
        return $this->managementIp;
    }

    public function setManagementIp(string $managementIp): static
    {
        $this->managementIp = $managementIp;

        return $this;
    }

    public function getSshUser(): string
    {
        return $this->sshUser;
    }

    public function setSshUser(string $sshUser): static
    {
        $this->sshUser = $sshUser;

        return $this;
    }

    public function getSshPassword(): string
    {
        return $this->sshPassword;
    }

    public function setSshPassword(string $sshPassword): static
    {
        $this->sshPassword = $sshPassword;

        return $this;
    }

    public function getCpu(): string
    {
        return $this->cpu;
    }

    public function setCpu(string $cpu): static
    {
        $this->cpu = $cpu;

        return $this;
    }

    public function getRam(): string
    {
        return $this->ram;
    }

    public function setRam(string $ram): static
    {
        $this->ram = $ram;

        return $this;
    }

    public function getHd(): string
    {
        return $this->hd;
    }

    public function setHd(string $hd): static
    {
        $this->hd = $hd;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getSite(): string
    {
        return $this->site;
    }

    public function setSite(string $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        $this->services->removeElement($service);

        return $this;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(ServerGroup $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }

        return $this;
    }

    public function removeGroup(ServerGroup $group): static
    {
        $this->groups->removeElement($group);

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(ServerTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(ServerTag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
    }
}
