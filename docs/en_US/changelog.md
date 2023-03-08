# Changelog : Prix Carburants

## 03/02/2022 :
- Update equipment page design to latest Jeedom standard.

## 08/14/2022 :
- Update gaz station list.

## 03/14/2022 :

## 05/11/2022 :
- Add mobile and desktop widgets with station logo view (list of logos to be completed)
- Distance to gaz station is egal to 0km if ther isn't any localisation define yet.

## 03/04/2022 :
- added definition of price list update frequency in the equipment config
- modification of the display layout in the equipment
- added geolocation command for top station calculation.
- added option to update automatically the top stations with the update of the geolocation command

## 03/02/2022
- Update to be compatible with Jeedom v4.2

## 07/21/2020
- Fixed a bug that kept the old top price, even if there was no value to display

## 07/20/2020
- Add the oissubility to select the display order of favorites

## 07/16/2020
- After this update, all equipment will need to be open to update configuration. This to allow all following point to works correctly.
- Gaz station files updates (JSON)
- Improve favorite selection
- Add the possibility on one equipement to follow several favorites (up to 10) and gaz stations arround a location.
- When the first equipement is created, creation of the list of price.
- During first install, gaz station price are collected from government website. In order to have fresh values.

## 07/01/2020
- Show only activated geoloc
However if you have the message "No more stations available in the selected radius", please select again the location option and save the equipement.

## 06/30/2020
- Add dynamix geolocation through Geoloc plugin (for car in mouvement, for example)

## 06/24/2020
- Documentation update
- Added on Jeedom market !

## 06/16/2020
- Translation created (fr_FR and en_US)
- Update command tab on equipement, for a better presentation and add the possibilty to choose which element can be historized.
