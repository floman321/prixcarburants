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
require_once dirname(__FILE__) . '/../core/class/prixcarburants.class.php';

function prixcarburants_checkCron() {
  // update du cron ocazou
  $freq = config::byKey('freq', 'prixcarburants');
  $perso = config::byKey('autorefresh', 'prixcarburants');
  log::add('prixcarburants', 'debug', 'la valeur de config est : ' . config::byKey('freq', 'prixcarburants'));
  if (($freq == null || $freq == '') || ($freq == 'prog' && ($perso == null || $perso == ''))) {
    config::save('freq', prixcarburants::DEFAULT_CRON, 'prixcarburants'); //dayly
  }
  if (config::byKey('freq_geo', 'prixcarburants') == null) {
    config::save('freq_geo', 'event', 'prixcarburants');
  }
  prixcarburants::setUpdateCron(); // mise en place du cron selon la config ou par défaut
}

function prixcarburants_install() {
  log::add('prixcarburants', 'debug', '======= installation');
  prixcarburants::updatePrixCarburant();
  prixcarburants_checkCron();
}

function prixcarburants_update() {
   log::add('prixcarburants', 'debug', '!!======= mise à jour plugin ');
  	log::add('prixcarburants', 'debug', 'Update Price List ');
  	prixcarburants::updatePrixCarburant();
  
  $plugin = plugin::byId('prixcarburants');
  foreach (eqLogic::byType($plugin->getId()) as $eqLogic) {
   log::add('prixcarburants', 'debug', 'update eqL : '.$eqLogic->getHumanName());
	

    // geoloc parameter to fit previous config
    $geoloc = $eqLogic->getConfiguration('geoloc', null);
    log::add('prixcarburants', 'debug', 'current geoloc :' . $geoloc);

    if ($geoloc == 'jeedom') { // si jeedom 
      log::add('prixcarburants', 'debug', 'ajout de la localisation jeedom ');
      $eqLogic->setConfiguration('jeedom_loc', true);
      $eqLogic->setConfiguration('geoloc', null);
      $eqLogic->setConfiguration('auto_update', false);
      
    } elseif (is_numeric($geoloc) && !is_null($geoloc)) { // si cmd id
      $eqLogic->setConfiguration('jeedom_loc', false);
      $locationcmd = cmd::byEqLogicIdAndLogicalId($geoloc, 'location:coordinate');
      if (!is_object($locationcmd)) {
        log::add('prixcarburants', 'debug', 'essai commande localisation par ID');
        $locationcmd = cmd::byId($geoloc);
      }
      if (is_object($locationcmd)) {
        log::add('prixcarburants', 'debug', 'transformation de la commande localisation : ' . $locationcmd->getHumanName());
        $eqLogic->setConfiguration('geoloc', "#" . $locationcmd->getId() . "#");
        $eqLogic->setConfiguration('auto_update', false);
        
      } else {
        log::add('prixcarburants', 'debug', 'commande localisation non trouvée');
      }
    }

    // test config  templatewidget
    if($eqLogic->getConfiguration('templatewidget'))$eqLogic->setConfiguration('templatewidget', 'logos');
    //save eqLogic
    $eqLogic->save();
  }
  
  // check cron 
  prixcarburants_checkCron();
  log::add('prixcarburants', 'debug', '=============  fin de mise à jour');

  //Remove unused files
  log::add('prixcarburants','debug','File to be removed, real path : '.realpath(getRootPath()."/plugins/prixcarburants/core/class/listestations"));
  if (file_exists(getRootPath()."/plugins/prixcarburants/core/class/stations.json")) rrmdir(getRootPath()."/plugins/prixcarburants/core/class/stations.json");
  if (file_exists(getRootPath()."/plugins/prixcarburants/core/class/PrixCarburants_instantane.xml")) rrmdir(getRootPath()."/plugins/prixcarburants/core/class/PrixCarburants_instantane.xml");
  if (file_exists(getRootPath()."/plugins/prixcarburants/core/class/listestations")) rrmdir(getRootPath()."/plugins/prixcarburants/core/class/listestations");

}


function prixcarburants_remove() {
  // remove of cron job
  $cron = cron::byClassAndFunction('prixcarburants', 'udpateAllData');
  if (is_object($cron)) {
    $cron->remove();
  }
  $cron = cron::byClassAndFunction('prixcarburants', 'pullGeoCmd');
  if (is_object($cron)) {
    $cron->remove();
  }
}