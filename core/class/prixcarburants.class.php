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
	/*     * *************************Attributs****************************** */
	


	/*     * ***********************Methode static*************************** */

	/*
	* Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {

	}
	*/


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

	public static function getMarqueStation($idstation) {
		$json = file_get_contents(__DIR__."/stations.json");
		$parsed_json = json_decode($json, true);
		foreach($parsed_json as $row) {
			if($row['id'] == $idstation) {
				return $row['marque'];
				break;
			}
		}
	}

	public static function MAJVehicules($oneveh) {
		
		if ($oneveh != null){
			log::add('prixcarburants','debug',' onlyoneveh ');
			$Vehicules = array($oneveh);
		}else{
			log::add('prixcarburants','debug',' allveh '); 
			$Vehicules = self::byType('prixcarburants');
		}
		
		foreach ($Vehicules as $unvehicule) {
			if ($unvehicule->getIsEnable() == 0) continue;
			
			$reader = XMLReader::open(__DIR__.'/PrixCarburants_instantane.xml');
			$doc = new DOMDocument;
			$maselection = array();
			$idx = 0;
			
			$nom = $unvehicule->getName();
			$typecarburant = $unvehicule->getConfiguration('typecarburant','');
			$rayon = $unvehicule->getConfiguration('rayon','30');
			$malat = $unvehicule->getConfiguration('latitude','46.492794');
			$malng = $unvehicule->getConfiguration('longitude','2.601271');
			$station1 = $unvehicule->getConfiguration('station1','');
			$monformatdate = $unvehicule->getConfiguration('formatdate','');
			$nbstation = $unvehicule->getConfiguration('nbstation','3');
			
			
			while($reader->read()) {
				if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'pdv') {
					$lat = $reader->getAttribute('latitude')/100000;
					$lng = $reader->getAttribute('longitude')/100000;
					$mastationid = $reader->getAttribute('id');
					$dist = 0;
					
					if ($station1 == ''){
						$dist = prixcarburants::distance($malat,$malng,$lat,$lng);
						if ($dist > $rayon) continue; //On enregistrer uniquement ceux qui sont a proximitées
					}
					
					$unestation = simplexml_import_dom($doc->importNode($reader->expand(), true));
					
					foreach ($unestation->prix as $prix){
						if ($prix->attributes()->nom == $typecarburant){ // FILTRER SELON CARBURANTS
						
							if ($station1 != '') {
								if ($mastationid != $station1) continue; 
							}
							
							$prixlitre = $prix->attributes()->valeur.'';
							$maj = $prix->attributes()->maj.'';
							$marque = prixcarburants::getMarqueStation($mastationid);
							
							$maselection[$idx]['adresse'] = $marque.', '.$unestation->ville;
							$maselection[$idx]['prix'] = $prixlitre;
							$maselection[$idx]['maj']  = date($monformatdate, strtotime($maj));
							$maselection[$idx]['distance'] = $dist;
							$maselection[$idx]['id'] = $mastationid;
							$idx++;
						}
					}
				}//PDV
			}// XML
			$reader->close();

			log::add('prixcarburants','debug',' step count selection '.count($maselection).' '.$nom);

			if ($station1 == '') {
				
				usort($maselection, "prixcarburants::custom_sort");
				
				For($i = 1; $i <= $nbstation; $i++) {
					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' ID');
					if (is_object($macmd)) $macmd->event($maselection[$i - 1]['id']);
					
					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' Adresse');
					if (is_object($macmd)) $macmd->event($maselection[$i - 1]['adresse']);
					
					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' MAJ');
					if (is_object($macmd)) $macmd->event($maselection[$i - 1]['maj']);
					
					$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top ' . $i . ' Prix');
					if (is_object($macmd)) $macmd->event($maselection[$i - 1]['prix']);
				}
			}else{ // enmode station favoris
				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top 1 ID');
				if (is_object($macmd)) $macmd->event($maselection[0]['id']);
				
				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top 1 Adresse');
				if (is_object($macmd)) $macmd->event($maselection[0]['adresse']);
				
				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top 1 MAJ');
				if (is_object($macmd)) $macmd->event($maselection[0]['maj']);
				
				$macmd = cmd::byEqLogicIdCmdName($unvehicule->getId(),'Top 1 Prix');
				if (is_object($macmd)) $macmd->event($maselection[0]['prix']);
			}
			
			$unvehicule->refreshWidget();
		}

	}
	
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
	
	/*
	//* Fonction exécutée automatiquement toutes les heures par Jeedom
	public static function cronHourly() {}
	*/
	
	
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

	public function preInsert() {}

	public function postInsert() {}

	public function preSave() {}

	public function postSave() {
		//prixcarburants::MAJVehicules($this);
		
		//Ajout de la commande pour rafraichir les données
		$PrixCarburantsCmd = $this->getCmd(null, 'refresh');
		if (!is_object($PrixCarburantsCmd)) {
			log::add('prixcarburants', 'debug', 'refresh');
			$PrixCarburantsCmd = new prixcarburantsCmd();
			$PrixCarburantsCmd->setName('{{Rafraichir}}');
			$PrixCarburantsCmd->setEqLogic_id($this->getId());
			$PrixCarburantsCmd->setLogicalId('refresh');
			$PrixCarburantsCmd->setType('action');
			$PrixCarburantsCmd->setSubType('other');
			$PrixCarburantsCmd->save();
		}
	}

	public function preUpdate() {}

	public function postUpdate() {
		//Choose correct quantity of station. Only 1 if there is a favorite selected
		$FavorisMode = $this->getConfiguration('station1','');
		if($FavorisMode == ''){
		    $nbstation = $this->getConfiguration('nbstation','3');
		} else {
		    $nbstation = 1;
		}
		
		$OrdreAffichage = 1;
		
		For($i = 1; $i <= 10; $i++) {
		    //Remove all station to avoid having too much station when favorite is selected
		    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' ID');
		    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
		    
		    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' Adresse');
		    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
		    
		    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' MAJ');
		    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
		    
		    $prixcarburantsCmd = cmd::byEqLogicIdCmdName($this->getId(),'Top ' . $i . ' Prix');
		    if (is_object($prixcarburantsCmd)) $prixcarburantsCmd->remove();
		    
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
			}
		}
		
		prixcarburants::MAJVehicules($this);
	}

	public function preRemove() {}

	public function postRemove() {}

	/*
	* Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	public function toHtml($_version = 'dashboard') {

	}
	*/

	/*
	* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
	public static function postConfig_<Variable>() {
	}
	*/

	/*
	* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
	public static function preConfig_<Variable>() {
	}
	*/

	/*     * **********************Getteur Setteur*************************** */
}

class prixcarburantsCmd extends cmd {
	/*     * *************************Attributs****************************** */


	/*     * ***********************Methode static*************************** */


	/*     * *********************Methode d'instance************************* */

	/*
	* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
	return true;
	}
	*/

	public function execute($_options = array()) {
		// If 'click' on 'refresh' command
		if ($this->getLogicalId() == 'refresh') {
			log::add('prixcarburants','debug','Call "refresh" command for this object by ' . $this->getHumanName());
			$this->getEqLogic()->updatePrixCarburant();
		}
		return true;
	}

	/*     * **********************Getteur Setteur*************************** */
}