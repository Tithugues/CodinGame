<?php

function conway($start, $nbLines) {
    $NChaine = array($start);
    $NbLignes = (int)$nbLines-1;
    $Ligne = 0;
    while($Ligne < $NbLignes) {
        $oldChaine = $NChaine;
        $NChaine = array();
        $Place = 1;
        $Longueur = count($oldChaine);
        $Courante = $oldChaine[0];
        $Compteur = 1;
        while($Place < $Longueur) {
            if ($oldChaine[$Place] == $Courante) {
                $Compteur++;
            }
            else {
                $NChaine[] = $Compteur;
                $NChaine[] = $oldChaine[$Place-1];
                $Compteur = 1;
                $Courante = $oldChaine[$Place];
            }
            $Place++;
        }
        $NChaine[] = $Compteur;
        $NChaine[] = $oldChaine[$Place-1];
        $Ligne++;
    }

    return $NChaine;
}

function formatConway($aNumbers) {
    return implode(' ', $aNumbers);
}