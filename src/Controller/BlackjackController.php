<?php

namespace App\Controller;

use App\DeckHandler\Card;
use App\DeckHandler\Deck;
use App\DeckHandler\Player;
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
        $deck = $deckData ? Deck::fromArray($deckData) : (new Deck())->shuffle();

        // Load current hand & state
        $playerData = $session->get('player');
        $player = $playerData ? $this->getPlayer($session) : new \App\DeckHandler\Player();

        // Calculate totals
        [$totalLow, $totalHigh] = $player->getTotals();


        $isBust = $player->isBust();
        $hasStayed = $player->hasStayed();
        $hasBlackjack = $player->hasBlackjack();

        // Decide if bust or blackjack if not yet set
        $this->savePlayer($session, $player);

        return $this->render('blackjack/index.html.twig', [
            'hand' => $player->getHand(),
            'totalLow' => $totalLow,
            'totalHigh' => $totalHigh <= 21 ? $totalHigh : null,
            'isBust' => $isBust,
            'hasStayed' => $hasStayed,
            'hasBlackjack' => $hasBlackjack,
            'deckCount' => $deck->cardsLeft(),
            'nextCard' => $deck->peek(),
        ]);
    }

    #[Route('/hit', name: 'blackjack_hit', methods: ['POST'])]
    public function hit(SessionInterface $session): Response
    {
        // Load deck from session
        $deck = $this->getDeck($session);

        // Load player from session
        $player = $this->getPlayer($session);

        if ($deck->cardsLeft() > 0 && !$player->hasStayed() && !$player->isBust() && !$player->hasBlackjack()) {
            $card = $deck->draw();
            $player->addCard($card);
        }

        // Save updated objects to session
        $this->saveDeck($session, $deck);
        $this->savePlayer($session, $player);


    return $this->redirectToRoute('blackjack_index');

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/stay', name: 'blackjack_stay', methods: ['POST'])]
    public function stay(SessionInterface $session): Response
    {
        $player = $this->getPlayer($session);
        $player->stay();
        $this->savePlayer($session, $player);

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/reset', name: 'blackjack_reset')] //, methods: ['POST']
    public function reset(SessionInterface $session): Response
    {
        $deck = new \App\DeckHandler\Deck();
        $deck->shuffle();

        $player = new \App\DeckHandler\Player();

        $this->saveDeck($session, $deck);
        $this->savePlayer($session, $player);

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

    private function getPlayer(SessionInterface $session): Player
    {
        $data = $session->get('player');
        return $data ? Player::fromArray($data) : new Player();
    }

    private function savePlayer(SessionInterface $session, Player $player): void
    {
        $session->set('player', $player->toArray());
    }

    private function getDeck(SessionInterface $session): Deck
    {
        $data = $session->get('deck');
        return $data ? Deck::fromArray($data) : (new Deck())->shuffleAndReturn();
    }

    private function saveDeck(SessionInterface $session, Deck $deck): void
    {
        $session->set('deck', $deck->toArray());
    }
}
