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

        // Calculate totals
        $totalLow = 0;
        $totalHigh = 0;

        foreach ($hand as $card) {
            $vals = $card->getValue();
            $totalLow += $vals[0];
            $totalHigh += $vals[1] ?? $vals[0];
        }

        if ($totalLow > 21 && $totalHigh > 21) {
            $isBust = true;
            $session->set('is_bust', true);
        }

        // Determine what to show
        if ($totalHigh != 21 or $totalLow != 21) {
            $showHigh = $totalHigh <= 21 && $totalLow !== $totalHigh;
        } else {
            $showHigh = "Black Jack!";
        }

        return $this->render('blackjack/index.html.twig', [
            'hand' => $hand,
            'hasStayed' => $hasStayed,
            'isBust' => $isBust,
            'totalLow' => $totalLow,
            'totalHigh' => $showHigh ? $totalHigh : null,
            'hasBlackjack' => $session->get('has_blackjack', false)
        ]);
    }

    #[Route('/hit', name: 'blackjack_hit', methods: ['POST'])]
    public function hit(SessionInterface $session): Response
    {
        $hand = $session->get('hand', []);
        $hasStayed = $session->get('has_stayed', false);
        $isBust = $session->get('is_bust', false);

        if (!$hasStayed && !$isBust) {
            $card = new Card(
                ['♠', '♥', '♦', '♣'][array_rand(['♠', '♥', '♦', '♣'])],
                "A"
            );

            $hand[] = $card;
            $session->set('hand', $hand);

            // Recalculate bust after hit
            $totalLow = 0;
            $totalHigh = 0;
            foreach ($hand as $card) {
                $vals = $card->getValue();
                $totalLow += $vals[0];
                $totalHigh += $vals[1] ?? $vals[0];
            }

            if ($totalLow > 21 && $totalHigh > 21) {
                $session->set('is_bust', true);
            }

            // Auto-stand if 21
            $validTotal = ($totalHigh <= 21) ? $totalHigh : $totalLow;
            if ($validTotal === 21) {
                $session->set('has_stayed', true);
                $session->set('has_blackjack', true);
            } else {
                $session->remove('has_blackjack');
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

    #[Route('/reset', name: 'blackjack_reset', methods: ['POST'])]
    public function reset(SessionInterface $session): Response
    {
        $session->remove('hand');
        $session->remove('has_stayed');
        $session->remove('is_bust');
        $session->remove('has_blackjack');
        return $this->redirectToRoute('blackjack_index');
    }



}
