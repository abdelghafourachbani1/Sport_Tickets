<?php
require_once __DIR__ . '/../config/setup.php';

// Vérifier l'authentification admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Charger l'admin
$admin = Admin::findById($_SESSION['user_id']);

if (!$admin) {
    header('Location: ../auth/logout.php');
    exit;
}

// Récupérer les statistiques globales
$stats = $admin->consulterStatistiquesGlobales();

// Récupérer les matches en attente
$matchesEnAttente = $admin->getMatchesEnAttente();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Dashboard Administrateur</h1>
            <div class="space-x-4">
                <a href="stats.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    📊 Statistiques détaillées
                </a>
                <a href="users.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    👥 Gérer les utilisateurs
                </a>
                <a href="comments.php" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    💬 Modérer les commentaires
                </a>
            </div>
        </div>

        <!-- Statistiques globales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Utilisateurs actifs</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total_utilisateurs'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Matches validés</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total_matches'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Billets vendus</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total_billets_vendus'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Chiffre d'affaires</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['chiffre_affaires_total'], 2) ?> DH</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matches en attente -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Matches en attente de validation (<?= $stats['matches_en_attente'] ?>)</h2>
            </div>

            <?php if (empty($matchesEnAttente)): ?>
                <p class="text-gray-600 text-center py-8">Aucun match en attente de validation.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Match</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lieu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($matchesEnAttente as $match): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($match['equipe_domicile']) ?> vs
                                            <?= htmlspecialchars($match['equipe_exterieur']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= date('d/m/Y H:i', strtotime($match['date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= htmlspecialchars($match['lieu']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= htmlspecialchars($match['org_prenom'] . ' ' . $match['org_nom']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <a href="validate_match.php?id=<?= $match['id'] ?>"
                                            class="text-green-600 hover:text-green-900">✓ Valider</a>
                                        <a href="reject_match.php?id=<?= $match['id'] ?>"
                                            class="text-red-600 hover:text-red-900">✗ Refuser</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Matches (via VUE SQL) -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-6">Top 5 des matches les plus populaires</h2>

            <?php if (empty($stats['top_matches'])): ?>
                <p class="text-gray-600 text-center py-8">Aucune donnée disponible.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($stats['top_matches'] as $index => $topMatch): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="text-2xl font-bold text-gray-400">#<?= $index + 1 ?></div>
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars($topMatch['equipe_domicile'] . ' vs ' . $topMatch['equipe_exterieur']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($topMatch['lieu']) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-blue-600"><?= $topMatch['billets_vendus'] ?> billets</p>
                                <p class="text-sm text-gray-600"><?= number_format($topMatch['revenus'], 2) ?> DH</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>