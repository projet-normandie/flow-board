<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\ChecklistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChecklistRepository::class)]
class Checklist
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

    #[ORM\ManyToOne(targetEntity: Card::class, inversedBy: 'checklists')]
    #[ORM\JoinColumn(nullable: false)]
    private Card $card;

    /** @var Collection<int, ChecklistItem> */
    #[ORM\OneToMany(targetEntity: ChecklistItem::class, mappedBy: 'checklist', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

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

    public function getCard(): Card
    {
        return $this->card;
    }

    public function setCard(Card $card): static
    {
        $this->card = $card;

        return $this;
    }

    /**
     * @return Collection<int, ChecklistItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ChecklistItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setChecklist($this);
        }

        return $this;
    }

    public function removeItem(ChecklistItem $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function getCheckedCount(): int
    {
        return $this->items->filter(fn (ChecklistItem $item) => $item->isChecked())->count();
    }

    public function getTotalCount(): int
    {
        return $this->items->count();
    }
}
