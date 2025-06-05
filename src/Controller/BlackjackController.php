<?php

namespace App\Controller;

use App\DeckHandler\Deck;
use App\DeckHandler\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

        $dealer = $this->getDealer($session);
        [$dealerLow, $dealerHigh] = $dealer->getTotals();
        $dealerTotal = ($dealerHigh <= 21) ? $dealerHigh : $dealerLow;

        $activePlayerIndex = $session->get('activePlayerIndex', 0);

        $allPlayersStayed = count($players) > 0 && array_reduce($players, fn($carry, $p) => $carry && $p->hasStayed(), true);

        $playersWithResults = [];

        foreach ($players as $player) {
            [$totalLow, $totalHigh] = $player->getTotals();
            $playerTotal = ($totalHigh <= 21) ? $totalHigh : $totalLow;

            // Default resultat om spelet pågår
            $result = null;

            // Beräkna resultat först när spelet är slut
            if ($allPlayersStayed || $session->get('activePlayerIndex') === null) {
                if ($player->isBust()) {
                    $result = 'lost';
                } elseif ($dealer->isBust()) {
                    $result = 'won';
                } else {
                    if ($playerTotal > $dealerTotal) {
                        $result = 'won';
                    } elseif ($playerTotal < $dealerTotal) {
                        $result = 'lost';
                    } else {
                        $result = 'push';
                    }
                }
            }

            $playersWithResults[] = [
                'player' => $player,
                'totalLow' => $totalLow,
                'totalHigh' => $totalHigh <= 21 ? $totalHigh : null,
                'result' => $result,
            ];
        }

        return $this->render('blackjack/index.html.twig', [
            'players' => $playersWithResults,
            'dealer' => $dealer,
            'dealerTotalLow' => $dealerLow,
            'dealerTotalHigh' => $dealerHigh <= 21 ? $dealerHigh : null,
            'deckCount' => $deck->cardsLeft(),
            'nextCard' => $deck->peek(),
            'allPlayersStayed' => $allPlayersStayed,
            'activePlayerIndex' => $activePlayerIndex,
            'canAddRemovePlayers' => $activePlayerIndex === null,
        ]);
    }


    #[Route('/hit', name: 'blackjack_hit', methods: ['POST'])]
    public function hit(SessionInterface $session): Response
    {
        $players = $this->getPlayers($session);
        $deck = $this->getDeck($session);
        $activeIndex = $session->get('activePlayerIndex', 0);

        if (!isset($players[$activeIndex])) {
            return $this->redirectToRoute('blackjack_index');
        }

        $player = $players[$activeIndex];

        if ($deck->cardsLeft() > 0 && !$player->hasStayed() && !$player->isBust() && !$player->hasBlackjack()) {
            $player->addCard($deck->draw());

            if ($player->isBust()) {
                $nextIndex = $this->findNextActivePlayer($players, $activeIndex);

                if ($nextIndex === null) {
                    $this->dealerPlay($session, $deck);
                    $session->set('activePlayerIndex', null);
                } else {
                    $session->set('activePlayerIndex', $nextIndex);
                }
            }

            // Ny kod: om spelaren får blackjack, automatiskt stay och gå vidare
            if ($player->hasBlackjack()) {
                $player->stay();

                $nextIndex = $this->findNextActivePlayer($players, $activeIndex);

                if ($nextIndex === null) {
                    $this->dealerPlay($session, $deck);
                    $session->set('activePlayerIndex', null);
                } else {
                    $session->set('activePlayerIndex', $nextIndex);
                }
            }
        }

        $players[$activeIndex] = $player;
        $this->savePlayers($session, $players);
        $this->saveDeck($session, $deck);

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/stay', name: 'blackjack_stay', methods: ['POST'])]
    public function stay(SessionInterface $session): Response
    {
        $players = $this->getPlayers($session);
        $activeIndex = $session->get('activePlayerIndex', 0);

        if (!isset($players[$activeIndex])) {
            return $this->redirectToRoute('blackjack_index');
        }

        $players[$activeIndex]->stay();

        $nextIndex = $this->findNextActivePlayer($players, $activeIndex);

        if ($nextIndex === null) {
            $deck = $this->getDeck($session);
            $this->dealerPlay($session, $deck);
            $session->set('activePlayerIndex', null);
        } else {
            $session->set('activePlayerIndex', $nextIndex);
        }

        $this->savePlayers($session, $players);

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/reset', name: 'blackjack_reset')] // , methods: ['POST']
    public function reset(SessionInterface $session): RedirectResponse
    {
        $deck = new Deck();
        $deck->shuffle();

        $players = $this->getPlayers($session);
        foreach ($players as $player) {
            $player->reset();
            // Dela ut 2 kort till varje spelare vid reset (valfritt, men vanligt)
            $player->addCard($deck->draw());
            $player->addCard($deck->draw());
        }

        $dealer = $this->initialiseDealer($deck);

        $session->set('deck', $deck->toArray());
        $this->savePlayers($session, $players);
        $this->saveDealer($session, $dealer);
        $session->set('activePlayerIndex', 0);

        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/add-player', name: 'blackjack_add_player', methods: ['POST'])]
    public function addPlayer(SessionInterface $session): RedirectResponse
    {
        $activePlayerIndex = $session->get('activePlayerIndex');

        // Tillåt bara att lägga till spelare om det INTE är pågående spelomgång
        if ($activePlayerIndex !== null) {
            return $this->redirectToRoute('blackjack_index');
        }

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
        $activePlayerIndex = $session->get('activePlayerIndex');

        // Tillåt bara ta bort spelare om det INTE är pågående spelomgång
        if ($activePlayerIndex !== null) {
            return $this->redirectToRoute('blackjack_index');
        }

        $players = $this->getPlayers($session);
        if (count($players) > 1) {
            array_pop($players);
            $this->savePlayers($session, $players);
        }
        return $this->redirectToRoute('blackjack_index');
    }

    /* ---------- PRIVATE HELPERS ---------- */

    private function findNextActivePlayer(array $players, int $currentIndex): ?int
    {
        $nextIndex = $currentIndex + 1;
        while (isset($players[$nextIndex])) {
            if (!$players[$nextIndex]->hasStayed() && !$players[$nextIndex]->isBust()) {
                return $nextIndex;
            }
            $nextIndex++;
        }
        return null;
    }

    private function dealerPlay(SessionInterface $session, Deck $deck): void
    {
        $dealer = $this->getDealer($session);

        while (!$dealer->isBust() && !$dealer->hasStayed()) {
            [$low, $high] = $dealer->getTotals();
            $total = $high <= 21 ? $high : $low;
            if ($total < 17) {
                $dealer->addCard($deck->draw());
            } else {
                $dealer->stay();
            }
        }

        $this->saveDealer($session, $dealer);
        $this->saveDeck($session, $deck);
    }

    private function initialiseDealer(Deck $deck): Player
    {
        $dealer = new Player();
        $dealer->addCard($deck->draw()); // första kortet synligt
        $dealer->addCard($deck->draw()); // dolt kort
        return $dealer;
    }

    /* session helpers */
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
        $session->set('players', array_map(fn(Player $p) => $p->toArray(), $players));
    }

    private function getDealer(SessionInterface $session): Player
    {
        $data = $session->get('dealer');
        return $data ? Player::fromArray($data) : new Player();
    }

    private function saveDealer(SessionInterface $session, Player $dealer): void
    {
        $session->set('dealer', $dealer->toArray());
    }
}
