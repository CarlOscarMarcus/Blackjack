<?php

namespace App\Controller;

use App\DeckHandler\Card;
use App\DeckHandler\Deck;
use App\DeckHandler\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class BlackjackController extends AbstractController
{
    #[Route('/', name: 'blackjack_index')]
    public function index(SessionInterface $session): Response
    {
        $deck = $this->getDeck($session);

        $players = $this->getPlayers($session);
        if (empty($players)) {
            $players[] = new Player();
            $this->savePlayers($session, $players);
        }

        $current = $session->get('currentPlayerIndex', 0);
        $currentPlayer = $players[$current];

        [$totalLow, $totalHigh] = $currentPlayer->getTotals();

        return $this->render('blackjack/index.html.twig', [
            'hand' => $currentPlayer->getHand(),
            'totalLow' => $totalLow,
            'totalHigh' => $totalHigh <= 21 ? $totalHigh : null,
            'isBust' => $currentPlayer->isBust(),
            'hasStayed' => $currentPlayer->hasStayed(),
            'hasBlackjack' => $currentPlayer->hasBlackjack(),
            'deckCount' => $deck->cardsLeft(),
            'nextCard' => $deck->peek(),
            'currentIndex' => $current,
            'players' => $players,
        ]);
    }

    #[Route('/hit', name: 'blackjack_hit', methods: ['POST'])]
    public function hit(SessionInterface $session): Response
    {
        $deck = $this->getDeck($session);
        $players = $this->getPlayers($session);
        $current = $session->get('currentPlayerIndex', 0);

        $player = $players[$current];

        if ($deck->cardsLeft() > 0 && !$player->hasStayed() && !$player->isBust() && !$player->hasBlackjack()) {
            $card = $deck->draw();
            $player->addCard($card);
        }

        $players[$current] = $player;
        $this->saveDeck($session, $deck);
        $this->savePlayers($session, $players);

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/stay', name: 'blackjack_stay', methods: ['POST'])]
    public function stay(SessionInterface $session): Response
    {
        $players = $this->getPlayers($session);
        $current = $session->get('currentPlayerIndex', 0);

        $players[$current]->stay();

        // Advance to next player
        $nextIndex = ($current + 1) % count($players);
        $session->set('currentPlayerIndex', $nextIndex);

        $this->savePlayers($session, $players);
        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/reset', name: 'blackjack_reset', methods: ['POST'])]
    public function reset(SessionInterface $session): RedirectResponse
    {
        $deck = new Deck();
        $deck->shuffle();

        $players = $this->getPlayers($session);
        foreach ($players as $player) {
            $player->reset();
        }

        $this->saveDeck($session, $deck);
        $this->savePlayers($session, $players);
        $session->set('currentPlayerIndex', 0);

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/add-player', name: 'blackjack_add_player', methods: ['POST'])]
    public function addPlayer(SessionInterface $session): RedirectResponse
    {
        $players = $this->getPlayers($session);

        if (count($players) < 3) {
            $players[] = new Player();
            $this->savePlayers($session, $players);
        }

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/remove-player', name: 'blackjack_remove_player', methods: ['POST'])]
    public function removePlayer(SessionInterface $session): RedirectResponse
    {
        $players = $this->getPlayers($session);

        if (count($players) > 1) {
            array_pop($players);
            $this->savePlayers($session, $players);
        }

        $session->set('currentPlayerIndex', 0);

        return $this->redirectToRoute('blackjack_index');
    }

    // === Helper Methods ===

    private function getDeck(SessionInterface $session): Deck
    {
        $data = $session->get('deck');
        return $data ? Deck::fromArray($data) : (new Deck())->shuffleAndReturn();
    }

    private function saveDeck(SessionInterface $session, Deck $deck): void
    {
        $session->set('deck', $deck->toArray());
    }

    private function getPlayers(SessionInterface $session): array
    {
        $data = $session->get('players', []);
        return array_map(fn($p) => Player::fromArray($p), $data);
    }

    private function savePlayers(SessionInterface $session, array $players): void
    {
        $session->set('players', array_map(fn($p) => $p->toArray(), $players));
    }
}
