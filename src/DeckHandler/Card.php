<?php

namespace App\DeckHandler;

class Card
{
    private int $suit;       // Stored as 1–4 (♠ ♥ ♦ ♣)
    private string $value;   // e.g., 'A', '2', ..., 'K'

    // Suit ID to symbol map
    private const SUIT_ID_TO_CHAR = [
        1 => '♠',
        2 => '♥',
        3 => '♦',
        4 => '♣',
    ];

    private const SUIT_CHAR_TO_ID = [
        '♠' => 1,
        '♥' => 2,
        '♦' => 3,
        '♣' => 4,
    ];


    public function __construct(int $suit, string $value)
    {
        $this->suit = $suit;
        $this->value = $value;
    }

    public function getSuitId(): int
    {
        return $this->suit;
    }

    public function getSuit(): string
    {
        return self::SUIT_ID_TO_CHAR[$this->suit] ?? '?';
    }

    public function getValue(): array
    {
        return match ($this->value) {
            'A' => [1, 11],
            'K', 'Q', 'J' => [10],
            default => [(int)$this->value]
        };
    }

    public function getDisplay(): string
    {
        return $this->getSuit() . $this->value;
    }

    public function getRawValue(): string
    {
        return $this->value;
    }

    public static function fromArray(Array $data): self
    {
        $char = $data[0];
        $value = $data[1];
        $suitId = self::SUIT_CHAR_TO_ID[$char] ?? 0;
        return new self($suitId, $value);
    }

    public static function fromString(string $cardString): self
    {
        $char = mb_substr($cardString, 0, 1);
        $value = mb_substr($cardString, 1);
        $suitId = self::SUIT_CHAR_TO_ID[$char] ?? 0;
        return new self($suitId, $value);
    }
}
