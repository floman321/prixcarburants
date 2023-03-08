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

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
	  var _cmd = {configuration: {}}
	}
	if (!isset(_cmd.configuration)) {
	  _cmd.configuration = {}
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
	tr += '<td class="hidden-xs">'
	tr += '<span class="cmdAttr" data-l1key="id"></span>'
	tr += '</td>'
	tr += '<td>'
	tr += '<div class="input-group">'
	tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
	tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
	tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
	tr += '</div>'
	tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
	tr += '<option value="">{{Aucune}}</option>'
	tr += '</select>'
	tr += '</td>'
	tr += '<td>'
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
	tr += '</td>'
	tr += '<td>'
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
	tr += '<div style="margin-top:7px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '</div>'
	tr += '</td>'
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
	tr += '</td>';
	tr += '<td>'
	if (is_numeric(_cmd.id)) {
	  tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
	  tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
	}
	tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
	tr += '</tr>'
	$('#table_cmd tbody').append(tr)
	var tr = $('#table_cmd tbody tr').last()
	jeedom.eqLogic.buildSelectCmd({
	  id:  $('.eqLogicAttr[data-l1key=id]').value(),
	  filter: {type: 'info'},
	  error: function (error) {
		$('#div_alert').showAlert({message: error.message, level: 'danger'})
	  },
	  success: function (result) {
		tr.find('.cmdAttr[data-l1key=value]').append(result)
		tr.setValues(_cmd, '.cmdAttr')
		jeedom.cmd.changeType(tr, init(_cmd.subType))
	  }
	})
}

//Trick to avoid emptying select with "onchange" during filling done by Jeedom
var ChargementFini = "Non";
function FinChargement(){
	ChargementFini = "Oui";
}

/*
 * Function to display select option one after the other
 * TypeSelect = type of select to display (commune or station)
 * IdSelectModif = id of the element that changed and launch this function
 * IdNum = Number of the favorite
 * IdSelectAModif = id of the element that need to be modified
*/
function AffichageChoixStation(TypeSelect, IdSelectModif, IdNum, IdSelectAModif) {
	if(ChargementFini == "Oui") {
		if(document.getElementById(IdSelectModif).value != ''){
			var selectElmt = document.getElementById('SelectStation'+IdNum+'_Dep');
			var ValeurDepartement = selectElmt.options[selectElmt.selectedIndex].value;
			
			var selectElmt = document.getElementById(IdSelectModif);
			var ValeurCommune = selectElmt.options[selectElmt.selectedIndex].value;
			
			$.ajax({
				type: 'POST',
				url: 'plugins/prixcarburants/core/ajax/prixcarburants.ajax.php',
				data: {
					action: 'StationName',
					Departement: ValeurDepartement
				},
				dataType: 'json',
				success: function(data) {
					var stations = new Object();
					stations = data.result.stations;
					var MaListe = new Object();
					var MaListeText = '';
					//Create the liste of element for the select
					MaListe["0"] = "{{Sélectionner une }}"+TypeSelect;
					MaListeText = "0<->{{Sélectionner une }}"+TypeSelect;
					for (var i = 0; i < stations.length; i++) {
						if(TypeSelect == "commune") {
							MaListe[stations[i].commune.toUpperCase()] = stations[i].commune.toUpperCase()+" ("+stations[i].cp+")";
							MaListeText = MaListeText+"--retouralaligne--"+stations[i].commune.toUpperCase()+"<->"+stations[i].commune.toUpperCase()+" ("+stations[i].cp+")";
							document.getElementById('Station'+IdNum+'_CommuneListe').value = MaListeText;
							document.getElementById('SelectStation'+IdNum+'_Station').options.length = 0;
							document.getElementById('SelectStation'+IdNum+'_Station').style.display = "none";
						} else {
							if(stations[i].commune.toUpperCase() == ValeurCommune) {
								MaListe[stations[i].id] = stations[i].marque+" ; "+stations[i].nom+" ; "+stations[i].adresse;
								MaListeText = MaListeText+"--retouralaligne--"+stations[i].id+"<->"+stations[i].marque+" ; "+stations[i].nom+" ; "+stations[i].adresse;
								document.getElementById('Station'+IdNum+'_StationListe').value = MaListeText;
							}
						}
					}
					//Register selected values on input test
					document.getElementById('Station'+IdNum+'_Dep').value = ValeurDepartement;
					document.getElementById('Station'+IdNum+'_Commune').value = ValeurCommune;
					//Fill the next select
					updateComboBox(IdSelectAModif, MaListe);
					document.getElementById('SelectStation'+IdNum+'_AddFav').style.display = "none";
				}
			})
		} else {
			//Hide next select elements
			document.getElementById('SelectStation'+IdNum+'_Commune').options.length = 0;
			document.getElementById('SelectStation'+IdNum+'_Commune').style.display = "none";
			document.getElementById('SelectStation'+IdNum+'_Station').options.length = 0;
			document.getElementById('SelectStation'+IdNum+'_Station').style.display = "none";
		}
	}
}
//Function to fill data on a select
function updateComboBox(idSelect, data) {
	var monSelect = document.getElementById(idSelect);
	var selected;
	var i=0;
	monSelect.options.length = 0;
 
	for (var key in data) 
	{
		monSelect.options[monSelect.length] = new Option(data[key],key);
		i++;
	}
	document.getElementById(idSelect).style.display = "block";
}

//Function to show/hide "add a favorite"
function AffichAjoutFav(IdSelect) {
	if(ChargementFini == "Oui") {
		if(document.getElementById('SelectStation'+IdSelect+'_Station').value != ''){
			//Register selected value on input text
			var selectElmt = document.getElementById('SelectStation'+IdSelect+'_Station');
			document.getElementById('Station'+IdSelect+'_Station').value = selectElmt.options[selectElmt.selectedIndex].value;
			CompteurTemp = IdSelect + 1;
			if(document.getElementById('SelectStation'+IdSelect+'_Station').value != '0' && document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display != "block" && document.getElementById('SelectStation'+CompteurTemp+'_AddFav').style.display != "block") {
				document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display = "block";
				//if(IdSelect > 1) document.getElementById('DivOrdreFavoris').style.display = "block";
			} else if(document.getElementById('SelectStation'+IdSelect+'_Station').value == '0' && document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display == "block") {
				document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display = "none";
				if(IdSelect == 2) {
					document.getElementById('DivOrdreFavoris').style.display = "none";
					document.getElementById('OrdreFavoris').options[0].selected = true;
				}
			}
		}
	}
}

//Function to show next favorite when "add a favorite" is selected
function AjouteFavoris(IdNew, IdCurrent) {
	if(ChargementFini == "Oui") {
		document.getElementById('SelectStation'+IdNew+'_Label').style.display = "block";
		document.getElementById('SelectStation'+IdNew+'_Dep').style.display = "block";
		document.getElementById('SelectStation'+IdNew+'_RemoveFav').style.display = "block";
		document.getElementById('SelectStation'+IdCurrent+'_AddFav').style.display = "none";
		document.getElementById('SelectStation'+IdCurrent+'_RemoveFav').style.display = "none";
	}
}

//Function to hide all elements of a favorite when "minus" button is selected
function RetireFavoris(IdCurrent, IdOld) {
	if(ChargementFini == "Oui") {
		document.getElementById('SelectStation'+IdCurrent+'_Label').style.display = "none";
		document.getElementById('SelectStation'+IdCurrent+'_Dep').style.display = "none";
		document.getElementById('SelectStation'+IdCurrent+'_Dep').options[0].selected = true;
		document.getElementById('SelectStation'+IdCurrent+'_Commune').style.display = "none";
		document.getElementById('SelectStation'+IdCurrent+'_Station').style.display = "none";
		document.getElementById('SelectStation'+IdCurrent+'_AddFav').style.display = "none";
		document.getElementById('SelectStation'+IdOld+'_RemoveFav').style.display = "block";
		document.getElementById('Station'+IdCurrent+'_Dep').value = "";
		document.getElementById('Station'+IdCurrent+'_CommuneListe').value = "";
		document.getElementById('Station'+IdCurrent+'_Commune').value = "";
		document.getElementById('Station'+IdCurrent+'_StationListe').value = "";
		document.getElementById('Station'+IdCurrent+'_Station').value = "";
		if(document.getElementById('SelectStation'+IdOld+'_AddFav').value != ''){
			document.getElementById('SelectStation'+IdOld+'_AddFav').style.display = "block";
		}
		document.getElementById('SelectStation'+IdCurrent+'_RemoveFav').style.display = "none";
		
		if(IdCurrent == 2) {
			//document.getElementById('DivOrdreFavoris').style.display = "none";
			document.getElementById('OrdreFavoris').options[0].selected = true;
		}
	}
}

//Function to show/hide elements bellow checkbox "geoloc" or "favorite"
$(".eqLogicAttr[data-l2key='ViaLoca']").on('change click update', function () {
	if(!this.checked){
	  $("#vialoca_jeeAdd_wrapper").hide();
	}else{
	  $("#vialoca_jeeAdd_wrapper").show();
	}
  });
  $(".eqLogicAttr[data-l2key='jeedom_loc']").on('change click update', function () {
	if(this.checked){
	  $("#vialoca_cmd_wrapper").hide();
	}else{
	  $("#vialoca_cmd_wrapper").show();
	}
  });

  $(".eqLogicAttr[data-l2key='Favoris']").on('change click update', function () {
	if(!this.checked){
	  $("#favoris_wrapper").hide();
	}else{
	  $("#favoris_wrapper").show();
	  $("#SelectStation1_Label").show();
	  $("#SelectStation1_Dep").show();
	}
  });
function printEqLogic(_mem) {
  	$(".eqLogicAttr[data-l2key='ViaLoca']").trigger('change');
  	$(".eqLogicAttr[data-l2key='jeedom_loc']").trigger('change');
   $(".eqLogicAttr[data-l2key='Favoris']").trigger('change');
  
}

//Function to display and filled already saved data
function FillSavedSelect() {
	// Do this only if Favorites CheckBox is cheked
	if (!$('.eqLogicAttr[data-l1key=configuration][data-l2key=Favoris]').value()==1) {
		return; 
	}	
	for(var i = 1; i <= 10 ; i++) {
		if(document.getElementById('Station'+i+'_Dep').value != '' && document.getElementById('Station'+i+'_CommuneListe').value != '' && document.getElementById('Station'+i+'_Commune').value != '' && document.getElementById('Station'+i+'_StationListe').value != '' && document.getElementById('Station'+i+'_Station').value != '') {
			//Only if all select elements are filled
			//Show desire elements 
			document.getElementById('SelectStation'+i+'_Label').style.display = "block";
			document.getElementById('SelectStation'+i+'_Dep').style.display = "block";
			document.getElementById('SelectStation'+i+'_Commune').style.display = "block";
			document.getElementById('SelectStation'+i+'_Station').style.display = "block";
			
			//Prepare select list
			PrepareList(document.getElementById('Station'+i+'_CommuneListe').value, 'SelectStation'+i+'_Commune');
			PrepareList(document.getElementById('Station'+i+'_StationListe').value, 'SelectStation'+i+'_Station');
			
			//Select correct element on the list
			document.getElementById('SelectStation'+i+'_Dep').value = OptionSelect = document.getElementById('Station'+i+'_Dep').value;
			document.getElementById('SelectStation'+i+'_Commune').value = OptionSelect = document.getElementById('Station'+i+'_Commune').value;
			document.getElementById('SelectStation'+i+'_Station').value = OptionSelect = document.getElementById('Station'+i+'_Station').value;
			
			//hide unwanted buttons
			document.getElementById('SelectStation'+i+'_AddFav').style.display = "none";
			document.getElementById('SelectStation'+i+'_RemoveFav').style.display = "none";
			
			//if(i > 1) document.getElementById('DivOrdreFavoris').style.display = "block";
		} else {
			if(i > 1) {
				var compteur = i - 1;
				//Show hide desire elements
				document.getElementById('SelectStation'+compteur+'_AddFav').style.display = "block";
				document.getElementById('SelectStation'+compteur+'_RemoveFav').style.display = "block";
				break;
			}
		}
	}
}

//Function to prepare correct list
function PrepareList(str, idSelect) {
	var ListeSelect = str.split("--retouralaligne--");
	var MaListe = new Object();
	for (var key in ListeSelect) {
		var SubListe = ListeSelect[key].split("<->");
		MaListe[SubListe[0]] = SubListe[1];
	}
	updateComboBox(idSelect, MaListe);
}



$(".cmdSendSel").on('click', function () {
   
 var el = $(this);
  jeedom.cmd.getSelectModal({cmd:{type:'info'}}, function(result) {
       var calcul = el.closest('div').find('.eqLogicAttr[data-l1key=configuration][data-l2key=geoloc]');
       calcul.val('');
       calcul.atCaret('insert', result.human);
     });
});

$(".eqLogicAttr[data-l2key='formatdate']").on('change click update', function () {
	if($(this).val()=='perso'){
	  $("#format_date_perso_wrapper").show();
	}else{
	  $("#format_date_perso_wrapper").hide();
	}
  });

// function called after eqLogic is loaded
// use here to trigger change on checkboxes for new equipement
function printEqLogic(_mem) {
  	$(".eqLogicAttr[data-l2key='ViaLoca']").trigger('change');
  	$(".eqLogicAttr[data-l2key='jeedom_loc']").trigger('change');
   $(".eqLogicAttr[data-l2key='Favoris']").trigger('change');
  
}