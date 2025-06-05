<?php

namespace App\DeckHandler;

class Deck
{
    private array $cards = [];

    public function __construct()
    {
        $this->initializeDeck();
    }

    private function initializeDeck(): void
    {
        $suits = [1, 2, 3, 4];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

        $this->cards = [];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $this->cards[] = new Card($suit, $value);
            }
        }
    }

    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    public function draw(): ?Card
    {
        return array_pop($this->cards) ?: null;
    }

    public function cardsLeft(): int
    {
        return count($this->cards);
    }

    public function toArray(): array
    {
        return array_map(fn($card) => [$card->getSuit(), $card->getValue()], $this->cards);
    }

    // Recreate deck from array of arrays [[suit, value], ...]
    public static function fromArray(array $data): self
    {
        $deck = new self();
        $deck->cards = [];

        foreach ($data as [$suit, $value]) {
            $deck->cards[] = Card::fromArray([$suit, (string)$value[0]]);
        }

        return $deck;
    }

    public function peek(): ?Card
    {
        return $this->cards[count($this->cards) - 1] ?? null;  // Returns the first card or null if empty
    }

}
