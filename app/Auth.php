<?php

declare(strict_types=1);

namespace App;

use App\DTO\UserDTO;
use App\Models\ApiToken;
use App\Models\User;
use Core\Session;

/**
 * Authentification web (session) et API (jeton Bearer).
 * Pas de facade : instancié par le conteneur (singleton).
 */
final class Auth
{// La classe Auth est responsable de l'authentification des utilisateurs.

    /** @var array<string, mixed>|null Ligne user sans password_hash */
    private ?array $user = null;// La propriété $user stocke les informations de l'utilisateur actuellement connecté, sans le mot de passe haché.

    public function __construct(private Session $session)
    {// Le constructeur de la classe Auth prend un objet Session en paramètre, qui est utilisé pour gérer les sessions utilisateur.

        $id = $this->session->get('user_id');// L'identifiant de l'utilisateur actuellement connecté est récupéré à partir de la session.

        if (is_int($id) || (is_string($id) && ctype_digit($id))) {// Si l'identifiant est un entier ou une chaîne de caractères représentant un entier, l'utilisateur correspondant est recherché dans la base de données.

            $row = User::find((int) $id);

            if ($row !== null) {// Si un utilisateur correspondant est trouvé, les informations de l'utilisateur sont stockées dans la propriété $user, sans le mot de passe haché.

                $this->user = $this->publicUser($row);
            }
        }
    }

    /**
     * Vérifie email + mot de passe. En cas de succès, ouvre la session web.
     */
    public function attempt(string $email, string $password): bool
    {// La méthode attempt vérifie si l'email et le mot de passe fournis correspondent à un utilisateur existant dans la base de données.

        $row = User::table()->where('email', '=', $email)->first();// Si un utilisateur correspondant est trouvé, le mot de passe fourni est vérifié par rapport au mot de passe haché stocké dans la base de données.

        if ($row === null) {// Si aucun utilisateur correspondant n'est trouvé, la méthode retourne false.

            return false;
        }

        if (!password_verify($password, (string) $row['password_hash'])) {// Si le mot de passe fourni ne correspond pas au mot de passe haché, la méthode retourne false.

            return false;
        }

        $this->login($row);// Si l'email et le mot de passe sont corrects, l'utilisateur est connecté en appelant la méthode login, et la méthode retourne true.

        return true;
    }

    /**
     * Mémorise l'utilisateur connecté dans la session (web).
     *
     * @param array<string, mixed> $row ligne SQL users
     */
    public function login(array $row): void
    {// La méthode login mémorise l'utilisateur connecté dans la session en stockant son identifiant dans la session et en mettant à jour la propriété $user avec les informations de l'utilisateur, sans le mot de passe haché.

        $this->session->set('user_id', (int) $row['id']);
        $this->user = $this->publicUser($row);
    }

    /**
     * Déconnexion web : on enlève user_id, sans casser flash ni CSRF.
     */
    public function logout(): void
    {// La méthode logout déconnecte l'utilisateur en supprimant son identifiant de la session et en réinitialisant la propriété $user à null.

        $this->user = null;
        unset($_SESSION['user_id']);
    }

    /**
     * Utilisateur courant (web session ou API après userFromToken), sans hash.
     *
     * @return array{id: int, username: string, email: string}|null
     */
    public function user(): ?array
    {// La méthode user retourne les informations de l'utilisateur actuellement connecté, sans le mot de passe haché. Si aucun utilisateur n'est connecté, elle retourne null.

        return $this->user;
    }

    public function id(): ?int
    {// La méthode id retourne l'identifiant de l'utilisateur actuellement connecté. Si aucun utilisateur n'est connecté, elle retourne null.

        if ($this->user === null) {// Si aucun utilisateur n'est connecté, la méthode retourne null.

            return null;
        }

        return (int) $this->user['id'];// Si un utilisateur est connecté, la méthode retourne son identifiant.
    }

    public function check(): bool
    {// La méthode check vérifie si un utilisateur est actuellement connecté. Elle retourne true si un utilisateur est connecté, sinon elle retourne false.

        return $this->user !== null;
    }

    /**
     * Crée un jeton API : le clair n'est montré qu'une fois au client.
     */
    public function issueToken(int $userId): string
    {// La méthode issueToken crée un jeton API pour l'utilisateur spécifié par son identifiant. Le jeton est généré de manière aléatoire, haché et stocké dans la base de données. Le jeton en clair est retourné au client, mais il n'est montré qu'une seule fois.

        $plain = bin2hex(random_bytes(32));
        ApiToken::insert([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
        ]);

        return $plain;
    }

    /**
     * Retrouve l'utilisateur à partir du jeton Bearer (clair).
     *
     * @return array<string, mixed>|null
     */
    public function userFromToken(string $plain): ?array
    {// La méthode userFromToken retrouve l'utilisateur à partir d'un jeton Bearer fourni en clair.
    // Le jeton est haché et recherché dans la base de données. Si un utilisateur correspondant est trouvé, ses informations sont stockées dans la propriété $user, sans le mot de passe haché, et retournées.
    // Si aucun utilisateur n'est trouvé, la méthode retourne null.

        $hash = hash('sha256', $plain);
        $token = ApiToken::table()->where('token_hash', '=', $hash)->first();
        if ($token === null) {
            return null;
        }

        $row = User::find((int) $token['user_id']);// Si un utilisateur correspondant est trouvé, ses informations sont stockées dans la propriété $user, sans le mot de passe haché, et retournées. Si aucun utilisateur n'est trouvé, la méthode retourne null.

        if ($row === null) {// Si aucun utilisateur correspondant n'est trouvé, la méthode retourne null.

            return null;
        }

        $this->user = $this->publicUser($row);// Si un utilisateur correspondant est trouvé, ses informations sont stockées dans la propriété $user, sans le mot de passe haché, et retournées.

        return $this->user;
    }

    /**
     * Enlève le hash du mot de passe avant toute exposition.
     *
     * @param array<string, mixed> $row
     * @return array{id: int, username: string, email: string}
     */
    private function publicUser(array $row): array
    {// La méthode private publicUser prend un tableau représentant une ligne d'utilisateur (row) en paramètre et retourne un tableau contenant uniquement les informations publiques de l'utilisateur, à savoir son identifiant, son nom d'utilisateur et son adresse e-mail, sans le mot de passe haché.
    
        return UserDTO::fromArray($row)->toArray();
    }
}
