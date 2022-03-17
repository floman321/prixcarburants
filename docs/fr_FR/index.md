Description :
===

Plugin permettant de récupérer les informations des prix des carburants d'après les open data https://www.prix-carburants.gouv.fr

Configuration :
===

Dans la configuration du plugin :
![image](./Configuration.png?raw=true)

La partie `Liste Prix` permet de choisir la fréquence avec laquelle les données des stations sont mises à jour.  
La deuxième partie, `Commande géolocalisation` se concentre sur la fréquence de mise à jour des stations à proximité quand la commande localisation se met à jour (utile surtout via les applications mobiles).

Création des équipements :
===

![image](./Equipement.png?raw=true)

Dans un premier temps, merci de choisir un carburant puis, vous avez 2 options : 
1. Une recherche autour d'une localisation.
    - Soit en utilisant la localisation rentrée dans la configuration de Jeedom, soit via une commande
    - Puis indiquer le rayon maximum (à vol d'oiseau) de la recherche, et le nombre de station, jusqu'à 10, maximum à afficher.

2. Une recherche par station favorite.
  - Choisir dans les menus déroulant le département, la ville puis la station
  - Ajouter jusqu'à 10 stations favorites
  - Et indiquez l'ordre d'affichage voulu, à savoir par ordre de sélection ou de prix croissant.

Les 2 options peuvent se cumuler. Les stations favorites s'afficheront alors en premier, puis le reste des stations affichées sera complété par celles au meilleur prix dans le rayon de la localisation sélectionné.

Widget :
===

Une fois sauvegardé, l'équipement sera visible (si activé) sur le dashboard avec le design suivant :
- Vue desktop :
![image](./Desktop.png?raw=true)
- Vue mobile :
![image](./Mobile.png?raw=true)

Commande :
===

Outre les valeurs affichées dans les widgets, l'équipement contient les commandes suivantes :
- ID : numéro de la station du site du gouvernement.
- Adresse : adresse affichée sur le widget desktop.
- Adresse complète.
- MAJ : date et heure de dernière mise à jour du prix de la station.
- Prix : prix au litre du carburant.
- Prix Plein : prix pour remplir votre réservoir, en fonction de la taille du réservoir renseigné dans l'équipement.
- Distance : distance, à vol d'oiseau, depuis votre localisation jusqu'à la station. Egale 0km, si pas de localisation disponible.
- Coord : coordonnées GPS latitude et longitude.
- Waze : Lien pour être guidé vers la station via l'application Waze.
- Google maps : Lien pour être guidé vers la station via l'application Google maps.
- Logo : chemin vers le logo de la marque de la station.

Liste des stations :
===
La liste des stations, sélectionnable dans un favoris et pour la recherche dans un rayon, est issus de fichiers JSON enregistré dans ce plugin. Ce qui signifie que ce n'est pas mis à jour en continue en fonction des mises à jour du site du gouvernement.  
Les fichiers sont généré en utilisant le [code python disponible ici](https://github.com/DuchkPy/fr.prix-carburants). De ce fait, si vous découvrez une erreur dans une station, nous vous encorageons à lancer le script python et à nous soumettre la mise à jour du fichier JSON sur le [Github de ce plugin](https://github.com/floman321/prixcarburants).

Contribuer :
===
Vous déceler un bug, vous voulez proposer une amélioration, n'hésitez pas à le dire sur le forum. Vous pouvez aussi directement faire un PR sur [le dépôt de ce plugin](https://github.com/floman321/prixcarburants)

L'intégralité des stations n'ont pas encore leur logo. Même si une grande majorité les ont.  
Si une station que vous utilisez n'en a pas encore, vous pouvez vous référer à [ce fichier](./ListeLogo.md) pour connaitre la liste des stations encore à pourvoir du bon logo.  
Les logos sont à redimensionner à 60px comme plus grande dimension avant de les proposer via Github, ou sur le forum.

Changelog :
===
[Changelog](./changelog.md)