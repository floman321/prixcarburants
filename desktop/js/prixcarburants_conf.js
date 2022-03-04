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
// hide la programmation du cron.
$(".configKey[data-l1key='freq']").on('change', function () {
  if($(this).val() != 'prog'){
    $(".mgh-actu-auto").hide();
  }else{
    $(".mgh-actu-auto").show();
  }
});

function prixcarburants_postSaveConfiguration() {
  $.ajax({
    type: 'POST',
    url: 'plugins/prixcarburants/core/ajax/prixcarburants.ajax.php',
    data: {
      action: 'UpdateCron'
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error);
  },
    success: function(data) {
      //console.log(data);
      //console.log(data.state);
      if(data.state == 'ok'){
        $('#div_alert').showAlert({message: '{{Cron mis à jour!}}', level: 'success'});
        // mise à jour des prev et next date
        $(".dueDateShow").show();
        $(".configInfo[data-key='prevDate']").text(data.result.prevDate);
        $(".configInfo[data-key='nextDate']").text(data.result.nextDate);
      }else{
        $('#div_alert').showAlert({message: '{{Erreur Mise à jour cron}}', level: 'error'});
      }
    }
  })


}