# Changelog : Prix Carburants

### TODO list
- Créer une documentation
- Mettre à jour le fichier contenant la liste des stations (stations.json)
- Améliorer la sélection de la station favoris (au lieu d'afficher le fichier JSON brut)
- Pouvoir sur un seul véhicule avoir le choix entre suivre une station favorite et des stations autour des coordonnées
- mettre en avant une confiance par rapport à la date de dernière mise à jour (via des couleurs, dont les seuils peuvent être modifé par l'utilisateur)

Cependant, si vous obtenez un message, "pas de station" …
Merci de re-sélectionner la configuration GPS (Lieu Geoloc ou Config Jeedom) puis sauvegarder.

## 01/07/2020
- Affiche uniquement les lieu geoloc activés.

## 30 Juin 2020
- Ajout des lieux géolocalisés dynamiques avec le plugin Geoloc. (Voiture en mouvement par ex).

## 24 juin 2020
- Modification de la documentation
- Mise sur le market !

## 16 juin 2020
- Création de la traduction (fr_FR et en_US)
- Changement de la mise en page de l'onglet commandes de l'équipement, pour donner la possibilité de choisir ce qui est historisable

## 12 juin 2020 v2
- Modification pour ne prendre en compte que les localisations possédant des coordonnées de latitude et longitude

## 12 juin 2020
- Changement de la sélection de la localisation. Il faut désormais sélectionner la localisation (geotrav ou configuration) plutôt que rentrer les coordonnées.

## 11 juin 2020
- Mise à jour de la fonctionnalité de sélection du nombre de station à surveiller, qui ne fonctionnait pas quand l'équipement était créé avant la mise à jour précédente
- Ajout d'un message quand le nombre de station surveillé à supérieur au nombre de station disponible dans le rayon sélectionné

## 10 juin 2020
- Ajout de la possibilité de choisir le nombre de station surveillée, jusqu'à 10 maximum
