<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Tâche de la todo list globale.
 */
final class Team extends Model
{
    protected static string $table = 'teams';

    public static function getAndCountTeam(){

    return self::query('SELECT teams.id, teams.name, COUNT(userteam.id) AS members_count FROM teams 
    LEFT JOIN userteam ON userteam.team_id = teams.id
    GROUP BY teams.id, teams.name
    ORDER BY teams.name ASC');

    }

}
