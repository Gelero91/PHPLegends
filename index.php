<?php
// index.php

// Chemin vers le fichier qui stocke l'état du jeu
$fichierEtatJeu = 'game_state.txt';

// Répertoire des statistiques des éléments
$repertoireBaseElements = [
    'Arbre'   => ['skin' => '♣', 'type'=> 'sprite', 'hp' => 0, 'attaque' => 0, 'defense'=> 0, 'xp' => 0],
    'Gobelin' => ['skin' => '☃', 'type'=> 'ennemy', 'hp' => 4, 'attaque' => 2, 'defense'=> 0, 'xp' => 2],
    'Coffre'  => ['skin' => '©', 'type'=> 'sprite', 'hp' => 0, 'attaque' => 0, 'defense'=> 0, 'xp' => 0],
    'Coeur'   => ['skin' => '♥', 'type'=> 'coeur', 'hp' => 0, 'attaque' => 0, 'defense'=> 0, 'xp' => 0],
];

// Fonction pour initialiser l'état du jeu
function initialiserEtatJeu($fichierEtatJeu, $repertoireBaseElements) {
    // Initialisation de la carte 32x32 avec des cases vides
    $matriceDeCarte = array_fill(0, 32, array_fill(0, 32, '.'));
    
    // Placer des murs autour des bords
    for ($i = 0; $i < 32; $i++) {
        $matriceDeCarte[0][$i] = '■';
        $matriceDeCarte[31][$i] = '■';
        $matriceDeCarte[$i][0] = '■';
        $matriceDeCarte[$i][31] = '■';
    }
    
    // Placer le joueur au centre
    $positionDuJoueur = [16, 16];
    $matriceDeCarte[$positionDuJoueur[0]][$positionDuJoueur[1]] = '☻';

    // Initialiser les variables du joueur
    $playerHP = 10;
    $playerHPMax = 10;
    $playerDmg = 1;
    $playerDef = 1;
    $playerLvl = 1;
    $playerXp = 0;
    $Journal = "Rien pour l'instant.";



    function genererRepartitionAleatoire($elements, $tailleMatrice = 32, $nbElements = 10, $repartition = 2) {
        // Tableau des positions à retourner
        $positionsDesObjetsEtPnj = [];
    
        // Générer une liste de positions disponibles (en excluant les bords)
        $positionsDisponibles = [];
        for ($x = 1; $x < $tailleMatrice - 1; $x++) {
            for ($y = 1; $y < $tailleMatrice - 1; $y++) {
                $positionsDisponibles[] = [$x, $y];
            }
        }
    
        // Mélanger les positions pour introduire l'aléatoire
        shuffle($positionsDisponibles);
    
        // Limiter les positions en fonction du nombre d'éléments demandé
        $nbElements = min($nbElements, count($positionsDisponibles));
    
        // Ajouter des éléments selon le degré de répartition
        for ($i = 0; $i < $nbElements; $i++) {
            $position = array_pop($positionsDisponibles);
    
            // Appliquer un facteur de répartition (distance minimale entre deux éléments)
            $x = $position[0];
            $y = $position[1];
            foreach ($positionsDesObjetsEtPnj as $key => $value) {
                [$px, $py] = explode(',', $key);
                $distance = sqrt(pow($px - $x, 2) + pow($py - $y, 2));
                if ($distance < $repartition) {
                    $i--;
                    continue 2; // Recommence la tentative pour cet élément
                }
            }
    
            // Associer un élément aléatoire de la liste
            $element = $elements[array_rand($elements)];
            $positionsDesObjetsEtPnj["$x,$y"] = $element;
        }
    
        return $positionsDesObjetsEtPnj;
    }
    
    // Exemple d'utilisation
    $elements = ['Arbre', 'Gobelin', 'Coeur', 'Coffre'];
    $nbElements = 15; // Nombre d'éléments à placer
    $repartition = 3; // Degré de répartition
    $tailleMatrice = 32;
    
    $positionsDesObjetsEtPnj  = genererRepartitionAleatoire($elements, $tailleMatrice, $nbElements, $repartition);

    // Placer des objets et PNJ avec leurs statistiques
    /*
    $positionsDesObjetsEtPnj = [
        '5,5' => 'Arbre',
        '20,20' => 'Coeur',
        '20,25' => 'Gobelin',
        '25,25' => 'Arbre',
    ];
    */

    // Mettre à jour la matrice avec les skins et ajouter les stats
    foreach ($positionsDesObjetsEtPnj as $position => $typeElement) {
        list($x, $y) = explode(',', $position);
        $skin = $repertoireBaseElements[$typeElement]['skin'];
        $matriceDeCarte[$x][$y] = $skin;
        $positionsDesObjetsEtPnj[$position] = [
            'type' => $typeElement,
            'x' => $x,
            'y' => $y,
            'statistiques' => $repertoireBaseElements[$typeElement],
        ];
    }

    // Sauvegarder l'état du jeu
    $etatJeu = [
        'matriceDeCarte' => $matriceDeCarte,
        'positionDuJoueur' => $positionDuJoueur,
        'positionsDesObjetsEtPnj' => $positionsDesObjetsEtPnj,
        'playerHP' => $playerHP,
        'playerHPMax' => $playerHPMax,
        'playerDmg' => $playerDmg,
        'playerDef' => $playerDef,
        'playerLvl' => $playerLvl,
        'playerXp' => $playerXp,
        'Journal' => $Journal,
    ];

    file_put_contents($fichierEtatJeu, json_encode($etatJeu));
}

// Vérifier si le formulaire est soumis pour réinitialiser
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reinitialiser'])) {
    initialiserEtatJeu($fichierEtatJeu, $repertoireBaseElements);
    header('Location: game.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Legends - Menu</title>
</head>
<body>
    <h1>Bienvenue dans PHP Legends</h1>
    
    <!-- Formulaire pour réinitialiser le jeu -->
    <form method="POST">
        <button type="submit" name="reinitialiser" value="1">Réinitialiser la carte</button>
    </form>
    
    <p><a href="game.php">Commencer le jeu sans réinitialiser la carte</a></p>
</body>
</html>
