Description:
===

Plugin to retrieve fuel price information from open data https://www.prix-carburants.gouv.fr

Configuration:
===

In the configuration of the plugin :
![image](./Configuration.png?raw=true)

The part `Price List` allows to choose the frequency with which the data of the stations are updated.  
The second part, `Geolocation command` focuses on how often nearby stations are updated when the location command updates (useful especially via mobile apps).

Equipment creation :
===

![image](./Equipment.png?raw=true)

At first, please choose a fuel and then you have 2 options: 
1. A search around a location.
    - Either by using the location entered in the Jeedom configuration, or via a command
    - Then indicate the maximum radius (as the crow flies) of the search, and the number of stations, up to 10, maximum to display.

2. A search by favorite station.
  - Choose in the drop-down menus the department, the city and then the station
  - Add up to 10 favorite stations
  - And indicate the order in which you want the stations to be displayed, i.e. by order of selection or increasing price.

The 2 options can be combined. The favorite stations will then be displayed first, then the rest of the displayed stations will be completed by those with the best price in the radius of the selected location.

Widget :
===

Once saved, the equipment will be visible (if enabled) on the dashboard with the following design:
- Desktop view :
![image](./Desktop.png?raw=true)
- Mobile view :
![image](./Mobile.png?raw=true)

Command:
===

In addition to the values displayed in the widgets, the equipment contains the following commands:
- ID: station number of the government site.
- Address: address displayed on the desktop widget.
- Full address.
- Update: date and time of last update of the station price.
- Price: price per liter of fuel.
- Price Full: price to fill your tank, depending on the tank size configured in the equipment.
- Distance: distance, as the crow flies, from your location to the station. Equals 0km, if no location available.
- Coord : GPS latitude and longitude coordinates.
- Waze : Link to be guided to the station via the Waze application.
- Google maps : Link to be guided to the station via the Google maps application.
- Logo: path to the station's branded logo.


List of stations:
===
The list of stations, selectable in a bookmark and for the search in a radius, is from JSON files saved in this plugin. This means that it is not continuously updated according to the updates of the government site.  
The files are generated using [python code available here](https://github.com/DuchkPy/fr.prix-carburants). Therefore, if you discover an error in a station, we encourage you to run the python script and submit the updated JSON file on the [Github of this plugin](https://github.com/floman321/prixcarburants).


Contribute:
===
You detect a bug, you want to propose an improvement, do not hesitate to say it on the forum. You can also directly make a PR on [the repository of this plugin](https://github.com/floman321/prixcarburants)

All the stations don't have their logo yet. Even if a great majority have them.  
If a station you are using doesn't have one yet, you can refer to [this file](./ListeLogo.md) to see the list of stations that still need to have the right logo.  
Logos should be resized to 60px as the largest dimension before submitting them via Github, or on the forum.

Changelog:
===
[Changelog](./changelog.md)