<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Compte utilisateur (authentification). Pas de CRUD public.
 * Ne jamais envoyer password_hash à Twig ni en JSON.
 */
final class User extends Model
{
    protected static string $table = 'users';

    public static function membersForTeam(int $teamId){

        // La requête SQL récupère l'identifiant, le nom d'utilisateur, l'adresse e-mail, le rôle et le job de chaque utilisateur qui est membre de l'équipe spécifiée.
        return self::query('SELECT users.id, users.username, users.email, users.role, jobs.job FROM users 
            INNER JOIN userteam ON userteam.user_id = users.id
            INNER JOIN jobs ON users.job_id = jobs.id
            WHERE userteam.team_id = ?
            ORDER BY users.username ASC', [$teamId]
            
            ); 
    }

    public static function availableUsersForTeam(int $teamId){

        // La requête SQL récupère l'identifiant, le nom d'utilisateur et l'adresse e-mail de chaque utilisateur qui n'est pas déjà membre de l'équipe spécifiée.
        return self::query('SELECT users.id, users.username, users.email, users.role FROM users 
            WHERE users.id NOT IN (SELECT userteam.user_id FROM userteam WHERE userteam.team_id = ? AND users.role = ?)
             ORDER BY users.username ASC',
             
             
             ['ouvrier',$teamId] 
             
             );
    }

    public static function getUserAndJob(){

        return self::query('SELECT users.id,users.username,users.email, users.role, jobs.job, users.created_at, users.updated_at 
        FROM users
        INNER JOIN jobs ON users.job_id = jobs.id
        ORDER BY users.created_at DESC;');

       /*return self::table()
            ->select('id', 'username', 'email', 'role', 'job', 'created_at', 'updated_at')
            ->orderBy('created_at', 'DESC')
            ->get();*/
    }
}
