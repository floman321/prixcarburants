<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

$plugin = plugin::byId('prixcarburants');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>
	<div class="row row-overflow">
		<div class="col-xs-12 eqLogicThumbnailDisplay">
			<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
			<div class="eqLogicThumbnailContainer">
				<div class="cursor eqLogicAction logoPrimary" data-action="add">
					<i class="fas fa-plus-circle"></i>
					<br>
					<span>{{Ajouter}}</span>
				</div>
				<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
					<i class="fas fa-wrench"></i>
					<br>
					<span>{{Configuration}}</span>
				</div>
			</div>
			<legend><i class="fas fa-table"></i> {{Mes Véhicules}}</legend>
			<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
			<div class="eqLogicThumbnailContainer">
				<?php
				foreach ($eqLogics as $eqLogic) {
					$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
					echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
					echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
					echo '<br>';
					echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
					echo '</div>';
				}
				?>
			</div>
		</div>

		<div class="col-xs-12 eqLogic" style="display: none;">
			<div class="input-group pull-right" style="display:inline-flex">
				<span class="input-group-btn">
					<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
					<a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
					<a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
					<a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
				</span>
			</div>
			<ul class="nav nav-tabs" role="tablist">
				<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
				<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
				<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
			</ul>
			<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;" onmouseenter="FinChargement()">
				<div role="tabpanel" class="tab-pane active" id="eqlogictab">
					<br/>
					<form class="form-horizontal">
						<fieldset>
							<legend>{{Général :}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label" for="name">{{Nom du véhicule :}}</label>
								<div class="col-sm-3">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" id="name" placeholder="{{Nom de l'équipement prix carburant}}"/>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label" for="ObjParent">{{Objet parent :}}</label>
								<div class="col-sm-3">
									<select id="ObjParent" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										foreach (jeeObject::all() as $object) {
											echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie :}}</label>
								<div class="col-sm-9">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label"></label>
								<div class="col-sm-9">
									<label class="checkbox-inline" for="Activer"><input id="Activer" type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
									<label class="checkbox-inline" for="Visible"><input id="Visible" type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
								</div>
							</div>
						</fieldset>
						
						<fieldset>
							<legend>{{Style d'affichage :}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label" for="FormatDate">{{Format date :}}</label>
								<div class="col-sm-3">
									<select id="FormatDate" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="formatdate">
										<option value="Y-m-d à G:i:s">{{Par défaut}}</option>
										<option value="j M Y à G:i:s"><?php echo date("j M Y à G:i:s"); ?></option>
										<option value="j M Y à G:i"><?php echo date("j M Y à G:i"); ?></option>
										<option value="j M y à G:i:s"><?php echo date("j M y à G:i:s"); ?></option>
										<option value="j M y à G:i"><?php echo date("j M y à G:i"); ?></option>
										<option value="j-m-y à G:i:s"><?php echo date("j-m-y à G:i:s"); ?></option>
										<option value="j-m-y à G:i"><?php echo date("j-m-y à G:i"); ?></option>
										<option value="Y-m-j à G:i:s"><?php echo date("Y-m-j à G:i:s"); ?></option>
										<option value="Y-m-j à G:i"><?php echo date("Y-m-j à G:i"); ?></option>
										<option value="Y-m-j"><?php echo date("Y-m-j"); ?></option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label" for="TypeCarburant">{{Type de carburant :}}</label>
								<div class="col-sm-3">
									<select id="TypeCarburant" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="typecarburant">
										<option value="">{{Aucun}}</option>
										<option value="Gazole">{{Gazole}}</option>
										<option value="E10">{{SP95-E10}}</option>
										<option value="SP95">{{SP95}}</option>
										<option value="SP98">{{SP98}}</option>
										<option value="E85">{{E85}}</option>
										<option value="GPLc">{{GPL}}</option>
									</select>
								</div>
							</div>
                            <div class="form-group">
                                  <label class="col-sm-3 control-label" for="name">{{Capacité du réservoir (litres) :}}</label>
                                 	 <div class="col-sm-1">
                                  		<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="reservoirlitre" placeholder="{{En litres}}"/>
                                  	</div>
							</div>
                            <div class="form-group">
                                  <label class="col-sm-3 control-label" for="name">{{Considérer date relevée comme expirée (jours) :}}</label>
                                 	 <div class="col-sm-1">
                                  		<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="dateexpire" placeholder="{{En jours}}"/>
                                     </div>
                                     <div class="col-sm-2">
                                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="dateexpirevisible" />{{Ignorer les stations avec date périmée}}</label>
                                  	</div>
							</div>
						</fieldset>
						
						<fieldset>
							<legend>{{Stations :}}</legend>
							<div class="Conteneur_Flex">
								<div class="col-sm-3 control-label"> </div>
								
								<div class="Conteneur_localisation" style="width: 33%;">
    								<label class="checkbox-inline" for="ViaLoca"><input id="ViaLoca" type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="ViaLoca" onclick="CheckBx('ViaLoca')" />{{Via une localisation}}</label>
    								<div class="form-group" id="Divloca1" style="display: none;">
        								<label class="Conteneur_Label" for="geoloc">{{Chercher autour de :}}</label>
        								<div class="Conteneur_Input">
                                            <select class="eqLogicAttr form-control" id="geoloc" data-l1key="configuration" data-l2key="geoloc">
                                                <?php
                                                $none = 0;
                                                if (class_exists('geotravCmd')) {
                                                    //List all geotrav localisation
                                                    foreach (eqLogic::byType('geotrav') as $geoloc) {
                                                        if ($geoloc->getConfiguration('type') == 'location' && $geoloc->getConfiguration('coordinate') != '') {
                                                            $none++;
                                                            echo '<option value="' . $geoloc->getId() . '">' . $geoloc->getName() . '</option>';
                                                        }
                                                    }
                                                    //List all geoloc localisation
                                                    foreach (eqLogic::byType('geoloc') as $moneqGeoLoc) {
                                                        if ($moneqGeoLoc->getIsEnable()){
                                                            foreach (cmd::searchConfigurationEqLogic($moneqGeoLoc->getId(),'{"mode":"fixe"') as $geoloc) {
                                                                $none++;
                                                                echo '<option value="' . $geoloc->getId() . '">' . $geoloc->getName() . '</option>';
                                                            }
                                                            foreach (cmd::searchConfigurationEqLogic($moneqGeoLoc->getId(),'{"mode":"dynamic"') as $geoloc) {
                                                                $none++;
                                                                echo '<option value="' . $geoloc->getId() . '">' . $geoloc->getName() . '</option>';
                                                            }
                                                        }
                                                    }
                                                }
                                                if ((config::byKey('info::latitude') != '') && (config::byKey('info::longitude') != '') ) {
                                                    echo '<option value="jeedom">{{Configuration Jeedom}}</option>';
                                                    $none++;
                                                }
                                                if ($none == 0) {
                                                    echo '<option value="none">{{Pas de localisation disponible (latitude et longitude nécessaire)}}</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
        							</div>
        							
                                    <div class="form-group" id="Divloca2" style="display: none;">
        								<label class="Conteneur_Label" for="rayon">{{Rayon maxi (Km) :}}</label>
        								<div class="Conteneur_Input">
        									<input type="text" id="rayon" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="rayon" placeholder="{{Saisir un nombre de kilomètres}}"/>
        								</div>
									</div>
        							<div class="form-group" id="Divloca3" style="display: none;">
        								<label class="Conteneur_Label" for="NbStation">{{Nombre de stations :}}</label>
        								<div class="Conteneur_Input">
        									<select id="NbStation" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="nbstation">
        										<option value="0">0</option>
        										<option value="1">1</option>
        										<option value="2">2</option>
        										<option value="3">3</option>
        										<option value="4">4</option>
        										<option value="5">5</option>
        										<option value="6">6</option>
        										<option value="7">7</option>
        										<option value="8">8</option>
        										<option value="9">9</option>
        										<option value="10">10</option>
        									</select>
        								</div>
        							</div>
    							</div>
    							
    							<div class="Conteneur_favoris" style="width: 33%;">
    								<label class="checkbox-inline" for="Favoris"><input id="Favoris" type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Favoris" onclick="CheckBx('Favoris')" />{{Via un / des favori(s)}}</label>
									<div class="form-group" id="DivOrdreFavoris" style="display: none;">
										<label class="Conteneur_Label" for="OrdreFavoris">{{Ordre d'affichage :}}</label>
        								<div class="Conteneur_Input">
											<select id="OrdreFavoris" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="OrdreFavoris">
												<option value="Ordre">{{Par ordre de sélection}}</option>
												<option value="Prix">{{Par prix}}</option>
											</select>
											<br />
										</div>
									</div>
    								<?php 
									if ($eqLogic != null){
										//Prepare favorite area
										for($i=1; $i <= 10; $i++) {
											echo '
										<div class="form-group">
											<label class="Conteneur_Label" id="SelectStation'. $i .'_Label" for="SelectStation'. $i .'_Dep" style="display: none;">{{station favorite n°}}'. $i .' :</label>
											<div class="Conteneur_Input">
												<select class="eqLogicAttr form-control" id="SelectStation'. $i .'_Dep" onchange="AffichageChoixStation(\'commune\', \'SelectStation'. $i .'_Dep\', '. $i .', \'SelectStation'. $i .'_Commune\')" style="display: none;">
													<option value="">{{Sélectionner un département}}</option>
													<option value="1" >01 - Ain</option>   <option value="2" >02 - Aisne</option>   <option value="3" >03 - Allier</option>   <option value="4" >04 - Alpes-de-Haute-Provence</option>   <option value="5" >05 - Hautes-Alpes</option>   <option value="6" >06 - Alpes-Maritimes</option>   <option value="7" >07 - Ardèche</option>   <option value="8" >08 - Ardennes</option>   <option value="9" >09 - Ariège</option>   <option value="10" >10 - Aube</option>   <option value="11" >11 - Aude</option>   <option value="12" >12 - Aveyron</option>   <option value="13" >13 - Bouches-du-Rhône</option>   <option value="14" >14 - Calvados</option>   <option value="15" >15 - Cantal</option>   <option value="16" >16 - Charente</option>   <option value="17" >17 - Charente-Maritime</option>   <option value="18" >18 - Cher</option>   <option value="19" >19 - Corrèze</option>   <option value="2A" >2A - Corse-du-Sud</option>   <option value="2B" >2B - Haute-Corse</option>   <option value="21" >21 - Côte d&#039;Or</option>   <option value="22" >22 - Côtes d&#039;Armor</option>   <option value="23" >23 - Creuse</option>   <option value="24" >24 - Dordogne</option>   <option value="25" >25 - Doubs</option>   <option value="26" >26 - Drôme</option>   <option value="27" >27 - Eure</option>   <option value="28" >28 - Eure-et-Loir</option>   <option value="29" >29 - Finistère</option>   <option value="30" >30 - Gard</option>   <option value="31" >31 - Haute-Garonne</option>   <option value="32" >32 - Gers</option>   <option value="33" >33 - Gironde</option>   <option value="34" >34 - Hérault</option>   <option value="35" >35 - Ille-et-Vilaine</option>   <option value="36" >36 - Indre</option>   <option value="37" >37 - Indre-et-Loire</option>   <option value="38" >38 - Isère</option>   <option value="39" >39 - Jura</option>   <option value="40" >40 - Landes</option>   <option value="41" >41 - Loir-et-Cher</option>   <option value="42" >42 - Loire</option>   <option value="43" >43 - Haute-Loire</option>   <option value="44" >44 - Loire-Atlantique</option>   <option value="45" >45 - Loiret</option>   <option value="46" >46 - Lot</option>   <option value="47" >47 - Lot-et-Garonne</option>   <option value="48" >48 - Lozère</option>   <option value="49" >49 - Maine-et-Loire</option>   <option value="50" >50 - Manche</option>   <option value="51" >51 - Marne</option>   <option value="52" >52 - Haute-Marne</option>   <option value="53" >53 - Mayenne</option>   <option value="54" >54 - Meurthe-et-Moselle</option>   <option value="55" >55 - Meuse</option>   <option value="56" >56 - Morbihan</option>   <option value="57" >57 - Moselle</option>   <option value="58" >58 - Nièvre</option>   <option value="59" >59 - Nord</option>   <option value="60" >60 - Oise</option>   <option value="61" >61 - Orne</option>   <option value="62" >62 - Pas-de-Calais</option>   <option value="63" >63 - Puy-de-Dôme</option>   <option value="64" >64 - Pyrénées-Atlantiques</option>   <option value="65" >65 - Hautes-Pyrénées</option>   <option value="66" >66 - Pyrénées-Orientales</option>   <option value="67" >67 - Bas-Rhin</option>   <option value="68" >68 - Haut-Rhin</option>   <option value="69" >69 - Rhône</option>   <option value="70" >70 - Haute-Saône</option>   <option value="71" >71 - Saône-et-Loire</option>   <option value="72" >72 - Sarthe</option>   <option value="73" >73 - Savoie</option>   <option value="74" >74 - Haute-Savoie</option>   <option value="75" >75 - Paris</option>   <option value="76" >76 - Seine-Maritime</option>   <option value="77" >77 - Seine-et-Marne</option>   <option value="78" >78 - Yvelines</option>   <option value="79" >79 - Deux-Sèvres</option>   <option value="80" >80 - Somme</option>   <option value="81" >81 - Tarn</option>   <option value="82" >82 - Tarn-et-Garonne</option>   <option value="83" >83 - Var</option>   <option value="84" >84 - Vaucluse</option>   <option value="85" >85 - Vendée</option>   <option value="86" >86 - Vienne</option>   <option value="87" >87 - Haute-Vienne</option>   <option value="88" >88 - Vosges</option>   <option value="89" >89 - Yonne</option>   <option value="90" >90 - Territoire-de-Belfort</option>   <option value="91" >91 - Essonne</option>   <option value="92" >92 - Hauts-de-Seine</option>   <option value="93" >93 - Seine-Saint-Denis</option>   <option value="94" >94 - Val-de-Marne</option>   <option value="95" >95 - Val-d&#039;Oise</option>
												</select>
                                                <input type="Text" class="eqLogicAttr form-control" id="Station'. $i .'_Dep" data-l1key="configuration" data-l2key="station'. $i .'_Dep" style="display: none;" />
												<input type="Text" class="eqLogicAttr form-control" id="Station'. $i .'_CommuneListe" data-l1key="configuration" data-l2key="station'. $i .'_CommuneListe" style="display: none;" />
												<select class="eqLogicAttr form-control" id="SelectStation'. $i .'_Commune" onchange="AffichageChoixStation(\'station\', \'SelectStation'. $i .'_Commune\', '. $i .', \'SelectStation'. $i .'_Station\')" style="display: none;"></select>
												<input type="Text" class="eqLogicAttr form-control" id="Station'. $i .'_Commune" data-l1key="configuration" data-l2key="station'. $i .'_Commune" style="display: none;" />
												<input type="Text" class="eqLogicAttr form-control" id="Station'. $i .'_StationListe" data-l1key="configuration" data-l2key="station'. $i .'_StationListe" style="display: none;" />
												<select class="eqLogicAttr form-control" id="SelectStation'. $i .'_Station" onchange="AffichAjoutFav('. $i .')" style="display: none;"></select>
												<input type="Text" class="eqLogicAttr form-control" id="Station'. $i .'_Station" data-l1key="configuration" data-l2key="station'. $i .'_Station" style="display: none;" />';
											//prepare add/remove buttons
											if($i < 10) {
												$compteur = $i + 1;
												echo '
												<a class="fas fa-plus-circle" id="SelectStation'. $i .'_AddFav" style="display: none;" onclick="AjouteFavoris(' . $compteur . ', ' . $i . ')"> {{Ajouter un autre favoris}}</a>';
											} else {
												echo '
												<a id="SelectStation'. $i .'_AddFav" style="display: none;">{{Max favoris ajoutable}}</a>';
											}
											($i >= 2)? $compteur = $i - 1:  $compteur = $i;
											echo '
											</div>
											<div>
												<a class="fas fa-minus-circle" id="SelectStation'. $i .'_RemoveFav" onclick="RetireFavoris('. $i .', '. $compteur .')" style="display: none;"></a>
											</div>
										</div><br />';
										}
									}
    								?>
    							</div>
    							<input type="Text" class="eqLogicAttr form-control" id="FinPage" data-l1key="configuration" data-l2key="FinPage" value="fini" style="display: none;" onchange="FillSavedSelect()" />
						</fieldset>
					</form>
				</div>
				<div role="tabpanel" class="tab-pane" id="commandtab">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th>#</th>
								<th>{{Nom}}</th>
								<th style="width: 250px;">{{Paramètres}}</th>
								<th>{{Action}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	
<?php include_file('desktop', 'prixcarburants-equipement', 'css', 'prixcarburants'); ?>
<?php include_file('desktop', 'prixcarburants', 'js', 'prixcarburants');?>
<?php include_file('core', 'plugin.template', 'js');?>
