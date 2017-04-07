<?php

namespace App\Services;

use InvalidArgumentException;

class CurrencyConverter
{
    protected $from = 'INR';

    protected $to = 'INR';

    public function from(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function convert(float $amount): float
    {
        if ($this->from === $this->to) {
            return $amount;
        }

        throw new InvalidArgumentException("Conversion from {$this->from} to {$this->to} is not supported.");
    }
}
