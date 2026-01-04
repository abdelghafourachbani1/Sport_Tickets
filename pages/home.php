<?php
require_once __DIR__ . '/../config/setup.php';

// Récupérer les filtres
$filtres = [];
if (isset($_GET['lieu']) && !empty($_GET['lieu'])) {
    $filtres['lieu'] = Validation::sanitizeInput($_GET['lieu']);
}
if (isset($_GET['equipe']) && !empty($_GET['equipe'])) {
    $filtres['equipe'] = Validation::sanitizeInput($_GET['equipe']);
}
if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $filtres['date_debut'] = $_GET['date_debut'];
}

// Récupérer les matches
$matches = MatchSport::getAll($filtres);

// Récupérer tous les lieux pour le filtre
$db = Database::getInstance()->getConnection();
$queryLieux = "SELECT DISTINCT lieu FROM matches WHERE statut = 'validé' ORDER BY lieu";
$lieux = $db->query($queryLieux)->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">⚽ Billetterie Sportive</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="matches.php" class="text-gray-700 hover:text-blue-600">Tous les matchs</a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="text-gray-700 hover:text-blue-600">Mon profil</a>
                        <?php if ($_SESSION['user_role'] === 'organisateur'): ?>
                            <a href="../organizer/create_match.php" class="text-gray-700 hover:text-blue-600">Créer un match</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="../admin/dashboard.php" class="text-gray-700 hover:text-blue-600">Administration</a>
                        <?php endif; ?>
                        <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Déconnexion</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="text-gray-700 hover:text-blue-600">Connexion</a>
                        <a href="../auth/register.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Inscription</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-blue-600 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-4">Réservez vos billets pour les meilleurs matchs</h2>
            <p class="text-xl mb-8">Ne manquez aucun événement sportif !</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold mb-4">Filtrer les matchs</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lieu</label>
                    <select name="lieu" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tous les lieux</option>
                        <?php foreach ($lieux as $lieu): ?>
                            <option value="<?= htmlspecialchars($lieu) ?>"
                                <?= (isset($filtres['lieu']) && $filtres['lieu'] === $lieu) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lieu) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Équipe</label>
                    <input type="text" name="equipe"
                        value="<?= htmlspecialchars($filtres['equipe'] ?? '') ?>"
                        placeholder="Nom de l'équipe"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">À partir du</label>
                    <input type="date" name="date_debut"
                        value="<?= $filtres['date_debut'] ?? '' ?>"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des matches -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h3 class="text-2xl font-bold mb-6">Matches à venir</h3>

        <?php if (empty($matches)): ?>
            <div class="bg-white p-8 rounded-lg shadow-md text-center">
                <p class="text-gray-600">Aucun match disponible pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($matches as $match): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <div class="text-center flex-1">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($match['logo_domicile']) ?>"
                                        alt="<?= htmlspecialchars($match['equipe_domicile']) ?>"
                                        class="w-16 h-16 mx-auto mb-2 object-contain"
                                        onerror="this.src='<?= BASE_URL ?>/uploads/default-logo.png'">
                                    <p class="font-semibold"><?= htmlspecialchars($match['equipe_domicile']) ?></p>
                                </div>
                                <div class="text-2xl font-bold text-gray-400">VS</div>
                                <div class="text-center flex-1">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($match['logo_exterieur']) ?>"
                                        alt="<?= htmlspecialchars($match['equipe_exterieur']) ?>"
                                        class="w-16 h-16 mx-auto mb-2 object-contain"
                                        onerror="this.src='<?= BASE_URL ?>/uploads/default-logo.png'">
                                    <p class="font-semibold"><?= htmlspecialchars($match['equipe_exterieur']) ?></p>
                                </div>
                            </div>

                            <div class="border-t pt-4 space-y-2">
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">📅 Date:</span>
                                    <?= date('d/m/Y à H:i', strtotime($match['date'])) ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">📍 Lieu:</span>
                                    <?= htmlspecialchars($match['lieu']) ?>
                                </p>
                            </div>

                            <div class="mt-4">
                                <a href="match_details.php?id=<?= $match['id'] ?>"
                                    class="block w-full bg-blue-600 text-white text-center px-4 py-2 rounded hover:bg-blue-700">
                                    Voir les détails
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; 2026 Billetterie Sportive. Tous droits réservés.</p>
        </div>
    </footer>
</body>

</html>