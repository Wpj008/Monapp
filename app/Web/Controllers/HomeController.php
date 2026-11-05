<?php

declare(strict_types=1);

namespace App\Web\Controllers;

use Core\Controller;
use Core\Request;

final class HomeController extends Controller
{// La classe HomeController est responsable de la gestion des requêtes liées à la page d'accueil de l'application.
    public function index(Request $request): void
    {// La méthode index est responsable de l'affichage de la page d'accueil.
    // Elle ne prend pas de paramètres et affiche la vue 'home/index.twig' pour l'utilisateur.
    
        $this->view('home/index.twig');
    }
}
