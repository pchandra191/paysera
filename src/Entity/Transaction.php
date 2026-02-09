<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $fromAccount;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $toAccount;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    // Use datetime_immutable to match DateTimeImmutable
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getFromAccount(): Account { return $this->fromAccount; }
    public function setFromAccount(Account $account): self { $this->fromAccount = $account; return $this; }

    public function getToAccount(): Account { return $this->toAccount; }
    public function setToAccount(Account $account): self { $this->toAccount = $account; return $this; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(float $amount): self { $this->amount = (string)$amount; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
