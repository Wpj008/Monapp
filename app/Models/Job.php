<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Tâche de la todo list globale.
 */
final class Job extends Model
{
    protected static string $table = 'jobs';
}
