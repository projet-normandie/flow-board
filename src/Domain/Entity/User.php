<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\JobTitle;
use App\Domain\Entity\Trait\TimestampableTrait;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private string $password;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private string $fullName;

    #[ORM\Column(type: 'string', nullable: true, enumType: JobTitle::class)]
    private ?JobTitle $jobTitle = null;

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $invitationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invitationTokenExpiresAt = null;

    /** @var Collection<int, Card> */
    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'author')]
    private Collection $authoredCards;

    /** @var Collection<int, Card> */
    #[ORM\ManyToMany(targetEntity: Card::class, mappedBy: 'assignees')]
    private Collection $cards;

    public function __construct()
    {
        $this->authoredCards = new ArrayCollection();
        $this->cards = new ArrayCollection();
    }

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

    /** @return non-empty-string */
    public function getUserIdentifier(): string
    {
        assert($this->email !== '');

        return $this->email;
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

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getJobTitle(): ?JobTitle
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?JobTitle $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getAuthoredCards(): Collection
    {
        return $this->authoredCards;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function getInvitationToken(): ?string
    {
        return $this->invitationToken;
    }

    public function setInvitationToken(?string $invitationToken): static
    {
        $this->invitationToken = $invitationToken;

        return $this;
    }

    public function getInvitationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->invitationTokenExpiresAt;
    }

    public function setInvitationTokenExpiresAt(?\DateTimeImmutable $invitationTokenExpiresAt): static
    {
        $this->invitationTokenExpiresAt = $invitationTokenExpiresAt;

        return $this;
    }

    public function isInvitationTokenValid(): bool
    {
        return $this->invitationToken !== null
            && $this->invitationTokenExpiresAt !== null
            && $this->invitationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function eraseCredentials(): void
    {
    }

    public function __toString(): string
    {
        return $this->fullName;
    }
}
