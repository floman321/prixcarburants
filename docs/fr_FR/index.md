Description 
===

Plugin permettant de récupérer les informations des prix des carburants d'après les open data https://www.prix-carburants.gouv.fr

Configuration
===
Aucune

Création des équipements
===
![image](./Capture1.PNG?raw=true)

Dans un premier temps, merci de choisir un carburant puis

Vous avez 2 options : 
- Une recherche autour d'un point gps (Domicile, Travail).
Remplissez le champ "Rayon" (par défaut 30km si vide)
Remplissez le champ "Chercher autour de " : 
  - Lieux dans le plugin Geoloc
  - Le repère GPS dans la configuration de Jeedom (https://adresseipjeedom/index.php?v=d&p=administration#infotab)

![image](./Capture2.PNG?raw=true)

- Une recherche par station favorite.
  - Choisir dans les menus déroulants le département, la ville puis la station
  - Ajouter jusqu'à … 10 stations favorites, dans l'ordre que vous voulez les afficher

![image](./capture3.PNG?raw=true)

Les 2 options peuvent se cumuler. Les stations favorites s'afficheront alors en premier (dans l'ordre rempli), puis le reste des stations affichées sera complété‚ par celles aux meilleurs prix dans le rayon de la localisation sélectionnée.
