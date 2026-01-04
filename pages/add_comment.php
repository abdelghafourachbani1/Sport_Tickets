<?php
require_once __DIR__ . '/../config/setup.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'acheteur') {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier l'ID du match
if (!isset($_GET['match_id'])) {
    header('Location: matches.php');
    exit;
}

$matchId = (int)$_GET['match_id'];

// Charger le match
$match = MatchSport::findById($matchId);

if (!$match) {
    header('Location: matches.php');
    exit;
}

// Charger l'acheteur
$acheteur = Acheteur::findById($_SESSION['user_id']);

// Vérifier si l'acheteur peut commenter
if (!$acheteur->peutCommenter($matchId)) {
    header('Location: match_details.php?id=' . $matchId);
    exit;
}

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texte = Validation::sanitizeInput($_POST['texte'] ?? '');
    $note = (int)($_POST['note'] ?? 0);

    if (empty($texte)) {
        $error = "Le commentaire ne peut pas être vide.";
    } elseif (!Validation::validerTexte($texte, 10, 1000)) {
        $error = "Le commentaire doit contenir entre 10 et 1000 caractères.";
    } elseif (!Validation::validerNote($note)) {
        $error = "La note doit être entre 1 et 5.";
    } else {
        try {
            $commentaire = $acheteur->laisserCommentaire($match, $texte, $note);

            if ($commentaire) {
                $success = "Votre commentaire a été ajouté avec succès ! Il sera visible après validation.";
                // Rediriger après 2 secondes
                header("refresh:2;url=match_details.php?id=" . $matchId);
            } else {
                $error = "Erreur lors de l'ajout du commentaire.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.5rem;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 3rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating input:checked~label,
        .star-rating label:hover,
        .star-rating label:hover~label {
            color: #fbbf24;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="match_details.php?id=<?= $matchId ?>" class="text-blue-600 hover:underline mb-4 inline-block">
            ← Retour aux détails du match
        </a>

        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6">Laisser un avis</h1>

            <!-- Info match -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h2 class="font-semibold mb-2">Match:</h2>
                <p class="text-lg">
                    <?= htmlspecialchars($match->getEquipeDomicile()->getNom()) ?> vs
                    <?= htmlspecialchars($match->getEquipeExterieur()->getNom()) ?>
                </p>
                <p class="text-gray-600 mt-1">
                    <?= $match->getDate()->format('d/m/Y à H:i') ?> - <?= htmlspecialchars($match->getLieu()) ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($success) ?>
                    <p class="mt-2">Redirection en cours...</p>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" class="space-y-6">
                    <!-- Note -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4 text-center">
                            Votre note (cliquez sur les étoiles)
                        </label>
                        <div class="star-rating">
                            <input type="radio" name="note" id="star5" value="5" required>
                            <label for="star5">★</label>
                            <input type="radio" name="note" id="star4" value="4">
                            <label for="star4">★</label>
                            <input type="radio" name="note" id="star3" value="3">
                            <label for="star3">★</label>
                            <input type="radio" name="note" id="star2" value="2">
                            <label for="star2">★</label>
                            <input type="radio" name="note" id="star1" value="1">
                            <label for="star1">★</label>
                        </div>
                        <p class="text-center text-sm text-gray-500 mt-2">1 = Très mauvais | 5 = Excellent</p>
                    </div>

                    <!-- Commentaire -->
                    <div>
                        <label for="texte" class="block text-sm font-medium text-gray-700 mb-2">
                            Votre commentaire
                        </label>
                        <textarea name="texte" id="texte" rows="6" required
                            placeholder="Partagez votre expérience du match (minimum 10 caractères)..."
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                        <p class="text-sm text-gray-500 mt-1">Minimum 10 caractères, maximum 1000 caractères</p>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Publier mon avis
                        </button>
                        <a href="match_details.php?id=<?= $matchId ?>"
                            class="flex-1 bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 text-center font-semibold">
                            Annuler
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>