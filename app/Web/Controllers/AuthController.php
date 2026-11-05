<?php

declare(strict_types=1);

namespace App\Web\Controllers;

use App\DTO\LoginDTO;
use App\DTO\RegisterDTO;
use App\Models\User;
use Core\Controller;
use Core\Request;
use Core\ValidationException;

final class AuthController extends Controller
{//class AuthController permet de gérer les méthodes liées à l'authentification des utilisateurs.
// Elle hérite de la classe Controller, ce qui lui permet d'accéder aux fonctionnalités de base du framework.
    public function showLogin(Request $request): void
    {// La méthode showLogin est responsable de l'affichage du formulaire de connexion.
    // Elle vérifie d'abord si l'utilisateur est déjà connecté en utilisant la méthode check() de l'objet auth.
    // Si l'utilisateur est connecté, il est redirigé vers la page des utilisateurs. Sinon, la vue 'auth/login.twig' est affichée pour permettre à l'utilisateur de se connecter.

        if ($this->auth->check()) {// Si l'utilisateur est déjà connecté, il est redirigé vers la page des utilisateurs.
            $this->redirect('/users');
        }
        $this->view('auth/login.twig');// Sinon, la vue 'auth/login.twig' est affichée pour permettre à l'utilisateur de se connecter.
    }

    public function login(Request $request): void
    {// La méthode login est responsable de la connexion de l'utilisateur.
    // Elle tente de créer un objet LoginDTO à partir des données du formulaire.

        try {// Si la validation échoue, une exception ValidationException est levée, et les erreurs de validation sont stockées dans la session.

            $input = LoginDTO::fromArray($request->all());

        } catch (ValidationException $e) {// Si une exception de validation est levée, les erreurs sont stockées dans la session et l'utilisateur est redirigé vers le formulaire de connexion.

            $this->session->flash('errors', $e->errors);
            $this->session->flash('old', $request->all());
            $this->redirect('/login');
        }

        if (!$this->auth->attempt($input->email, $input->password)) {// Si la tentative de connexion échoue, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire de connexion.

            $this->session->flash('error', 'Email ou mot de passe incorrect.');
            $this->session->flash('old', ['email' => $input->email]);
            $this->redirect('/login');
        }

        // Si la tentative de connexion réussit, un message de succès est stocké dans la session et l'utilisateur est redirigé vers la page des utilisateurs.

        $this->session->flash('success', 'Vous êtes connecté.');
        $this->redirect('/users');
    }

    public function showRegister(Request $request): void
    {// La méthode showRegister est responsable de l'affichage du formulaire d'inscription.

        if ($this->auth->check()) {// Si l'utilisateur est déjà connecté, il est redirigé vers la page des utilisateurs.

            $this->redirect('/users');
        }
        $this->view('auth/register.twig');// Sinon, la vue 'auth/register.twig' est affichée pour permettre à l'utilisateur de s'inscrire.
    }

    public function register(Request $request): void
    {// La méthode register est responsable de l'inscription d'un nouvel utilisateur.

        try {// Si la validation échoue, une exception ValidationException est levée, et les erreurs de validation sont stockées dans la session.

            $input = RegisterDTO::fromArray($request->all());

        } catch (ValidationException $e) { // Si une exception de validation est levée, les erreurs sont stockées dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

            $this->session->flash('errors', $e->errors);
            $this->session->flash('old', $request->all());
            $this->redirect('/register');
        }

        $emailExists = User::table()->where('email', '=', $input->email)->first();// Vérifie si l'email existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

        if ($emailExists !== null) {// Vérifie si l'email existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

            $this->session->flash('error', 'Cet email est déjà utilisé.');
            $this->session->flash('old', $request->all());
            $this->redirect('/register');
        }

        $usernameExists = User::table()->where('username', '=', $input->username)->first();// Vérifie si le nom d'utilisateur existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

        if ($usernameExists !== null) {// Vérifie si le nom d'utilisateur existe déjà dans la base de données. Si c'est le cas, un message d'erreur est stocké dans la session et l'utilisateur est redirigé vers le formulaire d'inscription.

            $this->session->flash('error', "Ce nom d'utilisateur est déjà utilisé.");
            $this->session->flash('old', $request->all());
            $this->redirect('/register');
        }

        $id = User::insert([// Si la validation réussit et que l'email et le nom d'utilisateur sont uniques, un nouvel utilisateur est inséré dans la base de données en utilisant le modèle User.
            'username' => $input->username,
            'email' => $input->email,
            'password_hash' => password_hash($input->password, PASSWORD_DEFAULT),
        ]);

        $user = User::find($id);// Récupère l'utilisateur nouvellement créé à partir de la base de données en utilisant l'ID retourné par l'insertion.

        if ($user !== null) {// Si l'utilisateur est trouvé, il est connecté en utilisant la méthode login() de l'objet auth.

            $this->auth->login($user);
        }

        // Un message de succès est stocké dans la session et l'utilisateur est redirigé vers la page des utilisateurs.
        $this->session->flash('success', 'Compte créé, vous êtes connecté.');
        $this->redirect('/users');
    }

    public function logout(Request $request): void
    {// La méthode logout est responsable de la déconnexion de l'utilisateur.

        $this->auth->logout();// Déconnecte l'utilisateur en utilisant la méthode logout() de l'objet auth.
        $this->session->flash('success', 'Vous êtes déconnecté.');
        $this->redirect('/login');
    }
}
