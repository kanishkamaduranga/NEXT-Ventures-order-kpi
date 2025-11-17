<?php
namespace Modules\Analytics\Domain\ValueObjects;

class KpiDate
{
    public function __construct(
        public string $date // YYYY-MM-DD format
    ) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('Invalid date format. Expected YYYY-MM-DD');
        }
    }

    public function toString(): string
    {
        return $this->date;
    }

    public static function today(): self
    {
        return new self(now()->format('Y-m-d'));
    }

    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return new self($dateTime->format('Y-m-d'));
    }
}
