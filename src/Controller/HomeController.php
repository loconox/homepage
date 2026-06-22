<?php

declare(strict_types=1);

namespace App\Controller;

use App\Content\ContentProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(ContentProvider $contentProvider): Response
    {
        return $this->render('index.html.twig', ['content' => $contentProvider]);
    }
}
