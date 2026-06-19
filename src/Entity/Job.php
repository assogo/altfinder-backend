<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\Table(name: 'job')]
#[ORM\Index(columns: ['external_id'], name: 'idx_external_id')]
#[ORM\Index(columns: ['status'], name: 'idx_status')]
class Job
{
    public const STATUS_OPEN = 'open';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PROBABLY_CLOSED = 'probably_closed';

    private const FALLBACK_DURATION_DAYS = 30;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $externalId;

    #[ORM\Column(length: 50)]
    private string $source = 'france_travail';

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contractType = null;

    #[ORM\Column]
    private bool $isAlternance = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column]
    private \DateTimeImmutable $publishedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
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

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
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

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(?string $contractType): static
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function isAlternance(): bool
    {
        return $this->isAlternance;
    }

    public function setIsAlternance(bool $isAlternance): static
    {
        $this->isAlternance = $isAlternance;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getPublishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function refreshStatus(?bool $linkStillResponds = null): static
    {
        $now = new \DateTimeImmutable();

        if ($this->expiresAt !== null) {
            $this->status = $this->expiresAt < $now ? self::STATUS_EXPIRED : self::STATUS_OPEN;
            $this->touch();
            return $this;
        }

        if ($linkStillResponds !== null) {
            $this->status = $linkStillResponds ? self::STATUS_OPEN : self::STATUS_PROBABLY_CLOSED;
            $this->touch();
            return $this;
        }

        $estimatedExpiry = $this->publishedAt->modify(sprintf('+%d days', self::FALLBACK_DURATION_DAYS));
        $this->status = $estimatedExpiry < $now ? self::STATUS_PROBABLY_CLOSED : self::STATUS_OPEN;
        $this->touch();

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->externalId,
            'source' => $this->source,
            'title' => $this->title,
            'company' => $this->company,
            'location' => $this->location,
            'description' => $this->description,
            'contractType' => $this->contractType,
            'isAlternance' => $this->isAlternance,
            'category' => $this->category,
            'url' => $this->url,
            'publishedAt' => $this->publishedAt->format(DATE_ATOM),
            'expiresAt' => $this->expiresAt?->format(DATE_ATOM),
            'status' => $this->status,
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}