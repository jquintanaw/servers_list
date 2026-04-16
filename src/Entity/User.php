<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private string $password = '';

    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_MAINTENANCE = 'maintenance';

    #[ORM\Column(length: 20)]
    private string $status = 'enabled';

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $apiToken = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $socialNetworks = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', nullable: true)]
    private ?Tenant $tenant = null;

    public const AVATAR_UPLOAD_DIR = 'var/uploads/avatars';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
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

    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): static
    {
        $this->apiToken = $apiToken;
        return $this;
    }

    public function generateApiToken(): string
    {
        $this->apiToken = bin2hex(random_bytes(32));
        return $this->apiToken;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getSocialNetworks(): ?array
    {
        return $this->socialNetworks;
    }

    public function setSocialNetworks(?array $socialNetworks): static
    {
        $this->socialNetworks = $socialNetworks;
        return $this;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): static
    {
        $this->avatarFilename = $avatarFilename;
        return $this;
    }

    public function getAvatarPath(): ?string
    {
        if (null === $this->avatarFilename) {
            return null;
        }
        return '/uploads/avatars/' . $this->avatarFilename;
    }

    public function removeAvatar(): void
    {
        if (null !== $this->avatarFilename) {
            $filePath = __DIR__ . '/../../' . self::AVATAR_UPLOAD_DIR . '/' . $this->avatarFilename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $this->avatarFilename = null;
        }
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
