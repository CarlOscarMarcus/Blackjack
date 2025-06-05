<?php

namespace App\Controller;

use App\DeckHandler\Card;
use App\DeckHandler\Deck;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class BlackjackController extends AbstractController
{
    #[Route('/', name: 'blackjack_index')]
    public function index(SessionInterface $session): Response
    {
        // Load deck or create new deck if missing
        $deckData = $session->get('deck');
        if (!$deckData) {
            $deck = new Deck();
            $deck->shuffle();
            $session->set('deck', $deck->toArray());
        } else {
            $deck = Deck::fromArray($deckData);
        }

        // Load current hand & state
        $hand = $session->get('hand', []);
        $hasStayed = $session->get('has_stayed', false);
        $isBust = $session->get('is_bust', false);
        $hasBlackjack = $session->get('has_blackjack', false);

        // Calculate totals
        [$totalLow, $totalHigh] = $this->calculateTotals($hand);

        // Decide if bust or blackjack if not yet set
        if (!$isBust) {
            if ($totalLow > 21 && $totalHigh > 21) {
                $isBust = true;
                $session->set('is_bust', true);
            }
        }

        if (!$hasBlackjack && !$hasStayed) {
            if ($totalHigh === 21 || $totalLow === 21) {
                $hasBlackjack = true;
                $session->set('has_blackjack', true);
                // Auto stay on blackjack
                $session->set('has_stayed', true);
                $hasStayed = true;
            }
        }

        return $this->render('blackjack/index.html.twig', [
            'hand' => $hand,
            'hasStayed' => $hasStayed,
            'isBust' => $isBust,
            'hasBlackjack' => $hasBlackjack,
            'totalLow' => $totalLow,
            'totalHigh' => ($totalHigh <= 21 ? $totalHigh : null),
            'deckCount' => $deck->cardsLeft(),
            'nextCard' => $deck->peek(),
        ]);
    }

    #[Route('/hit', name: 'blackjack_hit', methods: ['POST'])]
    public function hit(SessionInterface $session): Response
    {
        // Load deck
        $deckData = $session->get('deck');
        if (!$deckData) {
            $deck = new Deck();
            $deck->shuffle();
        } else {
            $deck = Deck::fromArray($deckData);
        }

        $hand = $session->get('hand', []);
        $hasStayed = $session->get('has_stayed', false);
        $isBust = $session->get('is_bust', false);
        $hasBlackjack = $session->get('has_blackjack', false);

        if (!$hasStayed && !$isBust && !$hasBlackjack) {
            $card = $deck->draw();

            if ($card) {
                $hand[] = $card;
                $session->set('hand', $hand);

                // Save updated deck immediately after draw
                $session->set('deck', $deck->toArray());

                // Recalculate totals
                [$totalLow, $totalHigh] = $this->calculateTotals($hand);

                // Bust check
                if ($totalLow > 21 && $totalHigh > 21) {
                    $session->set('is_bust', true);
                }

                // Blackjack check (auto stay)
                if ($totalHigh === 21 || $totalLow === 21) {
                    $session->set('has_blackjack', true);
                    $session->set('has_stayed', true);
                }
            }
        }

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/stay', name: 'blackjack_stay', methods: ['POST'])]
    public function stay(SessionInterface $session): Response
    {
        $session->set('has_stayed', true);
        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/reset', name: 'blackjack_reset')] // , methods: ['POST']
    public function reset(SessionInterface $session): Response
    {
        $deck = new Deck();
        $deck->shuffle();
        $session->set('deck', $deck->toArray());

        $session->remove('hand');
        $session->remove('has_stayed');
        $session->remove('is_bust');
        $session->remove('has_blackjack');

        return $this->redirectToRoute('blackjack_index');
    }

    /**
     * Calculate total values of a hand considering Ace as 1 or 11
     * Returns array: [totalLow, totalHigh]
     */
    private function calculateTotals(array $hand): array
    {
        $totalLow = 0; // counting Aces as 1
        $totalHigh = 0; // counting Aces as 11 if possible

        foreach ($hand as $card) {
            // getValue returns an array, e.g. [1, 11] for Ace or [10] for King
            $values = $card->getValue();

            $totalLow += $values[0];
            $totalHigh += $values[1] ?? $values[0];
        }

        return [$totalLow, $totalHigh];
    }
}
