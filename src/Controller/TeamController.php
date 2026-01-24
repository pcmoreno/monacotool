<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Team;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TeamController extends AbstractController
{
    #[Route('/team', name: 'app_team')]
    public function index(): Response
    {
        return $this->render('team/index.html.twig', [
            'controller_name' => 'TeamController',
        ]);
    }

    #[Route('/team/{id}', name: 'app_team_show')]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
                'team' => $team,
                'outputAverage' => $team->getOutputAverage(),
                'standardDeviation' => $team->getStandardDeviation(),
            ]
        );

    }
}
