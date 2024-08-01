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

use PhpParser\Node\Stmt\Switch_;

require_once __DIR__  . '/../../../../core/php/core.inc.php';

class prixcarburants extends eqLogic
{
	/*     * *********************** Constant creation *************************** */

	const DEFAULT_CRON = '7 2 * * *'; // cron by default if not set 
	const ZIP_PATH = __DIR__ . '/../../data'; // file path for zip file, data and so on
	const DEFAULT_CMD = array(
                              'id' => '',
                              'adresse' => '',
                              'adressecompl' => '',
                              'maj' => '',
                              'prix' => '',
                              'distance' => '',
                              'coord' => '',
                              'waze' => '',
                              'googleMap' => '',
                              'logo' => ''
                              );

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
			self::MAJOneVehicule($unvehicule);
		}
	}
	public static function MAJOneVehicule($unvehicule){
			$maselection = array();
			$SelectionFav = array();
			$StationFav = array();
          	$vehiculeId = $unvehicule->getId();
			$urlMap = 'https://www.google.com/maps/dir/?api=1&travelmode=driving&dir_action=navigate&origin=';;
			$urlWaze = 'https://waze.com/ul?';
			$should_ignore = $unvehicule->getConfiguration('dateexpirevisible');
			$daysminus = $unvehicule->getConfiguration('dateexpire');
      		$dminus5 = strtotime("-" . $daysminus . " days");
			$nom = $unvehicule->getName();
			$rayon = $unvehicule->getConfiguration('rayon', '30');
			$nbstation = $unvehicule->getConfiguration('nbstation', '0');
      		$PathToLogo = '../../plugins/' . __CLASS__ . '/data/logo/';
			
			$typecarburant = $unvehicule->getConfiguration('typecarburant', '');
			if ($typecarburant == '') log::add('prixcarburants', 'error', __('Le type de carburant n\'est pas renseigné dans la configuration de Prix Carburants : ', __FILE__) . $nom);
			
			$monformatdate = $unvehicule->getConfiguration('formatdate', '');
			if($monformatdate =='perso') $monformatdate = $unvehicule->getConfiguration('formatdate_perso', '');
			log::add(__CLASS__,'debug', 'date format : '.$monformatdate);

			$idx = 0;
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
						$macmd = cmd::byEqLogicIdCmdName($vehiculeId, 'Top 1 Adresse');
						if (is_object($macmd)) $macmd->event(__('Pas de localisation sélectionnée', __FILE__));
						return;
					} else {
						$cmd = cmd::byId(str_replace('#', '', $unvehicule->getConfiguration('geoloc')));
						if ($cmd != null) {
							$coordonnees = $cmd->execCmd();
						} else {
							log::add(__CLASS__, 'error', __('commande de localisation non trouvée ', __FILE__));
                          	return false;
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
					if ($malat != 0 && $malng != 0) $dist = self::distance($malat, $malng, $lat, $lng);
					
					//Check if this station is a favorite
					$MonTest  = $EstFavoris = in_array($mastationid, $StationFav) && in_array($MaStationDep, $DepartementFav);
					$ordreFav = $EstFavoris?(array_search($mastationid, $StationFav)-1):0;
                  	
					if ($EstFavoris == false && $unvehicule->getConfiguration('ViaLoca') == '1' ) $MonTest = $dist <= $rayon;

					//Register only station that are a favorite or on max radius
					if ($MonTest == false) continue;
                  
                  	// general variable
                  	$marque = prixcarburants::getMarqueStation($mastationid, $MaStationDep);
                  	$LogoName = strtoupper(str_replace(' ', '', $marque));
                  	$logo =file_exists(self::ZIP_PATH . '/logo/' . $LogoName . '.png')?$PathToLogo . $LogoName . '.png':$PathToLogo . 'AUCUNE.png';
					$unestation = simplexml_import_dom($doc->importNode($reader->expand(), true));
                  	$prixlitre = self::getPriceFromStationXML($unestation, $typecarburant);
                  	$computePrice = true;//flag to test if we should consider price if outdated base
                    if ($prixlitre && $dminus5 >= strtotime($prixlitre['maj']))$computePrice=!$should_ignore;
                  	
                    if($EstFavoris){
                   		$SelectionFav[$ordreFav]['adresse'] = $marque . ', ' . $unestation->ville;
                        $SelectionFav[$ordreFav]['adressecompl'] = $unestation->adresse . ", " . $reader->getAttribute('cp') . ' ' . $unestation->ville;
                        $SelectionFav[$ordreFav]['prix'] = $computePrice && $prixlitre?$prixlitre['prix']:0;
                        $SelectionFav[$ordreFav]['maj'] = self::TranslateDate($monformatdate, config::byKey('language'), strtotime($prixlitre['maj']));
                        $SelectionFav[$ordreFav]['distance'] = $dist;
                        $SelectionFav[$ordreFav]['id'] = $mastationid;
                        $SelectionFav[$ordreFav]['coord'] = $lat . "," . $lng;
                        $SelectionFav[$ordreFav]['waze'] = $urlWaze . 'to=ll.' . urlencode($lat . ',' . $lng) . '&from=ll.' . urlencode($malat . ',' . $malng) . '&navigate=yes';
                        $SelectionFav[$ordreFav]['googleMap'] = $urlMap . urlencode($malat . ',' . $malng) . '&destination=' . urlencode($lat . ',' . $lng);
                        $SelectionFav[$ordreFav]['logo'] = $logo;
                      	$SelectionFav[$ordreFav]['fuelFound'] = $prixlitre?true:false;
                    
                    }elseif($prixlitre && $computePrice){// not a favorite but in distance AND if has price found (carburant type founded) AND Date Ok
                      	$maselection[$idx]['adresse'] = $marque . ', ' . $unestation->ville;
                        $maselection[$idx]['adressecompl'] = $unestation->adresse . ", " . $reader->getAttribute('cp') . ' ' . $unestation->ville;
                        $maselection[$idx]['prix'] = $computePrice && $prixlitre?$prixlitre['prix']:0;
                        $maselection[$idx]['maj'] = self::TranslateDate($monformatdate, config::byKey('language'), strtotime($prixlitre['maj']));
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
					self::updateVehiculeCmd($vehiculeId, $i,$lreservoir, $liste[$i - 1]);	
                  
				} else {
                  	if ($i <= $NbFavoris) {
                      	$arr['prix']='';
                      	$arr['logo']=$liste[$i - 1]['logo'];
                      	$arr['adresse'] = $liste[$i - 1]['adresse'];
                        $arr['adressecompl']=$liste[$i - 1]['adressecompl'];
                      
                      	if($liste[$i - 1]['fuelFound']){//type de fuel trouvé dans la station favorite
                          $arr['maj']=__('Obsolète', __FILE__);
                        }else{// type de fuel non référencé dans la statiuon favorite
                          $arr['maj']=__('Type de carburant non vendu en station', __FILE__);
                        }
                    } else {
                      	$arr['adresse'] =__('Plus de station disponible dans le rayon sélectionné', __FILE__);
                        $arr['adressecompl']=__('Plus de station disponible dans le rayon sélectionné', __FILE__);
                      	$arr['prix']='';
                        $arr['logo']='';
                        $arr['maj']='';
                    }
                  	$arrMerged = array_merge(self::DEFAULT_CMD,$arr);
                  	self::updateVehiculeCmd($vehiculeId, $i,$lreservoir,$arrMerged);
				}
			}
			$unvehicule->refreshWidget();
	}
  	/** getPriceFromStationXML : allow to get an array with keys : 'prix' for price corresponding at $typecarburant and 'maj' for update date corresponding
    * $unestation : xml node extracted
    * $typecarburant : the tyep of carburant 
    * return false if carburant not found in the list
    */
  	public static function getPriceFromStationXML($unestation, $typecarburant){
      foreach ($unestation->prix as $prix) {
        if ($prix->attributes()->nom == $typecarburant) { //Filter by fuel type
         
          $prixlitre = $prix->attributes()->valeur . '';
          $maj = $prix->attributes()->maj . '';
           return array('prix'=>$prixlitre, 'maj'=>$maj);
        }
      }
      return false;
    }
  	/** Function updateVehiculeCmd : use to save current top 
    * $vId : eqLogic Id of the current equipement
    * $currTop : integer : current top to be recorded
    * $cmd : array : with all cmd referenced or empty
    */
  	public static function updateVehiculeCmd($vId,$currTop,$lreservoir, $cmdArr){
        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' ID');
        if (is_object($macmd)) $macmd->event($cmdArr['id']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Adresse');
        if (is_object($macmd)) $macmd->event($cmdArr['adresse']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Adresse complète');
        if (is_object($macmd)) $macmd->event($cmdArr['adressecompl']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' MAJ');
        if (is_object($macmd)) $macmd->event($cmdArr['maj']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Prix');
        if (is_object($macmd)) $macmd->event($cmdArr['prix']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Prix Plein');
        if (is_object($macmd)) $macmd->event(round(($cmdArr['prix']?$cmdArr['prix']:0) * $lreservoir, 2));

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Distance');
        if (is_object($macmd)) $macmd->event($cmdArr['distance']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Coord');
        if (is_object($macmd)) $macmd->event($cmdArr['coord']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Waze');
        if (is_object($macmd)) $macmd->event($cmdArr['waze']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Google maps');
        if (is_object($macmd)) $macmd->event($cmdArr['googleMap']);

        $macmd = cmd::byEqLogicIdCmdName($vId, 'Top ' . $currTop . ' Logo');
        if (is_object($macmd)) $macmd->event($cmdArr['logo']);

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
			//log::add(__CLASS__, 'debug', 'JSON file : ' . self::ZIP_PATH . '/data/listestations/stations' . $DepStation . '.json available');
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

	public function preInsert() {
		$this->setConfiguration('templatewidget','default');
		
	}
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
					$prixcarburantsCmd->setSubType('string');
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
					$prixcarburantsCmd->setTemplate('dashboard', 'prixcarburants::logoStation');
					$prixcarburantsCmd->setTemplate('mobile', 'prixcarburants::logoStation');
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

		// Design parameters related to selected display (mobile vs desktop and w/ vs w/o logo)
		$template = $this->getConfiguration('templatewidget');
		$PicSize = 60;
		if($template == 'default') {
			$replace['#TemplateWidth#'] = 400;
			$replace['#opacity#'] = 100;
			$replace['#TextMargin#'] = 80;
			$replace['#logowidth#'] = 80;
		} elseif($template == '0logo') {
			$replace['#TemplateWidth#'] = 180;
			$replace['#opacity#'] = 0;
			$replace['#TextMargin#'] = 10;
			$replace['#logowidth#'] = 0;
		}else{
			return parent::toHtml($_version);
		}
		if ($_version != 'dashboard') {
			$PicSize = $PicSize/2;
			$replace['#TextMargin#'] = $replace['#TextMargin#']/2;
			$replace['#logowidth#'] = $replace['#logowidth#']/2;
		}

		// Fuel station template
		$GazStation_html = '';
		$GazStation_template = getTemplate('core', $version, 'gazstation.template', 'prixcarburants');
		$GazStation_Qtty = 0;

		$EmptyStation = 0;
		for ($i = 1; $i <= 20; $i++) {
			if (is_object($this->getCmd(null, 'TopAdresse_' . $i))) {
				$arr['prix']='';
			  $arr['logo']='';
			  $arr['maj']='';
				$TopAdresse = $this->getCmd(null, 'TopAdresse_' . $i);
				$replace['#TopMarque#'] = is_object($TopAdresse) ? explode(",", $TopAdresse->execCmd())[0] : '';
				$replace['#TopVille#'] = is_object($TopAdresse) ? explode(",", $TopAdresse->execCmd())[1] : '';
				if($replace['#TopMarque#'] == __('Plus de station disponible dans le rayon sélectionné', __FILE__)) $EmptyStation++;

				$FullAdress = $this->getCmd(null, 'TopAdresseCompl_' . $i);
				$replace['#FullAdress#'] = is_object($FullAdress) ? $FullAdress->execCmd() : '';

				$PrixStation = $this->getCmd(null, 'TopPrix_' . $i);
				$replace['#history#'] = '';
				$replace['#history_id#'] = '';
				if(is_object($PrixStation)) {
					$replace['#TopPrix#'] = $PrixStation->execCmd() != '' ? $PrixStation->execCmd() : '';
					if ($PrixStation->getIsHistorized() == 1) {
						$replace['#history_id#'] = $PrixStation->getId();
						$replace['#history#'] = 'history cursor';
					}
				} else {
					$replace['#TopPrix#'] = '';
				}
				

				$MajStation = $this->getCmd(null, 'TopMaJ_' . $i);
				if(is_object($MajStation)) {
					$replace['#TopMaJ#'] = $MajStation->execCmd() != '' ? __('le ', __FILE__) . $MajStation->execCmd() : '';
				} else {
					$replace['#TopMaJ#'] = '';
				}
				

				if ($template == "default") {
					$LogoStation = $this->getCmd(null, 'TopLogo_' . $i);
					$replace['#LogoStation#'] = is_object($LogoStation) ? '<img src="'.$LogoStation->execCmd().'" style="max-width: '.$PicSize.'px; max-height: '.$PicSize.'px;">' : '';
				} else {
					$replace['#LogoStation#'] = ' ';
				}

				if($EmptyStation <= 1) {
					$GazStation_html .= template_replace($replace, $GazStation_template);
					$GazStation_Qtty++;
				}
			}
		}

		$replace['#GazStation#'] = $GazStation_html;
		$replace['#TemplateHeight#'] = 80 * $GazStation_Qtty;

		$replace['#PrixCarburants#'] = $this->getName();

		$refresh = $this->getCmd(null, 'refresh');
		$replace['#refresh#'] = is_object($refresh) ? $refresh->getId() : '';

		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'prixcarburants.template', 'prixcarburants')));
	}


	/*     * ********************* Global functions ************************* */

	/** Function to calculate a distance between selected location and a station */
	public static function distance($lat1, $lng1, $lat2, $lng2, $unit = 'k')
	{
		$earth_radius = 6378137;	// Terre = sphère de 6378km de rayon
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
		if(empty($a['prix']) || $a['prix'] == 0  )return true;
		if(empty($b['prix']) || $b['prix'] == 0 )return false;
		return $a['prix'] > $b['prix'];
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

	/** Function to display French dates */
	public static function TranslateDate($DateFormat, $language, $timestamp = null)
	{
		if ($timestamp === null) $timestamp = time();
		$date = date($DateFormat, $timestamp);
		
		switch($language) {
			case 'en_US':
				//nothing
				break;
			case 'fr_FR':
				$date = str_replace(
					array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'Feb', 'Apr', 'May', 'Jun', 'Jul', 'Aug'),
					array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Decembre', 'Fev', 'Avr', 'Mai', 'Juin', 'Juill', 'Août'),
					$date
				);
				break;
			case 'de_DE':
				// TBD
				break;
			case 'es_ES':
				// TBD
				break;
		}
		
		return $date;
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