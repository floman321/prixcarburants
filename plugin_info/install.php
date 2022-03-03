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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function prixcarburants_install() {
  log::add('prixcarburants','debug', '======= installation de '.$eqLogic->getHumanName());
  
}

function prixcarburants_update() {
  $plugin = plugin::byId('prixcarburants');
  foreach (eqLogic::byType($plugin->getId()) as $eqLogic) {
      log::add('prixcarburants','debug', '!!======= mise à jour de '.$eqLogic->getHumanName());
      
      
      // geoloc parameter to fit previous config
      $geoloc = $eqLogic->getConfiguration('geoloc', 'none');
      log::add('prixcarburants','debug', 'current geoloc :'.$geoloc);

  		if($geoloc == 'jeedom'){// si jeedom 
        	$eqLogic->setConfiguration('jeedom_loc', true);
          $eqLogic->setConfiguration('geoloc', null);
          $eqLogic->setConfiguration('auto_update', false);
          log::add('prixcarburants','debug', 'ajout de la localisation jeedom ');
          
        }elseif(is_numeric($geoloc) && !is_null($geoloc)){// si cmd id
          $eqLogic->setConfiguration('jeedom_loc', false);
          $locationcmd = cmd::byEqLogicIdAndLogicalId($geoloc, 'location:coordinate');
          if(!is_object($locationcmd)){
            log::add('prixcarburants','debug', 'essai commande localisation par ID');
            $locationcmd = cmd::byId($geoloc);
          }
          if(is_object($locationcmd)){
           $eqLogic->setConfiguration('geoloc', "#". $locationcmd->getId()."#");
           $eqLogic->setConfiguration('auto_update', false);
            log::add('prixcarburants','debug', 'transformation de la commande localisation : '.$locationcmd->getHumanName());
          }else{
            log::add('prixcarburants','debug', 'commande localisation non trouvée');
          }
          
        }
		$eqLogic->save();
 }
 log::add('prixcarburants','debug','=============  fin de mise à jour');

}


function template_remove() {
    
}

?>
