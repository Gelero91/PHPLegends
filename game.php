<?php
$fichierEtatJeu = 'game_state.txt';

function recupererEtatJeu($fichierEtatJeu) {
    if (file_exists($fichierEtatJeu)) {
        $contenu = file_get_contents($fichierEtatJeu);
        return json_decode($contenu, true);
    }
    return null;
}

function enregistrerEtatJeu($fichierEtatJeu, $etat) {
    file_put_contents($fichierEtatJeu, json_encode($etat));
}

function combat($personnageNonJoueur, &$etatDuJeu, &$journal) {
    $playerHP = $etatDuJeu['playerHP'];
    $playerDmg = $etatDuJeu['playerDmg'];
    $playerDef = $etatDuJeu['playerDef'];
    $playerXp = $etatDuJeu['playerXp'];

    $nomDuPnj = $personnageNonJoueur['type'];
    $defenseDuPnj = $personnageNonJoueur['statistiques']['defense'];
    $attaqueDuPnj = $personnageNonJoueur['statistiques']['attaque'];

    $degatsInfligesParLeJoueur = $playerDmg - $defenseDuPnj; 
    if ($degatsInfligesParLeJoueur < 0) {
        $degatsInfligesParLeJoueur = 0;
    }

    $degatsInfligesParLePnj = $attaqueDuPnj - $playerDef;
    if ($degatsInfligesParLePnj < 0) {
        $degatsInfligesParLePnj = 0;
    }

    $playerHP -= $degatsInfligesParLePnj;
    $personnageNonJoueur['statistiques']['hp'] -= $degatsInfligesParLeJoueur;

    $journal = "Le joueur attaque ".$nomDuPnj." et inflige ".$degatsInfligesParLeJoueur." dégâts !<br>";
    $journal .= $nomDuPnj." riposte et inflige ".$degatsInfligesParLePnj." dégâts !<br>";
    // $journal .= "Le joueur a maintenant ".$playerHP." points de vie restants.<br>";
    $journal .= $nomDuPnj." a maintenant ".$personnageNonJoueur['statistiques']['hp']." points de vie restants.<br>";

    if ($personnageNonJoueur['statistiques']['hp'] <= 0) {
        $journal .= $nomDuPnj." est mort.<br>";
        $playerXp += $personnageNonJoueur['statistiques']['xp'];
        $positionDuPnj = $personnageNonJoueur['x'] . ',' . $personnageNonJoueur['y'];
        $etatDuJeu['matriceDeCarte'][$personnageNonJoueur['x']][$personnageNonJoueur['y']] = '.';
        unset($etatDuJeu['positionsDesObjetsEtPnj'][$positionDuPnj]);
    }

    if ($playerHP <= 0) {
        $journal .= "Le joueur est mort.<br>";
    }

    $etatDuJeu['playerHP'] = $playerHP;
    $etatDuJeu['playerXp'] = $playerXp;

    $positionDuPnj = $personnageNonJoueur['x'] . ',' . $personnageNonJoueur['y'];
    $etatDuJeu['positionsDesObjetsEtPnj'][$positionDuPnj] = $personnageNonJoueur;

    enregistrerEtatJeu('game_state.txt', $etatDuJeu);
}

function sprite($personnageNonJoueur, &$journal) {
    $nomDuPnj = $personnageNonJoueur['type'];
    $journal = "C'est un ".$nomDuPnj.".";
}

function coeur($personnageNonJoueur, &$etatDuJeu, &$journal) {
    $playerHP = $etatDuJeu['playerHP'];
    $playerHPMax = $etatDuJeu['playerHPMax'];

    $playerHP += 5;

    if ($playerHP > $playerHPMax) {
        $playerHP = $playerHPMax;
    }

    $nomDuPnj = $personnageNonJoueur['type'];
    $journal = "C'est un ".$nomDuPnj.".<br> Vous êtes soigné de 5hp.";
    
    $etatDuJeu['playerHP'] = $playerHP;

    $positionDuPnj = $personnageNonJoueur['x'] . ',' . $personnageNonJoueur['y'];
    $etatDuJeu['positionsDesObjetsEtPnj'][$positionDuPnj] = $personnageNonJoueur;

    enregistrerEtatJeu('game_state.txt', $etatDuJeu);
}

$etatJeu = recupererEtatJeu($fichierEtatJeu);

if ($etatJeu !== null) {
    $playerHP = $etatJeu['playerHP'];
    $playerDmg = $etatJeu['playerDmg'];
    $playerDef = $etatJeu['playerDef'];
    $playerLvl = $etatJeu['playerLvl'];
    $playerXp = $etatJeu['playerXp'];
    $Journal = $etatJeu['Journal'];

    if (isset($_GET['direction'])) {
        $direction = $_GET['direction'];
        $nouvelleLigne = $etatJeu['positionDuJoueur'][0];
        $nouvelleColonne = $etatJeu['positionDuJoueur'][1];

        switch ($direction) {
            case 'haut':
                $nouvelleLigne--;
                break;
            case 'bas':
                $nouvelleLigne++;
                break;
            case 'gauche':
                $nouvelleColonne--;
                break;
            case 'droite':
                $nouvelleColonne++;
                break;
        }

        if ($nouvelleLigne >= 0 && $nouvelleLigne < 32 && $nouvelleColonne >= 0 && $nouvelleColonne < 32) {
            $caseActuelle = $etatJeu['matriceDeCarte'][$nouvelleLigne][$nouvelleColonne];

            if ($caseActuelle == '.') {
                $etatJeu['matriceDeCarte'][$etatJeu['positionDuJoueur'][0]][$etatJeu['positionDuJoueur'][1]] = '.'; 
                $etatJeu['positionDuJoueur'] = [$nouvelleLigne, $nouvelleColonne];
                $etatJeu['matriceDeCarte'][$nouvelleLigne][$nouvelleColonne] = '☻';
            } elseif ($caseActuelle != '■' && isset($etatJeu['positionsDesObjetsEtPnj'][$nouvelleLigne.','.$nouvelleColonne])) {
                $pnj = $etatJeu['positionsDesObjetsEtPnj'][$nouvelleLigne.','.$nouvelleColonne];
                
                if ($pnj['statistiques']['type']==="ennemy") {
                    combat($pnj, $etatJeu, $Journal);
                    if ($pnj['statistiques']['hp'] <= 0) {
                        $etatJeu['matriceDeCarte'][$nouvelleLigne][$nouvelleColonne] = '.';
                        unset($etatJeu['positionsDesObjetsEtPnj'][$nouvelleLigne.','.$nouvelleColonne]);
                    }
                } elseif ($pnj['statistiques']['type']==="sprite") {
                    sprite($pnj, $Journal);
                } elseif ($pnj['statistiques']['type']==="coeur") {
                    coeur($pnj, $etatJeu, $Journal);
                    $etatJeu['matriceDeCarte'][$nouvelleLigne][$nouvelleColonne] = '.';
                    unset($etatJeu['positionsDesObjetsEtPnj'][$nouvelleLigne.','.$nouvelleColonne]);
                }   
            }
            enregistrerEtatJeu($fichierEtatJeu, $etatJeu);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Legends</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="carte">
            <?php
            if ($etatJeu === null) {
                echo "Pas de monde initialisé, allez sur index.php pour lancer une partie ! </br>";
            } else if ($etatJeu['playerHP'] === 0) {
                echo "Vous êtes mort ! Allez sur index.php pour lancer une nouvelle partie ! </br>";
            } else {
                echo "<pre>";
                foreach ($etatJeu['matriceDeCarte'] as $ligneDeCarte) {
                    echo implode(" ", $ligneDeCarte) . "\n";
                }
                echo "</pre>";
            }
            ?>
        </div>
        
        <div class="infos">
            <div class="stats">
            <h2>Infos Joueur</h2>
            <p>HP : <?= $playerHP ?? '' ?>/<?= $etatJeu['playerHPMax'] ?? '' ?></p>
            <p>Attaque : <?= $playerDmg ?? '' ?></p>
            <p>Défense : <?= $playerDef ?? '' ?></p>
            <p>Level : <?= $playerLvl ?? '' ?></p>
            <p>XP : <?= $playerXp ?? '' ?></p>
            <h2>Journal</h2>
            <p><?= $Journal ?? '' ?></p>
            </div>
            <div class="controls">
                <h2>Déplacement</h2>
                <form method="get" class="grid-controls">
                    <div class="empty"></div>
                    <button type="submit" name="direction" value="haut">↑</button>
                    <div class="empty"></div>

                    <button type="submit" name="direction" value="gauche">←</button>
                    <div class="empty"></div>
                    <button type="submit" name="direction" value="droite">→</button>

                    <div class="empty"></div>
                    <button type="submit" name="direction" value="bas">↓</button>
                    <div class="empty"></div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
