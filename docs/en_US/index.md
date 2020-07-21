Description 
===

Plugin to retrieve fuel price information from open data https://www.prix-carburants.gouv.fr (only French gaz station)

Configuration
===
None

Création des équipements
===
![image](./Capture1.png?raw=true)

First of all, please choose a fuel type then

You have have 2 possibilties:
- A search around a localisation (Home, Work).
Fill radius field (30 km by default, if empty).
Fill location field :
  - Location on Geoloc plugin
  - GPS corrdoniate from Jeedom configuration (https://adresseipjeedom/index.php?v=d&p=administration#infotab)

![image](./Capture2.PNG?raw=true)

- A search by favorite gaz station.
  - Select department, town and then gaz station
  - Add up to 10 favorite stations, in the order you want to display them

![image](./capture3.PNG?raw=true)

The 2 options can be cumulated. The favorite stations will then be displayed first (in the filled order), then the rest of the displayed stations will be completed by the best priced ones in the radius of the selected location.