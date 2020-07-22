# Ecrit par Christian Quest le 10/3/2017
# Modification et adaptation par DuchkPy le 23/06/2020
# ce code est sous licence WTFPL
# La version d'origine disponible sur https://github.com/openeventdatabase/datasources

# recuperation des nom/marque des stations services et sortie en stream js

from bs4 import BeautifulSoup
import requests
import json
import re
import unicodedata

# recuperation du token pour la recherche
session = requests.session()
html = session.get('https://www.prix-carburants.gouv.fr/').text
html_tree = BeautifulSoup(html,'lxml').find(id="recherche_recherchertype__token")
token=html_tree['value']

#Creation de la serialisation
def serialiseur_perso(obj):
    if isinstance(obj, Departement):
        return {"numero": obj.numero,
                "stations": obj.stations}
                
    if isinstance(obj, Station):
       return {"id": obj.id,
                "commune": obj.commune,
                "cp": obj.cp,
                "adresse": obj.adresse,
                "nom": obj.nom,
                "marque": obj.marque}
    
    raise TypeError(repr(obj) + " n'est pas serialisable !")

class Departement:
    def __init__(self, numero):
        self.numero = numero
        self.stations = []

class Station:
    def __init__(self, id, commune, cp, adresse, nom, marque):
        self.id = id
        self.commune = commune
        self.cp = cp
        self.adresse = adresse
        self.nom = nom
        self.marque = marque

# preparation de la liste des departements...
depts = ['01']
for dep in range(2, 20):
   d = '0'+str(dep)
   depts.append(d[-2:])
depts.extend(['2A', '2B'])
for dep in range(21, 96):
   depts.append(str(dep))

#Boucle les departements
for d in depts:
    Increment = 0
    #Creation du fichier, en retirant le 0 au debut
    try:
        nomfichier = "stations"+str(int(d))+".json"
    except:
        nomfichier = "stations"+d+".json"
    ListeStation = Departement(d)
    
    # execution de la recherche
    html = session.post('https://www.prix-carburants.gouv.fr/',
        {'_recherche_recherchertype[localisation]':d,
        '_recherche_recherchertype[_token]':token}).text
    page=1
    
    #Boucle les pages
    while True:
        # reception des resultats
        html = session.get('https://www.prix-carburants.gouv.fr/recherche/?page=%s&limit=100' % page).text
        html_tree = BeautifulSoup(html,'lxml')
        try:
            pages = len(html_tree.find_all(class_=re.compile('^paginationPage')))
        except:
            pages=1
        
        if page==1:
            try:
                Listeh2 = html_tree.find_all('h2')
                nbstationdep = Listeh2[1].string.split(' ')[0]
            except:
                break
        
        #Boucle les donnees des stations
        for retour in html_tree.find_all(class_='data'):
            dv = retour.find(class_='pdv-description')
            td = dv.find_all(re.compile('span'))
            NomMarque = dv.find('strong').string.split(' | ')
            Adresse = td[len(td)-1].string.split(' ', 1)
            
            #Met en forme en retirant tous les accents et caracteres particuliers
            StCommune = unicodedata.normalize('NFD', Adresse[1]).encode("ascii", "replace").replace("?", "")
            StCP = Adresse[0]
            StAdresse = unicodedata.normalize('NFD', td[len(td)-2].string).encode("ascii", "replace").replace("?", "")
            StNom = unicodedata.normalize('NFD', NomMarque[0]).encode("ascii", "replace").replace("?", "")
            StMarque = unicodedata.normalize('NFD', NomMarque[1]).encode("ascii", "replace").replace("?", "")
            
            # id, Commune, Code Postal, Adresse, Nom, Marque                
            ListeStation.stations.append(Station(retour['id'], StCommune, StCP, StAdresse, StNom, StMarque))
            
            Increment = Increment + 1

        if page==pages:
            break
        else:
            page = page + 1
    
    #Ecriture du fichier
    with open(nomfichier, "w") as file:
        json.dump(ListeStation, file, sort_keys=False, separators=(',', ':'), default=serialiseur_perso)
    #Affichage du departement fait
    print("Departement : "+d+", nombre de stations relevees : "+str(Increment)+"/"+nbstationdep)
    #Ecriture dans un fichier pour le controle
    with open("0-retour.txt", "a") as fichier:
        fichier.write(d+" : "+str(Increment)+"/"+nbstationdep+"\n")