<?php
require_once __DIR__ . '/../config/setup.php';

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $nom = Validation::sanitizeInput($_POST['nom'] ?? '');
    $prenom = Validation::sanitizeInput($_POST['prenom'] ?? '');
    $email = Validation::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!Validation::validerNom($nom) || !Validation::validerNom($prenom)) {
        $error = "Le nom et le prénom ne doivent contenir que des lettres.";
    } elseif (!Validation::validerEmail($email)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif (!Validation::validerMotDePasse($password)) {
        $error = "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            // Créer un nouvel acheteur
            $acheteur = new Acheteur($nom, $prenom, $email, $password);

            if ($acheteur->save()) {
                // Envoyer email de confirmation
                $emailService = new EmailService();
                $emailService->envoyerConfirmationInscription($email, $nom . ' ' . $prenom);

                // Rediriger vers la page de connexion
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = "Erreur lors de l'inscription. L'email existe peut-être déjà.";
            }
        } catch (Exception $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Créer un compte
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Ou
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                        se connecter à un compte existant
                    </a>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input id="nom" name="nom" type="text" required
                            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Votre nom">
                    </div>

                    <div>
                        <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
                        <input id="prenom" name="prenom" type="text" required
                            value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Votre prénom">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="votre@email.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                        <input id="password" name="password" type="password" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="••••••••">
                        <p class="mt-1 text-xs text-gray-500">
                            Min. 8 caractères, 1 majuscule, 1 chiffre
                        </p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                        <input id="confirm_password" name="confirm_password" type="password" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        S'inscrire
                    </button>
                </div>

                <div class="text-center">
                    <a href="../pages/home.php" class="text-sm text-blue-600 hover:text-blue-500">
                        Retour à l'accueil
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>