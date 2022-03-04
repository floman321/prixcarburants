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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
  <form class="form-horizontal">
  <fieldset>
     
      <div class="form-group mgh-actu-type">
              <label class="col-md-4 control-label">{{Fréquence de mise à jour des prix}}
              </label>
              <div class="col-sm-3">
                  <select id="sel_object" class="configKey form-control" data-l1key="freq">
                    <option value="prog">{{Programmé}}</option>
                    <!-- <option value="* * * * *">{{Cron minute}}</option>
                    <option value="*/10 * * * *">{{Cron 10 minutes}}</option> -->
                    <option value="*/30 * * * *">{{Cron 30 minutes}}</option>
                    <option value="7 * * * *">{{Cron heure}}</option>
                    <option value="7 */6 * * *">{{Cron 6 heure}}</option>
                    <option value="7 */12 * * *">{{Cron 12 heure}}</option>
                    <option value="7 2 * * *">{{Cron Jour}}</option>
                  </select>
               </div>
        </div>
		<div class="form-group mgh-actu-auto">
                <label class="col-md-4 control-label">{{Auto-actualisation}}
				</label>
              <div class="col-sm-3">
                <div class="input-group">
                  <input type="text" class="configKey form-control roundedLeft" data-l1key="autorefresh" placeholder="{{Cliquer sur ? pour afficher l'assistant cron}}"/>
                  <span class="input-group-btn">
                    <a class="btn btn-default cursor jeeHelper roundedRight" data-helper="cron" title="Assistant cron">
                    	<i class="fas fa-question-circle"></i>
                    </a>
                  </span>
                </div>
              </div>
        </div>
        <?php
        $dates = prixcarburants::getDueDate();
          echo '<div class="form-group dueDateShow"  >
              <label class="col-md-4 control-label">{{dates de mise à jour :}}</label>
              <div class="col-xs-7">
                  <label class="control-label">{{Précédent : }}</label>
                  
                  <span class="configInfo label label-primary" data-key="prevDate">'.$dates['prevDate'].'</span>
                  <label class="control-label">{{Prochain : }}</label>
                  <span class="configInfo label label-success" data-key="nextDate">'.$dates['nextDate'].'</span>
               </div>
        </div>'
          ?>
   
  </fieldset>
</form>
<?php include_file('desktop', 'prixcarburants_conf', 'js', 'prixcarburants');?>