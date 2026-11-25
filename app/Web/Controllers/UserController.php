<?php

declare(strict_types=1);

namespace App\Web\Controllers;

use App\Models\User;
use App\Models\Job;
use Core\Controller;
use Core\Request;
use App\DTO\UserDTO;
use Core\ValidationException;
use App\DTO\RegisterDTO;
final class UserController extends Controller
{// La classe UserController est responsable de la gestion des requêtes liées aux utilisateurs de l'application.
    public function index(Request $request): void
    {// La méthode index est responsable de l'affichage de la liste des utilisateurs.
    // Elle récupère tous les utilisateurs de la base de données en utilisant le modèle User et
    // les passe à la vue 'users/index.twig' pour affichage.
    
        $users = User::getUserAndJob();

        $this->view('users/index.twig', ['users' => $users]);
    }

    public function create(Request $request): void
    {// La méthode create est responsable de l'affichage du formulaire de création d'une nouvelle équipe.
    // Elle ne prend pas de paramètres et affiche la vue 'teams/add.twig' avec un tableau contenant une équipe vide (null) et l'URL d'action pour le formulaire.

       $jobs = Job::all();  
        
        $this->view('users/add.twig', [
                'action' => $this->view->url('/users'),
                'jobs' => $jobs,
            ]);
    }

    public function store(Request $request):void
    {// La méthode store est responsable de la création d'un nouvel utilisateur à partir des données soumises via le formulaire.
    // Elle prend un objet Request en paramètre, qui contient les données du formulaire.
    // La méthode tente de créer un objet TeamInputDTO à partir des données du formulaire.


        try {// Si la validation échoue, une exception ValidationException est levée, et les erreurs de validation sont stockées dans la session.

            $input = RegisterDTO::fromArray($request->all());

        } catch (ValidationException $e) { // Si une exception de validation est levée, les erreurs sont stockées dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

            $this->session->flash('errors', $e->errors);
            $this->session->flash('old', $request->all());
            $this->redirect('/adduser');
        }

        $emailExists = User::table()->where('email', '=', $input->email)->first();// Vérifie si l'email existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

        if ($emailExists !== null) {// Vérifie si l'email existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

            $this->session->flash('error', 'Cet email est déjà utilisé.');
            $this->session->flash('old', $request->all());
            $this->redirect('/adduser');
        }

        $usernameExists = User::table()->where('username', '=', $input->username)->first();// Vérifie si le nom d'utilisateur existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

        if ($usernameExists !== null) {// Vérifie si le nom d'utilisateur existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

            $this->session->flash('error', "Ce nom d'utilisateur est déjà utilisé.");
            $this->session->flash('old', $request->all());
            $this->redirect('/adduser');
        }

        $id = User::insert([// Si la validation réussit et que l'email et le nom d'utilisateur sont uniques, un nouvel utilisateur est inséré dans la base de données en utilisant le modèle User.
            'username' => $input->username,
            'email' => $input->email,
            'password_hash' => password_hash($input->password, PASSWORD_DEFAULT),
            'role' => $input->role,
            'job_id' => $input->job,
        ]);

      

        // Un message de succès est stocké dans la session et l'utilisateur est redirigé vers la page des utilisateurs.
        $this->session->flash('success', 'Compte créé, vous pouvez maintenant vous connecter.');
        $this->redirect('/users');
    }

    public function remove(Request $request): void
    {// La méthode remove est responsable de la suppression d'un utilisateur à partir de son identifiant.
    // Elle prend un objet Request en paramètre, qui contient les données de la requête. La méthode récupère l'identifiant de l'utilisateur à supprimer à partir des données
     
    $user = $request->all();
    //var_dump($user);

    $id_user = (int) $user['id'];

    if (User::find($id_user) === null) {
        $this->session->flash('error', 'Utilisateur introuvable.');
        $this->redirect('/users');
    }

    User::delete($id_user);
    $this->session->flash('success', 'Utilisateur supprimé.');
    $this->redirect('/users');

    }
}
