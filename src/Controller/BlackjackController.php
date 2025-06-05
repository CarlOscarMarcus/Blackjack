<?php

namespace App\Controller;

use App\DeckHandler\Card;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlackjackController extends AbstractController
{
    #[Route('/', name: 'blackjack_index')]
    public function index(Request $request, SessionInterface $session): Response
    {
        $hand = $session->get('hand', []);
        $hasStayed = $session->get('has_stayed', false);
        $isBust = $session->get('is_bust', false);

        if ($request->getMethod() === 'POST' && !$hasStayed && !$isBust) {
            $suits = ['♠', '♥', '♦', '♣'];
            $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

            $card = new Card(
                $suits[array_rand($suits)],
                $values[array_rand($values)]
            );

            $hand[] = $card;
            $session->set('hand', $hand);

            // Kontrollera bust
            $totalLow = 0;
            $totalHigh = 0;

            foreach ($hand as $c) {
                $vals = $c->getValue();
                $totalLow += $vals[0];
                $totalHigh += $vals[1] ?? $vals[0];
            }

            // Om båda summor > 21 = bust
            if ($totalLow > 21 && $totalHigh > 21) {
                $session->set('is_bust', true);
            }
        }

        return $this->render('blackjack/index.html.twig', [
            'hand' => $hand,
            'hasStayed' => $hasStayed,
            'isBust' => $session->get('is_bust', false)
        ]);
    }

    #[Route('/stay', name: 'blackjack_stay', methods: ['POST'])]
    public function stay(SessionInterface $session): Response
    {
        $session->set('has_stayed', true);
        return $this->redirectToRoute('blackjack_index');
    }

    #[Route('/reset', name: 'blackjack_reset', methods: ['POST'])]
    public function reset(SessionInterface $session): Response
    {
        $session->remove('hand');
        $session->remove('has_stayed');
        $session->remove('is_bust');
        return $this->redirectToRoute('blackjack_index');
    }



}
