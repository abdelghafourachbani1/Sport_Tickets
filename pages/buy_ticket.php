<?php
require_once __DIR__ . '/../config/setup.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'acheteur') {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier les paramètres
if (!isset($_GET['match_id']) || !isset($_GET['categorie_id'])) {
    header('Location: matches.php');
    exit;
}

$matchId = (int)$_GET['match_id'];
$categorieId = (int)$_GET['categorie_id'];

// Charger le match et la catégorie
$match = MatchSport::findById($matchId);
$categorie = Categorie::findById($categorieId);

if (!$match || !$categorie) {
    header('Location: matches.php');
    exit;
}

// Charger l'acheteur
$acheteur = Acheteur::findById($_SESSION['user_id']);

// Vérifier le nombre de billets déjà achetés
$nbBilletsAchetes = $acheteur->getNombreBilletsMatch($matchId);

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numeroPlace = Validation::sanitizeInput($_POST['numero_place'] ?? '');

    if (empty($numeroPlace)) {
        $error = "Veuillez sélectionner un numéro de place.";
    } elseif ($nbBilletsAchetes >= 4) {
        $error = "Vous avez atteint la limite de 4 billets pour ce match.";
    } else {
        try {
            // Acheter le billet
            $billet = $acheteur->acheterBillet($match, $categorie, $numeroPlace);

            if ($billet) {
                $success = "Billet acheté avec succès ! Un email de confirmation a été envoyé.";
                // Rediriger vers la page de profil après 3 secondes
                header("refresh:3;url=profile.php");
            } else {
                $error = "Erreur lors de l'achat du billet.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Générer les places disponibles (simulation)
$placesDisponibles = [];
for ($i = 1; $i <= min(50, $categorie->getPlacesDisponibles()); $i++) {
    $placesDisponibles[] = strtoupper($categorie->getNom()[0]) . '-' . $i;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acheter un billet - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="match_details.php?id=<?= $matchId ?>" class="text-blue-600 hover:underline mb-4 inline-block">
            ← Retour aux détails du match
        </a>

        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6">Acheter un billet</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($success) ?>
                    <p class="mt-2">Redirection vers votre profil...</p>
                </div>
            <?php endif; ?>

            <!-- Informations du match -->
            <div class="bg-gray-50 p-6 rounded-lg mb-6">
                <h2 class="text-xl font-bold mb-4">Informations du match</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600">Match</p>
                        <p class="font-semibold">
                            <?= htmlspecialchars($match->getEquipeDomicile()->getNom()) ?> vs
                            <?= htmlspecialchars($match->getEquipeExterieur()->getNom()) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Date</p>
                        <p class="font-semibold"><?= $match->getDate()->format('d/m/Y à H:i') ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Lieu</p>
                        <p class="font-semibold"><?= htmlspecialchars($match->getLieu()) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Catégorie</p>
                        <p class="font-semibold"><?= htmlspecialchars($categorie->getNom()) ?></p>
                    </div>
                </div>
            </div>

            <!-- Prix -->
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">Prix du billet:</span>
                    <span class="text-3xl font-bold text-blue-600"><?= number_format($categorie->getPrix(), 2) ?> DH</span>
                </div>
            </div>

            <!-- Limite de billets -->
            <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
                <p class="text-yellow-800">
                    <strong>Note:</strong> Vous avez déjà acheté <?= $nbBilletsAchetes ?> billet(s) pour ce match.
                    <?= (4 - $nbBilletsAchetes) ?> billet(s) restant(s).
                </p>
            </div>

            <?php if (!$success && $nbBilletsAchetes < 4): ?>
                <!-- Formulaire d'achat -->
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="numero_place" class="block text-sm font-medium text-gray-700 mb-2">
                            Choisissez votre place
                        </label>
                        <select name="numero_place" id="numero_place" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionnez une place</option>
                            <?php foreach ($placesDisponibles as $place): ?>
                                <option value="<?= htmlspecialchars($place) ?>"><?= htmlspecialchars($place) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">
                            <?= count($placesDisponibles) ?> place(s) disponible(s) dans cette catégorie
                        </p>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="font-semibold mb-4">Récapitulatif</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Catégorie:</span>
                                <span class="font-semibold"><?= htmlspecialchars($categorie->getNom()) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Prix unitaire:</span>
                                <span class="font-semibold"><?= number_format($categorie->getPrix(), 2) ?> DH</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-2">
                                <span>Total à payer:</span>
                                <span class="text-blue-600"><?= number_format($categorie->getPrix(), 2) ?> DH</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Confirmer l'achat
                        </button>
                        <a href="match_details.php?id=<?= $matchId ?>"
                            class="flex-1 bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 text-center font-semibold">
                            Annuler
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div><?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>