<?php

namespace App\Facades {

    class Currency
    {
        static public function from(string $currency): self
        {
            return $this;
        }

        static public function to(string $currency): self
        {
            return $this;
        }

        static public function convert(float $amount): float
        {
            return 0;
        }
    }
}
