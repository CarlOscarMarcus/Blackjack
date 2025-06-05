<?php

namespace App\DeckHandler;

class Card
{
    private string $suit;
    private string $rank;

    public function __construct(string $suit, string $rank)
    {
        $this->suit = $suit;
        $this->rank = $rank;
    }

    public function getSuit(): string
    {
        return $this->suit;
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    /**
     * Returns the value(s) of the card.
     * @return int[] e.g., [10] for K, [1, 11] for A, [2] for 2
     */
    public function getValue(): array
    {
        return match ($this->rank) {
            'A' => [1, 11],
            'K', 'Q', 'J' => [10],
            default => [(int)$this->rank],
        };
    }

    public function getDisplay(): string
    {
        return $this->rank . $this->suit;
    }
}
