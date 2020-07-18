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
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
    tr += '</td>';
    
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
    tr += '</td>';
    
    tr += '<td>';
    if(!isset(_cmd.type) || _cmd.type == 'action' ){
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    }
    if(!isset(_cmd.type) || _cmd.type == 'info' ){
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    }
    tr += '</td>';
    
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" style="display : none;"></i>';
    tr += '</td>';
    
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
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
			
			var requestURL = '/../../../../plugins/prixcarburants/core/class/listestations/stations'+ValeurDepartement+'.json';
			var request = new XMLHttpRequest();
			request.open('GET', requestURL);
			request.responseType = 'json';
			request.send();
			request.onload = function() {
				var jsonObj = request.response;
				var stations = jsonObj['stations'];
				var MaListe = new Object();
				var MaListeText = '';
				//Create the liste of element for the select
				MaListe["0"] = "{{Sélectionner une }}"+TypeSelect;
				MaListeText = "<0>{{Sélectionner une }}"+TypeSelect;
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
			if(document.getElementById('SelectStation'+IdSelect+'_Station').value != '0' && document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display != "block") {
				document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display = "block";
			} else if(document.getElementById('SelectStation'+IdSelect+'_Station').value == '0' && document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display == "block") {
				document.getElementById('SelectStation'+IdSelect+'_AddFav').style.display = "none";
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
		if(document.getElementById('SelectStation'+IdOld+'_AddFav').value != ''){
			document.getElementById('SelectStation'+IdOld+'_AddFav').style.display = "block";
		}
		document.getElementById('SelectStation'+IdCurrent+'_RemoveFav').style.display = "none";
	}
}

//Function to show/hide elements bellow checkbox "geoloc" or "favorite"
function CheckBx(Type_) {
 
	if(Type_ == "Favoris+") {
		document.getElementById('SelectStation1_Label').style.display = "block";
		document.getElementById('SelectStation1_Dep').style.display = "block";
    }
    if(Type_ == "Favoris-") {
		for(var i = 1; i <= 10; i++) {
			document.getElementById('SelectStation'+i+'_Label').style.display = "none";
			document.getElementById('SelectStation'+i+'_Dep').style.display = "none";
			document.getElementById('SelectStation'+i+'_Dep').options[0].selected = true;
			document.getElementById('SelectStation'+i+'_Commune').style.display = "none";
			document.getElementById('SelectStation'+i+'_Commune').options.length = 0;
			document.getElementById('SelectStation'+i+'_Station').style.display = "none";
			document.getElementById('SelectStation'+i+'_Station').options.length = 0;
			document.getElementById('SelectStation'+i+'_AddFav').style.display = "none";
			document.getElementById('SelectStation'+i+'_RemoveFav').style.display = "none";
			document.getElementById('Station'+i+'_Dep').value = "";
			document.getElementById('Station'+i+'_CommuneListe').value = "";
			document.getElementById('Station'+i+'_Commune').value = "";
			document.getElementById('Station'+i+'_StationListe').value = "";
			document.getElementById('Station'+i+'_Station').value = "";
		}
	} 
  
	if(Type_ == "ViaLoca+") {
		document.getElementById('Divloca1').style.display = "block";
		document.getElementById('Divloca2').style.display = "block";
		document.getElementById('Divloca3').style.display = "block";
    }
    if(Type_ == "ViaLoca-") {
		document.getElementById('Divloca1').style.display = "none";
		document.getElementById('Divloca2').style.display = "none";
		document.getElementById('Divloca3').style.display = "none";
		document.getElementById('rayon').value = "";
		document.getElementById('NbStation').options[0].selected = true;
	}

}

//Function to display and filled already saved data
function FillSavedSelect() {
	// Do this only if Favorites CheckBox is cheked
	if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=Favoris]').value() == "0") {
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

// Show/hide localisation and favorite box at equipement load
$('.eqLogicAttr[data-l1key=configuration][data-l2key=FinPage]').change(function() {
	if(document.getElementById('rayon').value == '') {
		document.getElementById('ViaLoca').checked = false;
		CheckBx('ViaLoca-');
	}
	if(document.getElementById('Station1_Station').value == '') {
		document.getElementById('Favoris').checked = false;
		CheckBx('Favoris-');
	}
	
});
//Show/Hide localisation box
$('.eqLogicAttr[data-l1key=configuration][data-l2key=ViaLoca]').change(function() {
	if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=ViaLoca]').value() == "1") {
		CheckBx('ViaLoca+');
	} else {
		CheckBx('ViaLoca-');
	}
});

//Show/Hide favorite box
$('.eqLogicAttr[data-l1key=configuration][data-l2key=Favoris]').change(function() {
	if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=Favoris]').value() == "1") {
		CheckBx('Favoris+');
	} else {
		CheckBx('Favoris-');
	}
});