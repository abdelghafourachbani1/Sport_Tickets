<?php
require_once __DIR__ . '/../config/setup.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Charger l'utilisateur
$user = User::findById($_SESSION['user_id']);

if (!$user) {
    header('Location: ../auth/logout.php');
    exit;
}

$error = '';
$success = '';

// Mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'nom' => Validation::sanitizeInput($_POST['nom'] ?? ''),
        'prenom' => Validation::sanitizeInput($_POST['prenom'] ?? ''),
        'email' => Validation::sanitizeInput($_POST['email'] ?? '')
    ];

    // Si un nouveau mot de passe est fourni
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            if (Validation::validerMotDePasse($_POST['new_password'])) {
                $data['password'] = $_POST['new_password'];
            } else {
                $error = "Le nouveau mot de passe ne respecte pas les exigences.";
            }
        } else {
            $error = "Les mots de passe ne correspondent pas.";
        }
    }

    if (empty($error)) {
        if ($user->updateProfile($data)) {
            $success = "Profil mis à jour avec succès !";
            // Recharger l'utilisateur
            $user = User::findById($_SESSION['user_id']);
        } else {
            $error = "Erreur lors de la mise à jour du profil.";
        }
    }
}

// Si c'est un acheteur, charger l'historique
$historique = [];
$stats = [];
if ($user->getRole() === 'acheteur') {
    $acheteur = Acheteur::findById($_SESSION['user_id']);
    $historique = $acheteur->consulterHistorique();
    $stats = $acheteur->getStatistiques();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold mb-8">Mon Profil</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Colonne gauche - Informations -->
            <div class="lg:col-span-1">
                <!-- Carte profil -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <div class="text-center mb-4">
                        <div class="w-24 h-24 bg-blue-600 rounded-full mx-auto flex items-center justify-center text-white text-3xl font-bold">
                            <?= strtoupper(substr($user->getPrenom(), 0, 1) . substr($user->getNom(), 0, 1)) ?>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-center mb-2"><?= htmlspecialchars($user->getNomComplet()) ?></h2>
                    <p class="text-gray-600 text-center mb-4"><?= htmlspecialchars($user->getEmail()) ?></p>
                    <div class="border-t pt-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Rôle:</span>
                            <span class="font-semibold capitalize"><?= htmlspecialchars($user->getRole()) ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Statut:</span>
                            <span class="font-semibold">
                                <span class="inline-block w-2 h-2 rounded-full <?= $user->isActif() ? 'bg-green-500' : 'bg-red-500' ?> mr-1"></span>
                                <?= $user->isActif() ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Membre depuis:</span>
                            <span class="font-semibold"><?= $user->getDateCreation()->format('d/m/Y') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistiques (pour acheteur) -->
                <?php if ($user->getRole() === 'acheteur' && !empty($stats)): ?>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-bold mb-4">Mes Statistiques</h3>
                        <div class="space-y-3">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Billets achetés</p>
                                <p class="text-2xl font-bold text-blue-600"><?= $stats['total_billets'] ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Montant dépensé</p>
                                <p class="text-2xl font-bold text-green-600"><?= number_format($stats['montant_total'], 2) ?> DH</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Commentaires</p>
                                <p class="text-2xl font-bold text-purple-600"><?= $stats['total_commentaires'] ?></p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Matchs à venir</p>
                                <p class="text-2xl font-bold text-orange-600"><?= $stats['matches_a_venir'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Colonne droite - Contenu principal -->
            <div class="lg:col-span-2">
                <!-- Modifier le profil -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-xl font-bold mb-6">Modifier mes informations</h3>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                                <input type="text" name="nom" required
                                    value="<?= htmlspecialchars($user->getNom()) ?>"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                                <input type="text" name="prenom" required
                                    value="<?= htmlspecialchars($user->getPrenom()) ?>"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required
                                value="<?= htmlspecialchars($user->getEmail()) ?>"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="border-t pt-6 mb-6">
                            <h4 class="font-semibold mb-4">Changer le mot de passe (optionnel)</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                                    <input type="password" name="new_password"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Min. 8 caractères, 1 majuscule, 1 chiffre</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                                    <input type="password" name="confirm_password"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_profile"
                            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Mettre à jour le profil
                        </button>
                    </form>
                </div>

                <!-- Historique des billets (pour acheteur) -->
                <?php if ($user->getRole() === 'acheteur'): ?>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold">Historique de mes billets</h3>
                            <?php if (!empty($historique)): ?>
                                <a href="download_recap.php"
                                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                                    📥 Télécharger le récapitulatif PDF
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($historique)): ?>
                            <div class="text-center py-8">
                                <p class="text-gray-600 mb-4">Vous n'avez acheté aucun billet pour le moment.</p>
                                <a href="matches.php" class="text-blue-600 hover:underline">
                                    Découvrir les matchs disponibles
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Match</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lieu</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Place</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($historique as $billet): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($billet['equipe_domicile']) ?> vs
                                                        <?= htmlspecialchars($billet['equipe_exterieur']) ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?= date('d/m/Y', strtotime($billet['match_date'])) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?= htmlspecialchars($billet['lieu']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?= htmlspecialchars($billet['categorie_nom']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                    <?= htmlspecialchars($billet['numero_place']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                                    <?= number_format($billet['prix_paye'], 2) ?> DH
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <a href="download_ticket.php?id=<?= $billet['id'] ?>"
                                                        class="text-blue-600 hover:text-blue-900">
                                                        📥 PDF
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>