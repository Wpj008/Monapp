<?php

declare(strict_types=1);

namespace App\Web\Controllers;
use App\DTO\TeamDTO;
use App\DTO\TeamInputDTO;
use App\Models\Team;
use Core\Controller;
use Core\Request;
use Core\ValidationException;

final class TeamController extends Controller
{//class TeamController permet de gérer les méthodes liées à une fonctionnalité spécifique, ici la gestion des équipes.
// Elle hérite de la classe Controller, ce qui lui permet d'accéder aux fonctionnalités de base du framework.
    public function index(Request $request): void
    {// La méthode index est responsable de l'affichage de la liste des équipes.

    // La requête SQL récupère l'identifiant et le nom de chaque équipe, ainsi que le nombre de membres
    // associés à chaque équipe en utilisant une jointure gauche avec la table userteam. Les résultats sont regroupés par identifiant
    // et nom d'équipe, puis triés par nom d'équipe par ordre croissant.

        $teams = Team::query('SELECT teams.id, teams.name, COUNT(userteam.id) AS members_count FROM teams 
            LEFT JOIN userteam ON userteam.team_id = teams.id
            GROUP BY teams.id, teams.name
            ORDER BY teams.name ASC');


        $this->view('teams/index.twig', ['teams' => $teams]);// La vue 'teams/index.twig' est affichée avec les données des équipes récupérées de la base de données.
    }

    public function create(Request $request): void
    {// La méthode create est responsable de l'affichage du formulaire de création d'une nouvelle équipe.
    // Elle ne prend pas de paramètres et affiche la vue 'teams/add.twig' avec un tableau contenant une équipe vide (null) et l'URL d'action pour le formulaire.

        $this->view('teams/add.twig', [
            'action' => $this->view->url('/teams'),
        ]);
    }

    public function store(Request $request):void
    {// La méthode store est responsable de la création d'une nouvelle équipe à partir des données soumises via le formulaire.
    // Elle prend un objet Request en paramètre, qui contient les données du formulaire.
    // La méthode tente de créer un objet TeamInputDTO à partir des données du formulaire.

        try {// Si la validation échoue, une exception ValidationException est levée, et les erreurs de validation sont stockées dans la session.

            $input = TeamInputDTO::fromArray($request->all());

        } catch (ValidationException $e) {// Si une exception de validation est levée, les erreurs sont stockées dans la session et l'utilisateur est redirigé vers le formulaire de création d'équipe.

            $this->session->flash('errors', $e->errors);
            $this->session->flash('old', $request->all());
            $this->redirect('/teams/create');
        }

        $name = trim($input->name);// Si la validation réussit, le nom de l'équipe est récupéré et les espaces vides sont supprimés.
        
           if($name === '') {
            $this->session->flash('error',"Le nom de l'équipe est obligatoire.");
            $this->session->flash('old', $request->all());
            $this->redirect('/teams/create');
        }


        $now = date('Y-m-d H:i:s');

        $id =Team::insert([// Si la validation réussit, une nouvelle équipe est insérée dans la base de données en utilisant le modèle Team.
            'name' => $input->name,
            'created_at' => $now,
            'updated_at' => $now,

        ]);

        $this->session->flash('success', 'Equipe crée');
        $this->redirect('/teams/' . $id);

    }

    public function show(Request $request): void
    {// La méthode show est responsable de l'affichage des détails d'une équipe spécifique.
    // Elle prend un objet Request en paramètre, qui contient les données de la requête, y compris l'identifiant de l'équipe à afficher.
    // La méthode récupère l'identifiant de l'équipe à partir des paramètres de la requête et utilise le modèle Team pour trouver l'équipe correspondante dans la base de données. 
    // Si l'équipe est trouvée, elle est passée à la vue 'teams/show.twig' pour affichage. Si l'équipe n'est pas trouvée, une exception est levée.

        $team = Team::find((int) $request->param('id'));// La méthode find() du modèle Team est utilisée pour rechercher l'équipe dans la base de données en utilisant l'identifiant fourni dans la requête.
        
        if($team === null) {

         $this->session->flash('error','Equipe introuvable');
         $this->redirect('/teams');
         }

        $teamId = (int) $team['id'];



        $this->view('teams/show.twig', [
            'team' => $team,
            'members'=> $this->membersForTeam($teamId),
            'available_users'=> $this->availableUsersForTeam($teamId),
            
            
            ]);
    }
}