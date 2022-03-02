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

class prixcarburants extends eqLogic {
	/*     * ***********************Methode static*************************** */
    
    //Function to calculate a distance between selected location and a station
	public static function distance($lat1, $lng1, $lat2, $lng2, $unit = 'k') {
		$earth_radius = 6378137;   // Terre = sphère de 6378km de rayon
		$rlo1 = deg2rad($lng1);
		$rla1 = deg2rad($lat1);
		$rlo2 = deg2rad($lng2);
		$rla2 = deg2rad($lat2);
		$dlo = ($rlo2 - $rlo1) / 2;
		$dla = ($rla2 - $rla1) / 2;
		$a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
		$d = 2 * atan2(sqrt($a), sqrt(1 - $a));
		return round(($earth_radius * $d)/1000);
	}

	public static function custom_sort($a,$b) {
	   return $a['prix']>$b['prix'];
	}
    
	//Function to get The brand of a gaz station
	public static function getMarqueStation($idstation, $DepStation) {
	    $json = @file_get_contents(__DIR__ . '/listestations/stations' . $DepStation . '.json');
	    if($json!==false){
    		$parsed_json = json_decode($json, true);
    		foreach($parsed_json['stations'] as $row) {
    			if($row['id'] == $idstation) {
    				return $row['marque'];
    				break;
    			}
    		}
	    } else {
	        log::add('prixcarburants','debug','JSON file : /listestations/stations' . $DepStation . '.json not available');
	        return __('Erreur',  __FILE__);
	    }
	}

	//Function list all gaz station that correspond to defined parameters on the configuration
	public static function MAJVehicules($oneveh) {
		
		if ($oneveh != null){
			log::add('prixcarburants','debug','Update only one vehicle');
			$Vehicules = array($oneveh);
		}else{
			log::add('prixcarburants','debug','Update all vehicle'); 
			$Vehicules = self::byType('prixcarburants');
		}
		
		foreach ($Vehicules as $unvehicule) {
			if ($unvehicule->getIsEnable() == 0) continue;
			
			$maselection = array();
			$SelectionFav = array();
			$idx = 0;
			
			$nom = $unvehicule->getName();
			$typecarburant = $unvehicule->getConfiguration('typecarburant','');
			if($typecarburant == '') log::add('prixcarburants','error',__('Le type de carburant n\'est pas renseigné dans la configuration de Prix Carburants : ',  __FILE__).$nom);
			$rayon = $unvehicule->getConfiguration('rayon','30');
			$nbstation = $unvehicule->getConfiguration('nbstation','0');
			$monformatdate = $unvehicule->getConfiguration('formatdate','');
			//Get the list of favoris selected
			$NbFavoris = 0;
			if ($unvehicule->getConfiguration('Favoris') == '1') {
    			for($i = 1; $i <=10; $i++) {
    			    if($unvehicule->getConfiguration('station' . $i . '_Dep','') != '' && $unvehicule->getConfiguration('station' . $i . '_Station','') != '') {
    			        $StationFav[$i] = $unvehicule->getConfiguration('station' . $i . '_Station','');
    			        $DepartementFav[$i] = $unvehicule->getConfiguration('station' . $i . '_Dep','');
    			        $NbFavoris++;
    			    } else {
    			        break;
    			    }
    			}
    			if($NbFavoris == 0) log::add('prixcarburants','error',__('Aucun favoris n\'est sélectionné dans la configuration de Prix Carburants : ',  __FILE__).$nom);
			}
			
			//Get position latitude and longitude only if geolocalisation is selected
			if ($unvehicule->getConfiguration('ViaLoca') == '1') {
			    if($nbstation == '0') {
			        log::add('prixcarburants','error',__('Le nombre de station n\'est pas renseigné dans la configuration de Prix Carburants : ',  __FILE__).$nom);
			    } else {
        			if ($unvehicule->getConfiguration('geoloc', 'none') == 'none') {
        			    $macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top 1 Adresse');
        			    if (is_object($macmd)) $macmd->event(__('Pas de localisation sélectionnée',  __FILE__));
        			    return;
        			} elseif ($unvehicule->getConfiguration('geoloc') == "jeedom") {
        			    $malat = config::byKey('info::latitude');
        			    $malng = config::byKey('info::longitude');
        			} else {
        			    if (geotravCmd::byEqLogicIdAndLogicalId($unvehicule->getConfiguration('geoloc'),'location:coordinate') != null){
                            $coordonnees = geotravCmd::byEqLogicIdAndLogicalId($unvehicule->getConfiguration('geoloc'),'location:coordinate')->execCmd();
        			    } else {
        			        $coordonnees = cmd::byId($unvehicule->getConfiguration('geoloc'))->execCmd();
        			    }
        			    $expcoord = explode(",",$coordonnees);
        		        $malat = $expcoord[0];
        		        $malng = $expcoord[1];
        			}
			    }
			}
			
			//Prepare and parse XML file
			$reader = XMLReader::open(__DIR__.'/PrixCarburants_instantane.xml');
			$doc = new DOMDocument;
			while($reader->read()) {
				if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'pdv') {
					$lat = $reader->getAttribute('latitude')/100000;
					$lng = $reader->getAttribute('longitude')/100000;
					$mastationid = $reader->getAttribute('id');
					$MaStationDep = intval(substr($reader->getAttribute('cp'), 0, 2));
					$dist = 0;
					$MonTest = False;
					$EstFavoris = False;
                  
                  	$dist = prixcarburants::distance($malat,$malng,$lat,$lng);
					
					//Check if this station is a favorite
					if($NbFavoris > 0) {
					    for($i = 1; $i <= $NbFavoris; $i++) {
					        if($mastationid == $StationFav[$i] && $MaStationDep == $DepartementFav[$i]) {
					            $MonTest = True;
					            $EstFavoris = True;
					            $ordreFav = $i - 1;
					            break;
					        }
					    }
					}
					if($MonTest == False) {
    					if($unvehicule->getConfiguration('ViaLoca') == '1'){
    						if($dist <= $rayon) $MonTest = True;
    					}
					}
					
					//Register only station that are a favorite or on max radius
					if($MonTest == False) continue;
                  
                  	$should_ignore = $unvehicule->getConfiguration('dateexpirevisible');
                  	$daysminus = $unvehicule->getConfiguration('dateexpire');
                  	$dminus5 = strtotime("-".$daysminus." days");
					
					//Import and review XML file
					$unestation = simplexml_import_dom($doc->importNode($reader->expand(), true));
					foreach ($unestation->prix as $prix) {
						if ($prix->attributes()->nom == $typecarburant){ //Filter by fuel type
                          
							$prixlitre = $prix->attributes()->valeur.'';
							$maj = $prix->attributes()->maj.'';
							$marque = prixcarburants::getMarqueStation($mastationid, $MaStationDep);
                          	
                          	$style = '';
                          	if ($dminus5 >= strtotime($maj)) {
                              if ($should_ignore) continue;
                              $style = "<div style='color:red;'>";
                            }
							
							if($EstFavoris) { //Register favorite station
							    $SelectionFav[$ordreFav]['adresse'] = $marque.', '.$unestation->ville;
							    $SelectionFav[$ordreFav]['prix'] = $prixlitre;
							    $SelectionFav[$ordreFav]['maj']  = date($monformatdate, strtotime($maj));
							    $SelectionFav[$ordreFav]['distance'] = $dist;
							    $SelectionFav[$ordreFav]['id'] = $mastationid;
							} else { //Register station that are one the radius
							    $maselection[$idx]['adresse'] = $marque.', '.$unestation->ville;
							    $maselection[$idx]['prix'] = $prixlitre;
							    $maselection[$idx]['maj']  = date($monformatdate, strtotime($maj));
                              	if ($dminus5 >= strtotime($maj)) $maselection[$idx]['maj'] = "<div style='color:red;'>".$maselection[$idx]['maj'].'</div>';
                              
							    $maselection[$idx]['distance'] = $dist;
							    $maselection[$idx]['id'] = $mastationid;
                              
                              	if ($style != '') $maselection[$idx]['maj'] = $style . $maselection[$idx]['maj'] . '</div>';
                              	
							    $idx++;
							}
                          
                          
                          
						}
					}
				}
			}
			$reader->close();

			log::add('prixcarburants','debug','Step count selection: '.count($maselection).' for equipement: '.$nom);
			
			//Sort by price, if needed (for favorite)
			usort($maselection, "prixcarburants::custom_sort");
			if($unvehicule->getConfiguration('OrdreFavoris','Ordre') == "Prix") usort($SelectionFav, "prixcarburants::custom_sort");
          
          	$lreservoir = $unvehicule->getConfiguration('reservoirlitre', 0);
			
			//Register favorites then require quantity of station from localisation
			$nbstation = $NbFavoris + $nbstation;
			For($i = 1; $i <= $nbstation; $i++) {
			    if($i <= $NbFavoris) {
			        $liste[$i - 1] = $SelectionFav[$i - 1];
			    } else {
			        $liste[$i - 1] = $maselection[$i - 1 - $NbFavoris];
			    }
			    if($liste[$i - 1]['prix'] != ''){ //Price available so, there is enough station on selected range
    				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' ID');
    				if (is_object($macmd)) $macmd->event($liste[$i - 1]['id']);
    				
    				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' Adresse');
    				if (is_object($macmd)) $macmd->event($liste[$i - 1]['adresse']);
    				
    				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' MAJ');
    				if (is_object($macmd)) $macmd->event($liste[$i - 1]['maj']);
    				
    				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' Prix');
    				if (is_object($macmd)) $macmd->event($liste[$i - 1]['prix']);
                  
                  	$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i.' Prix Plein');
                    if (is_object($macmd)) $macmd->event(round($liste[$i - 1]['prix'] * $lreservoir,2));

                    $macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i.' Distance');
                    if (is_object($macmd)) $macmd->event($liste[$i - 1]['distance']);

                  
			    } else {
			        $macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' Adresse');
			        if (is_object($macmd)) {
						if($i <= $NbFavoris) {
							$macmd->event(__('Favori pas correctement configuré',  __FILE__));
						} else {
							$macmd->event(__('Plus de station disponible dans le rayon sélectionné',  __FILE__));
						}
					}
    				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' ID');
    				if (is_object($macmd)) $macmd->event('');
    				
    				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' MAJ');
    				if (is_object($macmd)) $macmd->event('');
    				
    				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' Prix');
    				if (is_object($macmd)) $macmd->event('');
                  
                  	$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i.' Distance');
                    if (is_object($macmd)) $macmd->event('');

                    $macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i.' Prix Plein');
                    if (is_object($macmd)) $macmd->event('');
                  
			    }
			}
			$unvehicule->refreshWidget();
		}
	}
	
	//Function to get zip file from government website then update all values
	Public static function updatePrixCarburant(){
		$filenamezip = __DIR__.'/PrixCarburants.zip';
		
		$current = file_get_contents("https://donnees.roulez-eco.fr/opendata/instantane");
		file_put_contents($filenamezip, $current);
		
		$zip = new ZipArchive;
		if ($zip->open($filenamezip) === TRUE) {
    		$zip->extractTo(__DIR__);
    		$zip->close();
    		log::add('prixcarburants','debug','prix zip ok get'.__DIR__);
    		
    		unlink(__DIR__.'/PrixCarburants.zip');
    		prixcarburants::MAJVehicules(null);
		} else {
		  log::add('prixcarburants','debug','prix zip nok get'.__DIR__);
		}
	}
	
	
	//* Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDaily() {
		// Call command 'refresh', to update values
		foreach (self::byType('prixcarburants') as $prixcarburants) {
			if ($prixcarburants->getIsEnable() == 1) {
				$cmd = $prixcarburants->getCmd(null, 'refresh');
				if (!is_object($cmd)) {
					continue;
				}
				$cmd->execCmd();
			}
		}
	}



	/*     * *********************Méthodes d'instance************************* */

	public function postSave() {
		//Create refresh commande
		$prixcarburantsCmd = $this->getCmd(null, 'refresh');
		if (!is_object($prixcarburantsCmd)) {
			log::add('prixcarburants', 'debug', 'refresh');
			$prixcarburantsCmd = new prixcarburantsCmd();
			$prixcarburantsCmd->setName(__('Actualiser',  __FILE__));
			$prixcarburantsCmd->setEqLogic_id($this->getId());
			$prixcarburantsCmd->setLogicalId('refresh');
			$prixcarburantsCmd->setType('action');
			$prixcarburantsCmd->setSubType('other');
			$prixcarburantsCmd->setIsHistorized(0);
			$prixcarburantsCmd->setIsVisible(1);
			$prixcarburantsCmd->setOrder(0);
			$prixcarburantsCmd->save();
		}
		
		//Create file with fuel price if it doesn't exist (first creation)
		if (!file_exists(__DIR__.'/PrixCarburants_instantane.xml')) {
		  $prixcarburantsCmd->getEqLogic()->updatePrixCarburant();
		}
	}

    // Create corrrectly the good quantity of command on an equipement
	public function postUpdate() {
		//Choose correct quantity of station.
	    $nbstation = $this->getConfiguration('nbstation','0');
	    $NbFavoris = 0;
	    if ($this->getConfiguration('Favoris') == '1') {
	        for($i = 1; $i <=10; $i++) {
	            if($this->getConfiguration('station' . $i . '_Dep','') != '' && $this->getConfiguration('station' . $i . '_Station','') != '') {
	                $StationFav[$i] = $this->getConfiguration('station' . $i . '_Station','');
	                $DepartementFav[$i] = $this->getConfiguration('station' . $i . '_Dep','');
	                $NbFavoris++;
	            } else {
	                break;
	            }
	        }
	    }
	    $nbstation = $nbstation + $NbFavoris;
		
		$OrdreAffichage = 1;
		
		For($i = 1; $i <= 20; $i++) {
		    //Show only required quantity of station
			if($i <= $nbstation) {
				$prixcarburantsCmd = $this->getCmd(null, 'TopID_'.$i);
				if (!is_object($prixcarburantsCmd)) $prixcarburantsCmd = new prixcarburantsCmd();
				$prixcarburantsCmd->setName('Top ' . $i . ' ID');
				$prixcarburantsCmd->setEqLogic_id($this->getId());
				$prixcarburantsCmd->setLogicalId('TopID_'.$i);
				$prixcarburantsCmd->setType('info');
				$prixcarburantsCmd->setSubType('string');
				$prixcarburantsCmd->setIsHistorized(0);
				$prixcarburantsCmd->setIsVisible(0);
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;
				
				$prixcarburantsCmd = $this->getCmd(null, 'TopAdresse_'.$i);
				if (!is_object($prixcarburantsCmd)) $prixcarburantsCmd = new prixcarburantsCmd();
				$prixcarburantsCmd->setName('Top ' . $i . ' Adresse');
				$prixcarburantsCmd->setEqLogic_id($this->getId());
				$prixcarburantsCmd->setLogicalId('TopAdresse_'.$i);
				$prixcarburantsCmd->setType('info');
				$prixcarburantsCmd->setSubType('string');
				$prixcarburantsCmd->setIsHistorized(0);
				$prixcarburantsCmd->setIsVisible(1);
				$prixcarburantsCmd->setDisplay('showNameOndashboard',0);
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;
				
				$prixcarburantsCmd = $this->getCmd(null, 'TopMaJ_'.$i);
				if (!is_object($prixcarburantsCmd)) $prixcarburantsCmd = new prixcarburantsCmd();
				$prixcarburantsCmd->setName('Top ' . $i . ' MAJ');
				$prixcarburantsCmd->setEqLogic_id($this->getId());
				$prixcarburantsCmd->setLogicalId('TopMaJ_'.$i);
				$prixcarburantsCmd->setType('info');
				$prixcarburantsCmd->setSubType('string');
				$prixcarburantsCmd->setIsHistorized(0);
				$prixcarburantsCmd->setDisplay('showNameOndashboard',0);
				$prixcarburantsCmd->setIsVisible(1);
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;
				
				$prixcarburantsCmd = $this->getCmd(null, 'TopPrix_'.$i);
				if (!is_object($prixcarburantsCmd)) $prixcarburantsCmd = new prixcarburantsCmd();
				$prixcarburantsCmd->setName('Top ' . $i . ' Prix');
				$prixcarburantsCmd->setEqLogic_id($this->getId());
				$prixcarburantsCmd->setLogicalId('TopPrix_'.$i);
				$prixcarburantsCmd->setType('info');
				$prixcarburantsCmd->setSubType('numeric');
				$prixcarburantsCmd->setIsHistorized(0);
				$prixcarburantsCmd->setIsVisible(1);
				$prixcarburantsCmd->setUnite('€/L');
				$prixcarburantsCmd->setDisplay('showNameOndashboard',0);
				$prixcarburantsCmd->setTemplate('dashboard','badge');
				$prixcarburantsCmd->setTemplate('mobile','badge');
				$prixcarburantsCmd->setOrder($OrdreAffichage);
				$prixcarburantsCmd->save();
				$OrdreAffichage++;
              
              	$prixcarburantsCmd = $this->getCmd(null, 'PrixPlein_'.$i);
                if (!is_object($prixcarburantsCmd)) $prixcarburantsCmd = new prixcarburantsCmd();
                $prixcarburantsCmd->setName('Top ' . $i.' Prix Plein');
                $prixcarburantsCmd->setEqLogic_id($this->getId());
                $prixcarburantsCmd->setLogicalId('PrixPlein_'.$i);
                $prixcarburantsCmd->setType('info');
                $prixcarburantsCmd->setSubType('numeric');
                $prixcarburantsCmd->setIsHistorized(0);
                $prixcarburantsCmd->setIsVisible(0);
                $prixcarburantsCmd->setUnite('€');
                $prixcarburantsCmd->setDisplay('showNameOndashboard',0);
                $prixcarburantsCmd->setTemplate('dashboard','badge');
                $prixcarburantsCmd->setTemplate('mobile','badge');
                $prixcarburantsCmd->setOrder($OrdreAffichage);
                $prixcarburantsCmd->save();
                $OrdreAffichage++;

                $prixcarburantsCmd = $this->getCmd(null, 'Distance_'.$i);
                if (!is_object($prixcarburantsCmd)) $prixcarburantsCmd = new prixcarburantsCmd();
                $prixcarburantsCmd->setName('Top ' . $i.' Distance');
                $prixcarburantsCmd->setEqLogic_id($this->getId());
                $prixcarburantsCmd->setLogicalId('Distance_'.$i);
                $prixcarburantsCmd->setType('info');
                $prixcarburantsCmd->setSubType('numeric');
                $prixcarburantsCmd->setIsHistorized(0);
                $prixcarburantsCmd->setIsVisible(0);
                $prixcarburantsCmd->setUnite('Km');
                $prixcarburantsCmd->setDisplay('showNameOndashboard',0);
                $prixcarburantsCmd->setTemplate('dashboard','badge');
                $prixcarburantsCmd->setTemplate('mobile','badge');
                $prixcarburantsCmd->setOrder($OrdreAffichage);
                $prixcarburantsCmd->save();
                $OrdreAffichage++;
              
              
			} else {
			    //Remove all station to avoid having too much station when favorite is selected
			    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' ID');
			    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
			    
			    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' Adresse');
			    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
			    
			    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' MAJ');
			    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
			    
			    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' Prix');
			    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
              
              	$prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i.' Prix Plein');
                if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();

                $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i.' Distance');
                if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
			}
		}
		
		prixcarburants::MAJVehicules($this);
	}

	public function preRemove() {}

	public function postRemove() {}
}

class prixcarburantsCmd extends cmd {

	public function execute($_options = array()) {
		// If 'click' on 'refresh' command
		if ($this->getLogicalId() == 'refresh') {
			log::add('prixcarburants','debug','Call "refresh" command for this object by ' . $this->getHumanName());
			$this->getEqLogic()->updatePrixCarburant();
		}
		return true;
	}
}
