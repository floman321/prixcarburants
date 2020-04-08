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


    
     //* Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {
			
     
       
      }
     

    
     //* Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

        $filenamezip = __DIR__.'/PrixCarburants.zip';

       	$current = file_get_contents("https://donnees.roulez-eco.fr/opendata/instantane");
        file_put_contents($filenamezip, $current);
        
        $zip = new ZipArchive;
        if ($zip->open($filenamezip) === TRUE) {
            $zip->extractTo(__DIR__);
            $zip->close();
          	log::add('prixcarburants','debug','prix zip ok get'.__DIR__);
          
            unlink(__DIR__.'/PrixCarburants.zip');

            $doc = new DOMDocument;
            $reader = XMLReader::open(__DIR__.'/PrixCarburants_instantane.xml');

            $stack = array();
           	$malat = 44.853026;
            $malng = -0.294088;

            while($reader->read()) {
              if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'pdv') {

                $lat = $reader->getAttribute('latitude')/100000;
                $lng = $reader->getAttribute('longitude')/100000;
                $dist = prixcarburants::distance($malat,$malng,$lat,$lng);
                
                if ($dist < 15) {
                  
                  log::add('prixcarburants','debug',' '.$dist.' '.$node->ville);
                  //Détails
                  $node = simplexml_import_dom($doc->importNode($reader->expand(), true));
                  $node->distance = $dist;
                  array_push($stack, $node);
                }
              }
            }
            $reader->close();
          
          function custom_sort($a,$b) {
          	return $a->distance>$b->distance;
    	}
          
          	usort($stack, "custom_sort");

             log::add('prixcarburants','debug',' count '.$stack[0]->ville);

        } else {
            log::add('prixcarburants','debug','prix zip nok get'.__DIR__);
        }
        
       
        
        }
                    


    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
        
    }

    public function postSave() {
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        
    }

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }

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
        
    }

    /*     * **********************Getteur Setteur*************************** */
}