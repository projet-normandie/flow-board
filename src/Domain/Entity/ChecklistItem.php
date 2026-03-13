<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\ChecklistItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChecklistItemRepository::class)]
class ChecklistItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column]
    private int $position;

    #[ORM\Column]
    private bool $checked = false;

    #[ORM\ManyToOne(targetEntity: Checklist::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Checklist $checklist;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): static
    {
        $this->checked = $checked;

        return $this;
    }

    public function getChecklist(): Checklist
    {
        return $this->checklist;
    }

    public function setChecklist(Checklist $checklist): static
    {
        $this->checklist = $checklist;

        return $this;
    }
}
