<?php
namespace Modules\Analytics\Domain\ValueObjects;

class Revenue
{
    public function __construct(
        public float $amount,
        public string $currency = 'USD'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Revenue amount cannot be negative');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add revenues with different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract revenues with different currencies');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }
}
