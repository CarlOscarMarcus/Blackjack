<?php

namespace App\DeckHandler;

class Player
{
    private array $hand = [];
    private bool $hasStayed = false;
    private bool $isBust = false;
    private bool $hasBlackjack = false;

    public function addCard(Card $card): void
    {
        $this->hand[] = $card;
        $this->updateStatus();
    }

    public function stay(): void
    {
        $this->hasStayed = true;
    }

    public function reset(): void
    {
        $this->hand = [];
        $this->hasStayed = false;
        $this->isBust = false;
        $this->hasBlackjack = false;
    }

    public function getHand(): array
    {
        return $this->hand;
    }

    public function hasStayed(): bool
    {
        return $this->hasStayed;
    }

    public function isBust(): bool
    {
        return $this->isBust;
    }

    public function hasBlackjack(): bool
    {
        return $this->hasBlackjack;
    }

    public function getTotals(): array
    {
        $low = 0;
        $high = 0;

        foreach ($this->hand as $card) {
            $vals = $card->getValue();
            $low += $vals[0];
            $high += $vals[1] ?? $vals[0];
        }

        return [$low, $high];
    }

    private function updateStatus(): void
    {
        [$low, $high] = $this->getTotals();

        if ($low > 21 && $high > 21) {
            $this->isBust = true;
        }

        if (($high === 21 || $low === 21) && !$this->hasStayed) {
            $this->hasBlackjack = true;
            $this->hasStayed = true;
        }
    }

    public function toArray(): array
    {
        return [
            'hand' => array_map(fn($card) => $card->getDisplay(), $this->hand),
            'hasStayed' => $this->hasStayed,
            'isBust' => $this->isBust,
            'hasBlackjack' => $this->hasBlackjack,
        ];
    }

    public static function fromArray(array $data): self
    {
        $player = new self();
        $player->hand = array_map(fn($cardData) => Card::fromString($cardData), $data['hand']);
        $player->hasStayed = $data['hasStayed'] ?? false;
        $player->isBust = $data['isBust'] ?? false;
        $player->hasBlackjack = $data['hasBlackjack'] ?? false;
        return $player;
    }
}
