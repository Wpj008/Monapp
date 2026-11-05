<?php

declare(strict_types=1);

namespace App\Web\Controllers;

use App\Models\User;
use Core\Controller;
use Core\Request;

final class UserController extends Controller
{// La classe UserController est responsable de la gestion des requêtes liées aux utilisateurs de l'application.
    public function index(Request $request): void
    {// La méthode index est responsable de l'affichage de la liste des utilisateurs.
    // Elle récupère tous les utilisateurs de la base de données en utilisant le modèle User et
    // les passe à la vue 'users/index.twig' pour affichage.
    
        $users = User::table()
            ->select('id', 'username', 'email', 'created_at', 'updated_at')
            ->orderBy('created_at', 'DESC')
            ->get();

        $this->view('users/index.twig', ['users' => $users]);
    }
}
