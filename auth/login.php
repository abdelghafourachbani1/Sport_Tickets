<?php
require_once __DIR__ . '/../config/setup.php';

$error = '';
$success = '';

// Si déjà connecté, rediriger selon le rôle
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    switch ($role) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'organisateur':
            header('Location: ../organizer/create_match.php');
            break;
        case 'acheteur':
            header('Location: ../pages/home.php');
            break;
    }
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Validation::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Créer un utilisateur temporaire pour le login
            $tempUser = new Acheteur('', '', $email, '');

            if ($tempUser->login($email, $password)) {
                // Redirection selon le rôle
                $role = $_SESSION['user_role'];
                switch ($role) {
                    case 'admin':
                        header('Location: ../admin/dashboard.php');
                        break;
                    case 'organisateur':
                        header('Location: ../organizer/create_match.php');
                        break;
                    case 'acheteur':
                        header('Location: ../pages/home.php');
                        break;
                }
                exit;
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la connexion: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Connexion
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Ou
                    <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                        créer un nouveau compte
                    </a>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    Inscription réussie ! Vous pouvez maintenant vous connecter.
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input id="email" name="email" type="email" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Adresse email">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Mot de passe</label>
                        <input id="password" name="password" type="password" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Mot de passe">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Se connecter
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