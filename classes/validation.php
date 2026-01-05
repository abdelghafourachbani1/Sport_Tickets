<?php

class Validation {

    public static function validerEmail($email){
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    public static function validerMotDePasse($password) {
        if (strlen($password) < 8) {
            return false;
        }
        return true;
    }

    public static function getExigencesMotDePasse() {
        return 'au moins 8 caracter';
    }

    public static function validerNombrePlace($nombre) {
        return $nombre >= 1 && $nombre <= 4;
    }

    public static function validerDate(DateTime $date) {
        $now = new DateTime();
        return $date > $now;
    }

    public static function validerPrix($prix) {
        return $prix > 0;
    }

    public static function validerNom($nom) {
        if (strlen($nom) < 8) {
            return false;
        }
        return true;
    }

    public static function validerTelephone($telephone) {
        return preg_match("/^(\+\d{1,3}[- ]?)?\d{10}$/", $telephone);
    }

    public static function validerPlacesTotales($places) {
        return $places >= 1 && $places <= 2000;
    }

    public static function validerNote($note) {
        return $note >= 1 && $note <= 5;
    }

    public static function validerTexte($texte, $minLength = 10, $maxLength = 1000) {
        $length = strlen(trim($texte));
        return $length >= $minLength && $length <= $maxLength;
    }

    public static function validerCodePostal(string $codePostal) {
        return preg_match("/^\d{5}$/", $codePostal);
    }

    public static function detecterInjectionSQL($input) {
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

    public static function validerURL(string $url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function genererMessageErreur($champ, $type) {
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

    public static function validerFormulaire(array $data, array $rules) {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';

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
