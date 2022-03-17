<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class prixcarburants extends eqLogic
{
	/*     * *********************** Constant creation *************************** */

	const DEFAULT_CRON = '7 2 * * *'; // cron by default if not set 
	const ZIP_PATH = __DIR__ . '/../../data'; // file path for zip file, data and so on


	/*     * *********************** Plugin static methods *************************** */

	/** Function to list all fuel station corresponding to the parameters defined in the configuration */
	public static function MAJVehicules($oneveh)
	{
		if ($oneveh != null) {
			log::add('prixcarburants', 'debug', 'Update only one vehicle');
			$Vehicules = array($oneveh);
		} else {
			log::add('prixcarburants', 'debug', 'Update all vehicle');
			$Vehicules = self::byType('prixcarburants');
		}

		foreach ($Vehicules as $unvehicule) {
			if ($unvehicule->getIsEnable() == 0) continue;

			$maselection = array();
			$SelectionFav = array();
			$idx = 0;

			$nom = $unvehicule->getName();
			$typecarburant = $unvehicule->getConfiguration('typecarburant', '');
			if ($typecarburant == '') log::add('prixcarburants', 'error', __('Le type de carburant n\'est pas renseigné dans la configuration de Prix Carburants : ', __FILE__) . $nom);
			$rayon = $unvehicule->getConfiguration('rayon', '30');
			$nbstation = $unvehicule->getConfiguration('nbstation', '0');
			$monformatdate = $unvehicule->getConfiguration('formatdate', '');
			//Get the list of favoris selected
			$NbFavoris = 0;
			if ($unvehicule->getConfiguration('Favoris') == '1') {
				for ($i = 1; $i <= 10; $i++) {
					if ($unvehicule->getConfiguration('station' . $i . '_Dep', '') != '' && $unvehicule->getConfiguration('station' . $i . '_Station', '') != '') {
						$StationFav[$i] = $unvehicule->getConfiguration('station' . $i . '_Station', '');
						$DepartementFav[$i] = $unvehicule->getConfiguration('station' . $i . '_Dep', '');
						$NbFavoris++;
					} else {
						break;
					}
				}
				if ($NbFavoris == 0) log::add('prixcarburants', 'error', __('Aucun favoris n\'est sélectionné dans la configuration de Prix Carburants : ', __FILE__) . $nom);
			}

			//Get position latitude and longitude only if geolocalisation is selected
			$malat = $malng = 0;
			if ($unvehicule->getConfiguration('ViaLoca') == '1') {
				if ($nbstation == '0') {
					log::add('prixcarburants', 'error', __('Le nombre de station n\'est pas renseigné dans la configuration de Prix Carburants : ', __FILE__) . $nom);
				} else {
					if ($unvehicule->getConfiguration('jeedom_loc') == 1) {
						$malat = config::byKey('info::latitude');
						$malng = config::byKey('info::longitude');
					} elseif ($unvehicule->getConfiguration('geoloc', 'none') == 'none') {
						$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top 1 Adresse');
						if (is_object($macmd)) $macmd->event(__('Pas de localisation sélectionnée', __FILE__));
						return;
					} else {
						$cmd = cmd::byId(str_replace('#', '', $unvehicule->getConfiguration('geoloc')));
						if ($cmd != null) {
							$coordonnees = $cmd->execCmd();
						} else {
							log::add(__CLASS__, 'error', __('commande de localisation non trouvée ', __FILE__));
						}
						$expcoord = explode(",", $coordonnees);
						$malat = $expcoord[0];
						$malng = $expcoord[1];
					}
				}
			}

			//Prepare and parse XML file

			//check price list existance.
			$xmlPath = self::ZIP_PATH . '/PrixCarburants_instantane.xml';
			log::add(__CLASS__, 'debug', 'path :' . realpath($xmlPath));
			$reader = XMLReader::open($xmlPath);


			$doc = new DOMDocument;
			$urlMap = 'https://www.google.com/maps/dir/?api=1&travelmode=driving&dir_action=navigate&origin=';;
			$urlWaze = 'https://waze.com/ul?';
			while ($reader->read()) {
				if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'pdv') {
					$lat = $reader->getAttribute('latitude') / 100000;
					$lng = $reader->getAttribute('longitude') / 100000;
					$mastationid = $reader->getAttribute('id');
					$MaStationDep = intval(substr($reader->getAttribute('cp'), 0, 2));
					$MonTest = False;
					$EstFavoris = False;

					//Distance only when localisation define
					$dist = 0;
					if ($malat != 0 && $malng != 0) $dist = prixcarburants::distance($malat, $malng, $lat, $lng);

					//Check if this station is a favorite
					$ordreFav = 0;
					if ($NbFavoris > 0) {
						for ($i = 1; $i <= $NbFavoris; $i++) {
							if ($mastationid == $StationFav[$i] && $MaStationDep == $DepartementFav[$i]) {
								$MonTest = True;
								$EstFavoris = True;
								$ordreFav = $i - 1;
								break;
							}
						}
					}
					if ($MonTest == False) {
						if ($unvehicule->getConfiguration('ViaLoca') == '1') {
							if ($dist <= $rayon) $MonTest = True;
						}
					}

					//Register only station that are a favorite or on max radius
					if ($MonTest == False) continue;

					$should_ignore = $unvehicule->getConfiguration('dateexpirevisible');
					$daysminus = $unvehicule->getConfiguration('dateexpire');
					$dminus5 = strtotime("-" . $daysminus . " days");

					//Import and review XML file
					$unestation = simplexml_import_dom($doc->importNode($reader->expand(), true));
					foreach ($unestation->prix as $prix) {
						if ($prix->attributes()->nom == $typecarburant) { //Filter by fuel type

							$prixlitre = $prix->attributes()->valeur . '';
							$maj = $prix->attributes()->maj . '';
							$marque = prixcarburants::getMarqueStation($mastationid, $MaStationDep);

							$PathToLogo = '../../plugins/' . __CLASS__ . '/data/logo/';
							$LogoName = strtoupper(str_replace(' ', '', $marque));
							if (file_exists(self::ZIP_PATH . '/logo/' . $LogoName . '.png')) {
								$logo = $PathToLogo . $LogoName . '.png';
							} else {
								$logo = $PathToLogo . 'AUCUNE.png';
							}

							if ($dminus5 >= strtotime($maj)) {
								if ($should_ignore) continue;
							}

							if ($EstFavoris) { //Register favorite station
								$SelectionFav[$ordreFav]['adresse'] = $marque . ', ' . $unestation->ville;
								$SelectionFav[$ordreFav]['adressecompl'] = $unestation->adresse . ", " . $reader->getAttribute('cp') . ' ' . $unestation->ville;
								$SelectionFav[$ordreFav]['prix'] = $prixlitre;
								$SelectionFav[$ordreFav]['maj']  = date($monformatdate, strtotime($maj));
								$SelectionFav[$ordreFav]['distance'] = $dist;
								$SelectionFav[$ordreFav]['id'] = $mastationid;
								$SelectionFav[$ordreFav]['coord'] = $lat . "," . $lng;
								$SelectionFav[$ordreFav]['waze'] = $urlWaze . 'to=ll.' . urlencode($lat . ',' . $lng) . '&from=ll.' . urlencode($malat . ',' . $malng) . '&navigate=yes';
								$SelectionFav[$ordreFav]['googleMap'] = $urlMap . urlencode($malat . ',' . $malng) . '&destination=' . urlencode($lat . ',' . $lng);
								$SelectionFav[$ordreFav]['logo'] = $logo;
							} else { //Register station that are on the radius
								$maselection[$idx]['adresse'] = $marque . ', ' . $unestation->ville;
								$maselection[$idx]['adressecompl'] = $unestation->adresse . ", " . $reader->getAttribute('cp') . ' ' . $unestation->ville;
								$maselection[$idx]['prix'] = $prixlitre;
								$maselection[$idx]['maj']  = date($monformatdate, strtotime($maj));
								$maselection[$idx]['distance'] = $dist;
								$maselection[$idx]['id'] = $mastationid;
								$maselection[$idx]['coord'] = $lat . "," . $lng;
								$maselection[$idx]['waze'] = $urlWaze . 'to=ll.' . urlencode($lat . ',' . $lng) . '&from=ll.' . urlencode($malat . ',' . $malng) . '&navigate=yes';
								$maselection[$idx]['googleMap'] = $urlMap . urlencode($malat . ',' . $malng) . '&destination=' . urlencode($lat . ',' . $lng);
								$maselection[$idx]['logo'] = $logo;
								$idx++;
							}
						}
					}
				}
			}
			$reader->close();

			log::add('prixcarburants', 'debug', 'Step count selection: ' . count($maselection) . ' for equipement: ' . $nom);

			//Sort by price, if needed (for favorite)
			usort($maselection, "prixcarburants::custom_sort");
			if ($unvehicule->getConfiguration('OrdreFavoris', 'Ordre') == "Prix") usort($SelectionFav, "prixcarburants::custom_sort");

			$lreservoir = $unvehicule->getConfiguration('reservoirlitre', 0);

			//Register favorites then require quantity of station from localisation
			$nbstation = $NbFavoris + $nbstation;
			for ($i = 1; $i <= $nbstation; $i++) {
				if ($i <= $NbFavoris) {
					$liste[$i - 1] = $SelectionFav[$i - 1];
				} else {
					$liste[$i - 1] = $maselection[$i - 1 - $NbFavoris];
				}

				if ($liste[$i - 1]['prix'] != '') { //Price available so, there is enough station on selected range
					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' ID');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['id']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Adresse');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['adresse']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Adresse complète');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['adressecompl']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' MAJ');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['maj']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Prix');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['prix']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Prix Plein');
					if (is_object($macmd)) $macmd->event(round($liste[$i - 1]['prix'] * $lreservoir, 2));

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Distance');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['distance']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Coord');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['coord']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Waze');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['waze']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Google maps');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['googleMap']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Logo');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['logo']);
				} else {
					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Adresse');
					$macmd2 = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Adresse complète');
					if (is_object($macmd)) {
						if ($i <= $NbFavoris) {
							$macmd->event(__('Favori pas correctement configuré', __FILE__));
							$macmd2->event(__('Favori pas correctement configuré', __FILE__));
						} else {
							$macmd->event(__('Plus de station disponible dans le rayon sélectionné', __FILE__));
							$macmd2->event(__('Plus de station disponible dans le rayon sélectionné', __FILE__));
						}
					}
					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' ID');
					if (is_object($macmd)) $macmd->event('');

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' MAJ');
					if (is_object($macmd)) $macmd->event('');

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Prix');
					if (is_object($macmd)) $macmd->event('');

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Distance');
					if (is_object($macmd)) $macmd->event('');

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Prix Plein');
					if (is_object($macmd)) $macmd->event('');

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Coord');
					if (is_object($macmd)) $macmd->event($liste[$i - 1]['coord']);

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Waze');
					if (is_object($macmd)) $macmd->event('');

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Google maps');
					if (is_object($macmd)) $macmd->event('');

					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(), 'Top ' . $i . ' Logo');
					if (is_object($macmd)) $macmd->event('');
				}
			}
			$unvehicule->refreshWidget();
		}
	}

	/** Function to get zip file from government website then update all values */
	public static function updatePrixCarburant()
	{
		log::add(__CLASS__, 'debug', 'zip path : ' . realpath(self::ZIP_PATH));
		$filenamezip = self::ZIP_PATH . '/PrixCarburants.zip';
		$current = file_get_contents("https://donnees.roulez-eco.fr/opendata/instantane");
		file_put_contents($filenamezip, $current);
		$zip = new ZipArchive;
		if ($zip->open($filenamezip) === TRUE) {
			$zip->extractTo(self::ZIP_PATH);
			$zip->close();
			log::add(__CLASS__, 'debug', 'prix zip OK get :' . $filenamezip);
			unlink($filenamezip);
			return true;
			//prixcarburants::MAJVehicules(null);
		} else {
			log::add(__CLASS__, 'debug', 'prix zip NOK get :' . $filenamezip);
			return false;
		}
	}
	
	/** Function to get the brand of a fuel station */
	public static function getMarqueStation($idstation, $DepStation)
	{
		$json = @file_get_contents(self::ZIP_PATH . '/listestations/stations' . $DepStation . '.json');
		if ($json !== false) {
			log::add(__CLASS__, 'debug', 'JSON file : ' . self::ZIP_PATH . '/data/listestations/stations' . $DepStation . '.json available');
			$parsed_json = json_decode($json, true);
			foreach ($parsed_json['stations'] as $row) {
				if ($row['id'] == $idstation) {
					return $row['marque'];
					break;
				}
			}
		} else {
			log::add(__CLASS__, 'debug', 'JSON file : ' . self::ZIP_PATH . '/listestations/stations' . $DepStation . '.json not available');
			return __('Erreur', __FILE__);
		}
	}
	
	/** Function to calculate a distance between selected location and a station */
	public static function distance($lat1, $lng1, $lat2, $lng2, $unit = 'k')
	{
		$earth_radius = 6378137;   // Terre = sphère de 6378km de rayon
		$rlo1 = deg2rad($lng1);
		$rla1 = deg2rad($lat1);
		$rlo2 = deg2rad($lng2);
		$rla2 = deg2rad($lat2);
		$dlo = ($rlo2 - $rlo1) / 2;
		$dla = ($rla2 - $rla1) / 2;
		$a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
		$d = 2 * atan2(sqrt($a), sqrt(1 - $a));
		return round(($earth_radius * $d) / 1000);
	}

	/** Function to sort fuel station */
	public static function custom_sort($a, $b)
	{
		return $a['prix'] > $b['prix'];
	}


	/*     * ********************* Cron management from configuration ************************* */

	/** Function setUpdateCron : called when by ajax on configuraiton save
	 * update both cron job (update price list and update rop from geoloc cmd) */
	public static function setUpdateCron()
	{ // called by ajax in config
		log::add(__CLASS__, 'debug', "update cron called");

		// get frequency from config
		$freq = config::byKey('freq', 'prixcarburants');
		if ($freq == 'prog') $freq = config::byKey('autorefresh', 'prixcarburants');

		if ($freq == '' || is_null($freq)) { // set default if not set
			log::add(__CLASS__, 'debug', __('Aucun Cron Défini pour la mise à jour, passage au défaut :', __FILE__) . self::DEFAULT_CRON);
			$freq = self::DEFAULT_CRON;
		}
		log::add(__CLASS__, 'debug', "Add cron to freq : $freq ");
		// update cron
		$cron = cron::byClassAndFunction(__CLASS__, 'udpateAllData');
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass(__CLASS__);
			$cron->setFunction('udpateAllData');
		}
		$cron->setEnable(1);
		$cron->setDeamon(0);
		$cron->setSchedule(checkAndFixCron($freq));
		$cron->save();

		// gestion de la mise à jour des commande de geoloc
		$freq_geo = config::byKey('freq_geo', 'prixcarburants');

		$eqLogics = self::byType('prixcarburants');
		foreach ($eqLogics as $eqLogic) {
			if ($freq_geo == 'event') {
				$eqLogic->setListener();
			} else {
				$eqLogic->removeListener();
			}
		}
		$cron = cron::byClassAndFunction(__CLASS__, 'pullGeoCmd');
		if ($freq_geo != 'event') {
			log::add(__CLASS__, 'debug', "Add cron to freq for CMD update: $freq_geo ");
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass(__CLASS__);
				$cron->setFunction('pullGeoCmd');
			}
			$cron->setEnable(1);
			$cron->setDeamon(0);
			$cron->setSchedule(checkAndFixCron($freq_geo));
			$cron->save();
		} elseif (is_object($cron)) {
			log::add(__CLASS__, 'debug', "Remove cron to freq for CMD update ");
			$cron->remove();
		}

		return self::getDueDateStr($freq);
	}

	/** Function pullGeoCmd : called on cron (see config) to update top and distance from geolocation command
	 * update top list and distance of the correponding eqLogic */
	public static function pullGeoCmd()
	{
		log::add(__CLASS__, 'debug', '╔═══════════════════════  PULL geoloc cmd sur id :');
		$eqLogics = self::byType('prixcarburants');
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('ViaLoca') == '1' && $eqLogic->getConfiguration('jeedom_loc') != 1 && $eqLogic->getConfiguration('geoloc', 'none') != 'none' && $eqLogic->getConfiguration('auto_update', false) == 1) {
				log::add(__CLASS__, 'debug', '--update eqLogic  :' . $eqLogic->getHumanName());
				self::MAJVehicules($eqLogic);
			}
		}
	}
	
	/** Function udpateAllData : called on cron to update price list and tops */
	public static function udpateAllData()
	{
		log::add(__CLASS__, 'debug', '------ Cron Triggering');
		$ISoK = self::updatePrixCarburant(); //update only xml
		if ($ISoK) {
			self::MAJVehicules(null); // update all equipement
		} else {
			log::add(__CLASS__, 'debug', 'Error Zip File, vehicule not updated');
		}
	}

	/*     * ********************* utils to get due date from frequency ************************* */

	/** Function getDueDate : called when by ajax on configuration save to update due date of cron job in config panel */
	public static function getDueDate()
	{
		$freq = config::byKey('freq', 'prixcarburants');
		if ($freq == 'prog') $freq = config::byKey('autorefresh', 'prixcarburants');

		if ($freq == '' || is_null($freq)) { // set default if not set
			log::add(__CLASS__, 'debug', __('Aucun Cron Défini pour la mise à jour, passage au défaut :', __FILE__) . self::DEFAULT_CRON);
			$freq = self::DEFAULT_CRON;
		}
		return self::getDueDateStr($freq);
	}

	/** Function getDueDateStr : called when by getDueDate (ajax on configuration save) to get string from due date of cron job */
	public static function getDueDateStr($freq)
	{
		$c = new Cron\CronExpression(checkAndFixCron($freq), new Cron\FieldFactory);
		$calculatedDate = array('prevDate' => '', 'nextDate' => '');
		$calculatedDate['prevDate'] = $c->getPreviousRunDate()->format('Y-m-d H:i:s');
		$calculatedDate['nextDate'] = $c->getNextRunDate()->format('Y-m-d H:i:s');
		return $calculatedDate;
	}


	/*     * ********************* Listener manager function ************************* */

	/** Function trigger : called when a geolocation command event is triggered 
	 * update top list and distance of the correponding eqLogic */
	public static function trigger($_option)
	{
		log::add(__CLASS__, 'debug', '╔═══════════════════════  Trigger sur id :' . $_option['id']);

		$eqLogic = self::byId($_option['id']);
		if (is_object($eqLogic) && $eqLogic->getIsEnable() == 1) {
			self::MAJVehicules($eqLogic);
		}
		log::add(__CLASS__, 'debug', "╚═════════════════════════════════════════ END Trigger ");
	}
	
	/** Function getListener : return listener list on geolocation command (depends on configuration) */
	public function getListener()
	{
		return listener::byClassAndFunction(__CLASS__, 'trigger', array('id' => $this->getId()));
	}
	/** Function removeListener : remove listeners on geolocation command */
	public function removeListener()
	{
		log::add(__CLASS__, 'debug', ' Suppression des Ecouteurs de ' . $this->getHumanName());
		$listener = $this->getListener();
		if (is_object($listener)) {
			$listener->remove();
		}
	}
	/** Function setListener : add listeners on geolocation command (depends on configuration) to call funciton 'trigger' on geoloc command event*/
	public function setListener()
	{
		log::add(__CLASS__, 'debug', ' Recording listeners');

		$glCmd = $this->getConfiguration('geoloc', '');

		log::add(__CLASS__, 'debug', 'Geoloc command : ' . $glCmd);

		if ($this->getIsEnable() == 0 || $glCmd === '' || !$this->getConfiguration('ViaLoca', false) || !$this->getConfiguration('auto_update', false) || config::byKey('freq_geo', 'prixcarburants') != 'event') {
			$this->removeListener();
			return;
		}

		$pregResult = preg_match_all("/#([0-9]*)#/", $glCmd, $matches);

		if ($pregResult === false) {
			log::add(__CLASS__, 'error', __('Erreur regExp Expression', __FILE__));
			$this->removeListener();
			return;
		}

		if ($pregResult < 1) {
			log::add(__CLASS__, 'debug', 'Pas de Commandes trouvés dans les listeners');
			$this->removeListener();
			return;
		}

		$listener = $this->getListener();
		if (!is_object($listener)) {
			$listener = new listener();
			$listener->setClass(__CLASS__);
			$listener->setFunction('trigger');
			$listener->setOption(array('id' => $this->getId()));
		}
		$listener->emptyEvent();

		$eventAdded = false;
		foreach ($matches[1] as $cmd_id) {
			if (!is_numeric($cmd_id)) continue;

			$cmd = cmd::byId($cmd_id);
			log::add(__CLASS__, 'debug', 'Ajout listener pour la commande :' . $cmd->getHumanName());
			if (!is_object($cmd)) continue;
			$listener->addEvent($cmd->getId());
			$eventAdded = true;
		}
		if ($eventAdded) {
			$listener->save();
		} else {
			$listener->remove();
		}
	}


	/*     * ********************* Méthodes d'instance ************************* */

	/** Method called before saving (creation and update therefore) of your */
	public function preSave()
	{
		//Create file with fuel price if it doesn't exist (first creation)
		if (!file_exists(self::ZIP_PATH . '/PrixCarburants_instantane.xml')) {
			log::add(__CLASS__, 'debug', 'XML file doesn\'t exist, yet.');
			$this->updatePrixCarburant();
			log::add(__CLASS__, 'debug', 'XML file created.');
		}
	}

	/** Method called after saving your Jeedom equipment */
	public function postSave()
	{
		//Create refresh commande
		$prixcarburantsCmd = $this->getCmd(null, 'refresh');
		if (!is_object($prixcarburantsCmd)) {
			log::add('prixcarburants', 'debug', 'refresh');
			$prixcarburantsCmd = new prixcarburantsCmd();
			$prixcarburantsCmd->setName(__('Actualiser', __FILE__));
			$prixcarburantsCmd->setEqLogic_id($this->getId());
			$prixcarburantsCmd->setLogicalId('refresh');
			$prixcarburantsCmd->setType('action');
			$prixcarburantsCmd->setSubType('other');
			$prixcarburantsCmd->setIsHistorized(0);
			$prixcarburantsCmd->setIsVisible(1);
			$prixcarburantsCmd->setOrder(0);
			$prixcarburantsCmd->save();
		}



		// manage listener if needed
		$this->setListener();
	}

	/** Method called after updating Jeedom equipment */
	public function postUpdate()
	{
		$nbstation = $this->getConfiguration('ViaLoca') == 1 ? $this->getConfiguration('nbstation', '0') : 0;
		$NbFavoris = 0;
		if ($this->getConfiguration('Favoris') == '1') {
			for ($i = 1; $i <= 10; $i++) {
				if ($this->getConfiguration('station' . $i . '_Dep', '') != '' && $this->getConfiguration('station' . $i . '_Station', '') != '') {
					$StationFav[$i] = $this->getConfiguration('station' . $i . '_Station', '');
					$DepartementFav[$i] = $this->getConfiguration('station' . $i . '_Dep', '');
					$NbFavoris++;
				} else {
					break;
				}
			}
		}
		$nbstation = $nbstation + $NbFavoris;

		$OrdreAffichage = 1;

		for ($i = 1; $i <= 20; $i++) {
			//Show only required quantity of station
			if ($i <= $nbstation) {
				$prixcarburantsCmd = $this->getCmd(null, 'TopID_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('string');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(0);
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' ID');
				$prixcarburantsCmd->setLogicalId('TopID_' . $i);
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'TopAdresse_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('string');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(1);
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
					$prixcarburantsCmd->setLogicalId('TopAdresse_' . $i);
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Adresse');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'TopAdresseCompl_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('string');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(0);
					$prixcarburantsCmd->setLogicalId('TopAdresseCompl_' . $i);
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Adresse complète');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'TopMaJ_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('TopMaJ_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('string');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
					$prixcarburantsCmd->setIsVisible(1);
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' MAJ');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'TopPrix_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('TopPrix_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('numeric');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(1);
					$prixcarburantsCmd->setUnite('€/L');
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
					$prixcarburantsCmd->setTemplate('dashboard', 'badge');
					$prixcarburantsCmd->setTemplate('mobile', 'badge');
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Prix');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'PrixPlein_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('PrixPlein_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('numeric');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(0);
					$prixcarburantsCmd->setUnite('€');
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
					$prixcarburantsCmd->setTemplate('dashboard', 'badge');
					$prixcarburantsCmd->setTemplate('mobile', 'badge');
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Prix Plein');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'Distance_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('Distance_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('numeric');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(0);
					$prixcarburantsCmd->setUnite('Km');
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
					$prixcarburantsCmd->setTemplate('dashboard', 'badge');
					$prixcarburantsCmd->setTemplate('mobile', 'badge');
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Distance');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'Coord_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('Coord_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('other');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(0);
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
					$prixcarburantsCmd->setTemplate('dashboard', 'badge');
					$prixcarburantsCmd->setTemplate('mobile', 'badge');
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Coord');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'TopWaze_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('TopWaze_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('string');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(0);
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Waze');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'TopGoogleMap_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('TopGoogleMap_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('string');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(0);
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Google maps');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;

				$prixcarburantsCmd = $this->getCmd(null, 'TopLogo_' . $i);
				if (!is_object($prixcarburantsCmd)) {
					$prixcarburantsCmd = new prixcarburantsCmd();
					$prixcarburantsCmd->setEqLogic_id($this->getId());
					$prixcarburantsCmd->setLogicalId('TopLogo_' . $i);
					$prixcarburantsCmd->setType('info');
					$prixcarburantsCmd->setSubType('string');
					$prixcarburantsCmd->setIsHistorized(0);
					$prixcarburantsCmd->setIsVisible(1);
					$prixcarburantsCmd->setDisplay('showNameOndashboard', 0);
				}
				$prixcarburantsCmd->setName('Top ' . $i . ' Logo');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;
			} else {
				//Remove all station to avoid having too much station when favorite is selected
				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' ID');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Adresse');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Adresse complète');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' MAJ');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Prix');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Prix Plein');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Distance');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Coord');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Waze');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Google maps');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

				$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(), 'Top ' . $i . ' Logo');
				if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
			}
		}
		prixcarburants::MAJVehicules($this);
	}

	/** Method called after deleting Jeedom equipment */
	public function preRemove()
	{
		$this->removeListener();
	}

	/** Collect data to design the widget */
	public function toHtml($_version = 'dashboard')
	{
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);

		// Gaz station template
		$GazStation_html = '';
		$GazStation_template = getTemplate('core', $version, 'gazstation.template', 'prixcarburants');
		$GazStation_Qtty = 0;

		for ($i = 1; $i <= 20; $i++) {
			//$TopAdresse_i = $this->getCmd(null, 'TopAdresse_'.$i);
			if (is_object($this->getCmd(null, 'TopAdresse_' . $i))) {
				$TopAdresse = $this->getCmd(null, 'TopAdresse_' . $i);
				$replace['#TopMarque#'] = is_object($TopAdresse) ? explode(",", $TopAdresse->execCmd())[0] : '';
				$replace['#TopVille#'] = is_object($TopAdresse) ? explode(",", $TopAdresse->execCmd())[1] : '';

				$PrixStation = $this->getCmd(null, 'TopPrix_' . $i);
				$replace['#TopPrix#'] = is_object($PrixStation) ? $PrixStation->execCmd() : '';

				$DateRecover = $this->getCmd(null, 'TopMaJ_' . $i);
				$replace['#TopMaJ#'] = is_object($DateRecover) ? __('le ', __FILE__) . $DateRecover->execCmd() : '';

				$LogoStation = $this->getCmd(null, 'TopLogo_' . $i);
				$replace['#LogoStation#'] = is_object($LogoStation) ? $LogoStation->execCmd() : '';

				$GazStation_html .= template_replace($replace, $GazStation_template);
				$GazStation_Qtty++;
			}
		}

		$replace['#GazStation#'] = $GazStation_html;
		$replace['#TemplateHeight#'] = 80 * $GazStation_Qtty;

		$replace['#PrixCarburants#'] = $this->getName();

		$refresh = $this->getCmd(null, 'refresh');
		$replace['#refresh#'] = is_object($refresh) ? $refresh->getId() : '';

		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'prixcarburants.template', 'prixcarburants')));
	}
  	/** Function to get the number of favorites selected */
    public function getFavNumber(){
		if($this->getConfiguration('Favoris')==false)return 0;
      	for($i = 1; $i<=10;$i++){
          $currSt = $this->getConfiguration('station'.$i.'_Commune', null);
          if($currSt == null)return $i-1;        	
        }
      	return false;
      
  	}
}
class prixcarburantsCmd extends cmd
{
	/** Execute plugin command */
	public function execute($_options = array())
	{
		// If 'click' on 'refresh' command
		if ($this->getLogicalId() == 'refresh') {
			log::add('prixcarburants', 'debug', 'Call "refresh" command for this object by ' . $this->getHumanName());
			$this->getEqLogic()->updatePrixCarburant();
		}
		return true;
	}
}