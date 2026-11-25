<?php

declare(strict_types=1);

namespace App\DTO;

use Core\DTO;

/**
 * Utilisateur exposé (JSON, Twig). Jamais password_hash :
 * fromArray() ignore les clés absentes des propriétés publiques.
 */
final class UserDTO extends DTO
{
    public ?int $id;//(faudra renommer ou creer un autre DTO pour l'édition de l'utilisateur)
    public string $username;
    public string $email;
    public ?string $role = "ouvrier";
    public ?string $job = null;
}
