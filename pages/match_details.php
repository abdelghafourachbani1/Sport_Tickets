<?php
require_once __DIR__ . '/../config/setup.php';

// Vérifier l'ID du match
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: matches.php');
    exit;
}

$matchId = (int)$_GET['id'];

// Charger le match
$match = MatchSport::findById($matchId);

if (!$match) {
    header('Location: matches.php');
    exit;
}

// Récupérer les catégories
$categories = Categorie::getByMatch($matchId);

// Récupérer les commentaires validés
$commentaires = Commentaire::getByMatch($matchId, 'validé');

// Calculer la note moyenne
$noteMoyenne = $match->calculerNoteMoyenne();

// Vérifier si l'utilisateur peut commenter
$peutCommenter = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'acheteur') {
    $acheteur = Acheteur::findById($_SESSION['user_id']);
    $peutCommenter = $acheteur->peutCommenter($matchId);
}

// Statistiques
$billetsVendus = $match->getNombreBilletsVendus();
$placesDisponibles = $match->calculerPlacesDisponibles();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du match - Billetterie Sportive</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .star {
            color: #fbbf24;
            font-size: 1.5rem;
        }

        .star.empty {
            color: #d1d5db;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Retour -->
        <a href="matches.php" class="text-blue-600 hover:underline mb-4 inline-block">
            ← Retour aux matchs
        </a>

        <!-- Détails du match -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <!-- Équipe domicile -->
                <div class="text-center">
                    <img src="<?= BASE_URL . '/' . htmlspecialchars($match->getEquipeDomicile()->getLogo()) ?>"
                        alt="<?= htmlspecialchars($match->getEquipeDomicile()->getNom()) ?>"
                        class="w-32 h-32 mx-auto mb-4 object-contain"
                        onerror="this.src='<?= BASE_URL ?>/uploads/default-logo.png'">
                    <h2 class="text-2xl font-bold"><?= htmlspecialchars($match->getEquipeDomicile()->getNom()) ?></h2>
                    <p class="text-gray-600">Domicile</p>
                </div>

                <!-- VS -->
                <div class="flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-5xl font-bold text-gray-400 mb-4">VS</div>
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full">
                            <?= date('d/m/Y', strtotime($match->getDate()->format('Y-m-d H:i:s'))) ?>
                        </div>
                        <div class="mt-2 text-lg font-semibold">
                            <?= date('H:i', strtotime($match->getDate()->format('Y-m-d H:i:s'))) ?>
                        </div>
                    </div>
                </div>

                <!-- Équipe extérieur -->
                <div class="text-center">
                    <img src="<?= BASE_URL . '/' . htmlspecialchars($match->getEquipeExterieur()->getLogo()) ?>"
                        alt="<?= htmlspecialchars($match->getEquipeExterieur()->getNom()) ?>"
                        class="w-32 h-32 mx-auto mb-4 object-contain"
                        onerror="this.src='<?= BASE_URL ?>/uploads/default-logo.png'">
                    <h2 class="text-2xl font-bold"><?= htmlspecialchars($match->getEquipeExterieur()->getNom()) ?></h2>
                    <p class="text-gray-600">Extérieur</p>
                </div>
            </div>

            <!-- Informations -->
            <div class="border-t pt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">📍 Lieu</h3>
                        <p class="text-lg"><?= htmlspecialchars($match->getLieu()) ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">⏱️ Durée</h3>
                        <p class="text-lg"><?= $match->getDuree() ?> minutes</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">🎫 Billets vendus</h3>
                        <p class="text-lg"><?= $billetsVendus ?> / <?= $match->getPlacesTotales() ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">⭐ Note moyenne</h3>
                        <div class="flex items-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= round($noteMoyenne) ? '' : 'empty' ?>">★</span>
                            <?php endfor; ?>
                            <span class="ml-2 text-lg"><?= number_format($noteMoyenne, 1) ?>/5</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catégories et prix -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold mb-6">Catégories disponibles</h3>

            <?php if (empty($categories)): ?>
                <p class="text-gray-600">Aucune catégorie disponible pour ce match.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $categorie = Categorie::findById($cat['id']);
                        $disponible = $categorie->getPlacesDisponibles() > 0;
                        ?>
                        <div class="border-2 rounded-lg p-6 <?= $disponible ? 'border-blue-500' : 'border-gray-300 opacity-60' ?>">
                            <h4 class="text-xl font-bold mb-2"><?= htmlspecialchars($cat['nom']) ?></h4>
                            <p class="text-3xl font-bold text-blue-600 mb-4"><?= number_format($cat['prix'], 2) ?> DH</p>
                            <p class="text-gray-600 mb-4">
                                Places disponibles: <span class="font-semibold"><?= $cat['places_disponibles'] ?></span>
                            </p>

                            <?php if ($disponible): ?>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'acheteur'): ?>
                                    <a href="buy_ticket.php?match_id=<?= $matchId ?>&categorie_id=<?= $cat['id'] ?>"
                                        class="block w-full bg-blue-600 text-white text-center px-4 py-2 rounded hover:bg-blue-700">
                                        Acheter
                                    </a>
                                <?php else: ?>
                                    <a href="../auth/login.php"
                                        class="block w-full bg-gray-400 text-white text-center px-4 py-2 rounded hover:bg-gray-500">
                                        Connectez-vous pour acheter
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button disabled
                                    class="w-full bg-gray-300 text-gray-600 px-4 py-2 rounded cursor-not-allowed">
                                    Complet
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Commentaires -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h3 class="text-2xl font-bold mb-6">Avis des spectateurs (<?= count($commentaires) ?>)</h3>

            <?php if ($peutCommenter): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-blue-800 mb-2">Vous avez assisté à ce match. Laissez votre avis !</p>
                    <a href="add_comment.php?match_id=<?= $matchId ?>"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-block">
                        Laisser un commentaire
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($commentaires)): ?>
                <p class="text-gray-600">Aucun commentaire pour le moment. Soyez le premier à donner votre avis !</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($commentaires as $comment): ?>
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']) ?></p>
                                    <div class="flex items-center mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $comment['note'] ? '' : 'empty' ?>" style="font-size: 1rem;">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($comment['date_creation'])) ?>
                                </p>
                            </div>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($comment['texte'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>