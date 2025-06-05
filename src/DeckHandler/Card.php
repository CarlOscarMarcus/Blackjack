<?php

namespace App\DeckHandler;

class Card
{
    private string $suit;
    private string $value;

    public function __construct(string $suit, string $value)
    {
        $this->suit = $suit;
        $this->value = $value;
    }

    public function getDisplay(): string
    {
        return $this->value . $this->suit;
    }

    public function getValue(): array
    {
        if (is_numeric($this->value)) {
            $intValue = (int)$this->value;
            return [$intValue];
        }

        if (in_array($this->value, ['J', 'Q', 'K'])) {
            return [10];
        }

        // Ess = 1 eller 11
        if ($this->value === 'A') {
            return [1, 11];
        }

        return [0]; // borde inte hända
    }
}
