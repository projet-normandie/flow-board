<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Trait\TimestampableTrait;
use App\Infrastructure\Doctrine\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Board
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'boards')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    /** @var Collection<int, Column> */
    #[ORM\OneToMany(targetEntity: Column::class, mappedBy: 'board')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $columns;

    public function __construct()
    {
        $this->columns = new ArrayCollection();
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

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Collection<int, Column>
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function addColumn(Column $column): static
    {
        if (!$this->columns->contains($column)) {
            $this->columns->add($column);
            $column->setBoard($this);
        }

        return $this;
    }

    public function removeColumn(Column $column): static
    {
        $this->columns->removeElement($column);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
