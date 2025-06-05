<?php

namespace App\DeckHandler;

class Deck
{
    /**
     * @var Card[]
     */
    private array $cards = [];

    public function __construct()
    {
        $this->initialize();
        $this->shuffle();
    }

    private function initialize(): void
    {
        $suits = ['♠', '♥', '♦', '♣'];
        $values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

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

    /**
     * Drar ett kort från toppen av kortleken
     * @return Card|null Om inga kort kvar, returnera null
     */
    public function draw(): ?Card
    {
        return array_shift($this->cards) ?: null;
    }

    /**
     * Returnerar antal kort kvar i leken
     */
    public function count(): int
    {
        return count($this->cards);
    }

    /**
     * Returnerar alla kort i leken
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }
}
