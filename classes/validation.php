<?php

class Validation {

    public static function validerEmail(string $email): bool
    {
        // Vérifier le format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Vérifier que le domaine existe
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, "MX")) {
            return false;
        }

        return true;
    }

    public static function validerMotDePasse(string $password): bool
    {
        // Minimum 8 caractères
        if (strlen($password) < 8) {
            return false;
        }

        // Au moins une lettre majusculea
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Au moins une lettre minuscule
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        return true;
    }

    public static function getExigencesMotDePasse(): array
    {
        return [
            'Au moins 8 caractères',
            'Au moins une lettre majuscule',
            'Au moins une lettre minuscule',
            'Au moins un chiffre'
        ];
    }

    public static function validerNombrePlace(int $nombre): bool
    {
        return $nombre >= 1 && $nombre <= 4;
    }

    public static function validerDate(DateTime $date): bool
    {
        $now = new DateTime();
        return $date > $now;
    }

    public static function validerPrix(float $prix): bool
    {
        return $prix > 0;
    }

    public static function sanitizeInput(string $input): string
    {
        // Supprimer les espaces en début et fin
        $input = trim($input);

        // Supprimer les slashes
        $input = stripslashes($input);

        // Convertir les caractères spéciaux en entités HTML
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        return $input;
    }

    public static function validerNom(string $nom): bool
    {
        // Minimum 2 caractères, maximum 50
        if (strlen($nom) < 2 || strlen($nom) > 50) {
            return false;
        }

        // Seulement lettres, espaces, tirets et apostrophes
        return preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/u", $nom);
    }

    public static function validerTelephone(string $telephone): bool
    {
        // Format: 10 chiffres ou format international
        return preg_match("/^(\+\d{1,3}[- ]?)?\d{10}$/", $telephone);
    }

    public static function validerPlacesTotales(int $places): bool
    {
        return $places >= 1 && $places <= 2000;
    }

    public static function validerNote(int $note): bool
    {
        return $note >= 1 && $note <= 5;
    }

    public static function validerImage(array $file, int $maxSize = 2097152): array
    {
        $result = ['valid' => true, 'error' => ''];

        // Vérifier qu'un fichier a été uploadé
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Aucun fichier uploadé'];
        }

        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Erreur lors de l\'upload du fichier'];
        }

        // Vérifier la taille
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'Le fichier est trop volumineux (max 2MB)'];
        }

        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Type de fichier non autorisé (JPG, PNG, GIF uniquement)'];
        }

        // Vérifier que c'est bien une image
        if (!getimagesize($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Le fichier n\'est pas une image valide'];
        }

        return $result;
    }

    public static function validerTexte(string $texte, int $minLength = 10, int $maxLength = 1000): bool
    {
        $length = strlen(trim($texte));
        return $length >= $minLength && $length <= $maxLength;
    }

    public static function validerCodePostal(string $codePostal): bool
    {
        // Format: 5 chiffres
        return preg_match("/^\d{5}$/", $codePostal);
    }

    public static function detecterInjectionSQL(string $input): bool
    {
        $patterns = [
            '/(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bUNION\b)/i',
            '/--/',
            '/;/',
            '/\/\*/',
            '/\*\//'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    public static function validerURL(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function genererMessageErreur(string $champ, string $type): string
    {
        $messages = [
            'required' => "Le champ {$champ} est requis.",
            'email' => "L'adresse email n'est pas valide.",
            'password' => "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.",
            'date' => "La date doit être dans le futur.",
            'prix' => "Le prix doit être un nombre positif.",
            'nombre' => "Le nombre n'est pas valide.",
            'nom' => "Le nom ne doit contenir que des lettres.",
            'places' => "Le nombre de places doit être entre 1 et 2000.",
            'note' => "La note doit être entre 1 et 5.",
            'texte' => "Le texte est trop court ou trop long."
        ];

        return $messages[$type] ?? "Le champ {$champ} n'est pas valide.";
    }

    public static function validerFormulaire(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';

            // Champ requis
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = self::genererMessageErreur($field, 'required');
                continue;
            }

            // Email
            if (isset($rule['email']) && $rule['email'] && !empty($value)) {
                if (!self::validerEmail($value)) {
                    $errors[$field] = self::genererMessageErreur($field, 'email');
                }
            }

            // Mot de passe
            if (isset($rule['password']) && $rule['password'] && !empty($value)) {
                if (!self::validerMotDePasse($value)) {
                    $errors[$field] = self::genererMessageErreur($field, 'password');
                }
            }

            // Longueur min/max
            if (isset($rule['min']) && !empty($value)) {
                if (strlen($value) < $rule['min']) {
                    $errors[$field] = "Le champ {$field} doit contenir au moins {$rule['min']} caractères.";
                }
            }

            if (isset($rule['max']) && !empty($value)) {
                if (strlen($value) > $rule['max']) {
                    $errors[$field] = "Le champ {$field} ne doit pas dépasser {$rule['max']} caractères.";
                }
            }

            // Numeric
            if (isset($rule['numeric']) && $rule['numeric'] && !empty($value)) {
                if (!is_numeric($value)) {
                    $errors[$field] = "Le champ {$field} doit être numérique.";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
