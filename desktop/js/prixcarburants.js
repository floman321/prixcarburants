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
			var selectElmt = document.getElementById('station'+IdNum+'_Dep');
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
				//Create the liste of element for the select
				MaListe["0"] = "{{Sélectionner une }}"+TypeSelect;
				for (var i = 0; i < stations.length; i++) {
					if(TypeSelect == "commune") {
						MaListe[stations[i].commune.toUpperCase()] = stations[i].commune.toUpperCase()+" ("+stations[i].cp+")";
						document.getElementById('station'+IdNum+'_Station').options.length = 0;
						document.getElementById('station'+IdNum+'_Station').style.display = "none";
					} else {
						if(stations[i].commune.toUpperCase() == ValeurCommune) {
							MaListe[stations[i].id.toUpperCase()] = stations[i].marque+" ; "+stations[i].nom+" ; "+stations[i].adresse;
						}
					}
				}
				//Fill the next select
				updateComboBox(IdSelectAModif, MaListe);
				document.getElementById('station'+IdNum+'_AddFav').style.display = "none";
			}
		} else {
			document.getElementById('station'+IdNum+'_Commune').options.length = 0;
			document.getElementById('station'+IdNum+'_Commune').style.display = "none";
			document.getElementById('station'+IdNum+'_Station').options.length = 0;
			document.getElementById('station'+IdNum+'_Station').style.display = "none";
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
function AffichAjoutFav(IdSelectModif, IdAModif) {
	if(ChargementFini == "Oui") {
		if(document.getElementById(IdSelectModif).value != ''){
			if(document.getElementById(IdSelectModif).value != '0' && document.getElementById(IdAModif).style.display != "block") {
				document.getElementById(IdAModif).style.display = "block";
			} else if(document.getElementById(IdSelectModif).value == '0' && document.getElementById(IdAModif).style.display == "block") {
				document.getElementById(IdAModif).style.display = "none";
			}
		}
	}
}

//Function to show next favorite when "add a favorite" selected
function AjouteFavoris(IdNew, IdCurrent) {
	if(ChargementFini == "Oui") {
		document.getElementById('station'+IdNew+'_Label').style.display = "block";
		document.getElementById('station'+IdNew+'_Dep').style.display = "block";
		document.getElementById('station'+IdNew+'_RemoveFav').style.display = "block";
		document.getElementById('station'+IdCurrent+'_AddFav').style.display = "none";
		document.getElementById('station'+IdCurrent+'_RemoveFav').style.display = "none";
	}
}

//Function to hide all elements of a favorite when "minus" button is selected
function RetireFavoris(IdCurrent, IdOld) {
	if(ChargementFini == "Oui") {
		document.getElementById('station'+IdCurrent+'_Label').style.display = "none";
		document.getElementById('station'+IdCurrent+'_Dep').style.display = "none";
		document.getElementById('station'+IdCurrent+'_Dep').options[0].selected = true;
		document.getElementById('station'+IdCurrent+'_Commune').style.display = "none";
		document.getElementById('station'+IdCurrent+'_Station').style.display = "none";
		document.getElementById('station'+IdCurrent+'_AddFav').style.display = "none";
		document.getElementById('station'+IdOld+'_RemoveFav').style.display = "block";
		if(document.getElementById('station'+IdOld+'_AddFav').value != ''){
			document.getElementById('station'+IdOld+'_AddFav').style.display = "block";
		}
		document.getElementById('station'+IdCurrent+'_RemoveFav').style.display = "none";
	}
}

//Function to show/hide elements bellow checkbox "geoloc" or "favorite"
function CheckBx(Type_) {
 
	if(Type_ == "Favoris+") {
			document.getElementById('station1_Label').style.display = "block";
			document.getElementById('station1_Dep').style.display = "block";
    }
    if(Type_ == "Favoris-") {
			for(var i = 1; i <= 10; i++) {
				document.getElementById('station'+i+'_Label').style.display = "none";
				document.getElementById('station'+i+'_Dep').style.display = "none";
				document.getElementById('station'+i+'_Dep').options[0].selected = true;
				document.getElementById('station'+i+'_Commune').style.display = "none";
				document.getElementById('station'+i+'_Commune').options.length = 0;
				document.getElementById('station'+i+'_Station').style.display = "none";
				document.getElementById('station'+i+'_Station').options.length = 0;
				document.getElementById('station'+i+'_AddFav').style.display = "none";
				document.getElementById('station'+i+'_RemoveFav').style.display = "none";
			}
	}
  
	if(Type_ == "ViaLoca+") {
			document.getElementById('Divloca1').style.display = "block";
			document.getElementById('Divloca2').style.display = "block";
    }
    if(Type_ == "ViaLoca-") {
			document.getElementById('Divloca1').style.display = "none";
			document.getElementById('Divloca2').style.display = "none";
	}

}

$('.eqLogicAttr[data-l1key=configuration][data-l2key=ViaLoca]').change(function() {
    if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=ViaLoca]').value() == "1") {
    
      CheckBx('ViaLoca+');	
      
    }else{
      
      CheckBx('ViaLoca-');
      
    }
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=Favoris]').change(function() {
    if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=Favoris]').value() == "1") {
    
      	// show
      //document.getElementById('Conteneur_favoris').style.display = "block";
      CheckBx('Favoris+');
      
      
    }
    else {
      
      // hide
      CheckBx('Favoris-');
     // document.getElementById('Conteneur_favoris').style.display = "none";
      
      
    }
});
