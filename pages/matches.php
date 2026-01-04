<?php
require_once __DIR__ . '/../config/setup.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

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

// Récupérer tous les matches (sans pagination pour simplicité)
$allMatches = MatchSport::getAll($filtres);
$totalMatches = count($allMatches);
$totalPages = ceil($totalMatches / $perPage);

// Paginer les résultats
$matches = array_slice($allMatches, $offset, $perPage);

// Récupérer les lieux pour le filtre
$db = Database::getInstance()->getConnection();
$queryLieux = "SELECT DISTINCT lieu FROM matches WHERE statut = 'validé' ORDER BY lieu";
$lieux = $db->query($queryLieux)->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les matchs - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Header -->
    <div class="bg-blue-600 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold">Tous les matchs disponibles</h1>
            <p class="mt-2">Découvrez tous nos événements sportifs</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold mb-4">Filtrer les matchs</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lieu</label>
                    <select name="lieu" class="w-full border-gray-300 rounded-md shadow-sm">
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
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">À partir du</label>
                    <input type="date" name="date_debut"
                        value="<?= $filtres['date_debut'] ?? '' ?>"
                        class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filtrer
                    </button>
                    <a href="matches.php"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des matches -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div class="flex justify-between items-center mb-6">
            <p class="text-gray-600"><?= $totalMatches ?> match(s) trouvé(s)</p>
        </div>

        <?php if (empty($matches)): ?>
            <div class="bg-white p-8 rounded-lg shadow-md text-center">
                <p class="text-gray-600">Aucun match ne correspond à vos critères.</p>
                <a href="matches.php" class="text-blue-600 hover:underline mt-2 inline-block">
                    Voir tous les matchs
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($matches as $match): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <div class="text-center flex-1">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($match['logo_domicile']) ?>"
                                        alt="<?= htmlspecialchars($match['equipe_domicile']) ?>"
                                        class="w-16 h-16 mx-auto mb-2 object-contain"
                                        onerror="this.src='<?= BASE_URL ?>/uploads/default-logo.png'">
                                    <p class="font-semibold text-sm"><?= htmlspecialchars($match['equipe_domicile']) ?></p>
                                </div>
                                <div class="text-2xl font-bold text-gray-400">VS</div>
                                <div class="text-center flex-1">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($match['logo_exterieur']) ?>"
                                        alt="<?= htmlspecialchars($match['equipe_exterieur']) ?>"
                                        class="w-16 h-16 mx-auto mb-2 object-contain"
                                        onerror="this.src='<?= BASE_URL ?>/uploads/default-logo.png'">
                                    <p class="font-semibold text-sm"><?= htmlspecialchars($match['equipe_exterieur']) ?></p>
                                </div>
                            </div>

                            <div class="border-t pt-4 space-y-2">
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">📅</span>
                                    <?= date('d/m/Y à H:i', strtotime($match['date'])) ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">📍</span>
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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-8 space-x-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?><?= !empty($filtres) ? '&' . http_build_query($filtres) : '' ?>"
                            class="px-4 py-2 rounded <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>