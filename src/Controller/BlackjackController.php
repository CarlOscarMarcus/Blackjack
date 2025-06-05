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
    public function index(Request $request): Response
    {
        $card = null;

        if ($request->getMethod() === 'POST') {
            $suits = ['♠', '♥', '♦', '♣'];
            $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

            $card = $values[array_rand($values)] . $suits[array_rand($suits)];
        }

        return $this->render('blackjack/index.html.twig', [
            'card' => $card
        ]);
    }
}
