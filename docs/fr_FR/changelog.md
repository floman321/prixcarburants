# Changelog : Prix Carburants

### TODO list
- mettre en avant une confiance par rapport à la date de dernière mise à jour (via des couleurs, dont les seuils peuvent être modifé par l'utilisateur)

## 21/07/2020 21h
- Ajout critère pour mettre en évidence les dates de relevé trop anciennes.

## 21/07/2020 14h
- Ajout prix d'un plein selon capacité du réservoir
- Ajout distance de la station

## 21/07/2020
- Correction d'un bug qui gardait l'ancien prix du top, même s'il n'y avait pas de valeur à afficher

## 20/07/2020
- Ajout de la possibilité de choisir dans quel ordre afficher les favoris (par ordre de sélection, ou par prix)

## 16/07/2020
- Lors de cette mise à jour, chaque équipement devra être reconfiguré pour prendre en compte un certain nombre de modifications.
- Mise à jour des fichiers contenant la liste des stations (JSON).
- Améliorer la sélection de la station favoris
- Ajout de la possibilité, sur un seul véhicule avoir le choix entre suivre une station favorite et des stations autour des coordonnées
- Lors de la création du 1er équipement, création du fichier contenant la liste des prix. Pour avoir une liste à jour, plutôt qu'une ancienne reprise depuis le dépôt.
- Lors de la première installation, le fichiers des stations est récupéré sur le site du gouvernement. Ceci afin d'avoir les derniers prix disponible.

## 01/07/2020
- Affiche uniquement les lieu geoloc activés.
Cependant, si vous obtenez un message, "pas de station" …
Merci de re-sélectionner la configuration GPS (Lieu Geoloc ou Config Jeedom) puis sauvegarder.

## 30/06/2020
- Ajout des lieux géolocalisés dynamiques avec le plugin Geoloc. (Voiture en mouvement par ex).

## 24/06/2020
- Modification de la documentation
- Mise sur le market !

## 16/06/2020
- Création de la traduction (fr_FR et en_US)
- Changement de la mise en page de l'onglet commandes de l'équipement, pour donner la possibilité de choisir ce qui est historisable

## 12/06/2020
- Changement de la sélection de la localisation. Il faut désormais sélectionner la localisation (geotrav ou configuration) plutôt que rentrer les coordonnées.
- Modification pour ne prendre en compte que les localisations possédant des coordonnées de latitude et longitude

## 11/06/2020
- Mise à jour de la fonctionnalité de sélection du nombre de station à surveiller, qui ne fonctionnait pas quand l'équipement était créé avant la mise à jour précédente
- Ajout d'un message quand le nombre de station surveillé à supérieur au nombre de station disponible dans le rayon sélectionné

## 10/06/2020
- Ajout de la possibilité de choisir le nombre de station surveillée, jusqu'à 10 maximum
