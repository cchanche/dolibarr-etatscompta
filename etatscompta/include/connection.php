<?php


// connection à la base de donnée


try
{  
        // $dsn = 'mysql:host='. $domaine . ';port=' . $port . ';dbname=' . $bdd_name . ';charset=utf8' ;
        $dsn = 'mysql:host=localhost;port=3306;dbname=dolibarr;charset=utf8' ;
        // $bdd = new PDO($dsn, $bdd_utilisateur);
        // $bdd = new PDO($dsn, $bdd_utilisateur, $bdd_password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $bdd = new PDO($dsn, 'root', 'root', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
}
catch (Exception $e)
{
        die('Erreur : ' . $e->getMessage());
}



// On récupère l'identifiant de la structure

$ecriture = $bdd->prepare('SELECT value FROM `llx_const` WHERE name = "MAIN_INFO_SOCIETE_NOM"');                    
$ecriture->execute( array() );
$donnees = $ecriture->fetch();
$titre_du_site=$donnees['value'] ;
$ecriture->closeCursor(); 

// On recupere le plan comptable actif

$ecriture = $bdd->prepare('SELECT pcg_version FROM `llx_accounting_system` WHERE active = 1');                    
$ecriture->execute( array() );
$donnees = $ecriture->fetch();
$_SESSION['plan_comptable']=$donnees['pcg_version'] ;
$ecriture->closeCursor(); 


//on configure les dates si elle ne sont pas défini  

$annee=date('Y')-1 ;  //defini les date lors d'une nouvelle session du 1er Janver au 31/12 de l'année précédente

if ( !isset($_SESSION['periode']))
	{
	$_SESSION['periode']=array(DateTime::createFromFormat('d/m/Y','01/01/' . $annee),DateTime::createFromFormat('d/m/Y','31/12/' . $annee)) ;
	}
	
//on charge le fichier bilan.conf si il n'est pas chargée

if (!isset($_SESSION['bilan_conf']))
	{
        if (is_file('fichier/bilan.conf')) 
        {
        $fichier=fopen('fichier/bilan.conf', 'r') ;
        $bilan_conf=array();
        while(!feof($fichier))
            {
            $ligne=(int)fgets($fichier);
            if ($ligne!=0)
                $bilan_conf[]=$ligne ;
            
            }
        $_SESSION['bilan_conf']=$bilan_conf ;
        fclose($fichier) ;
        }
        else
            exit("Le fichier bilan.conf n'éxiste pas et est nécessaire<br>");
    }
   



/*
// Verification de la connection et formulaire d'identification
// on doit se connecter  avec un utiisateur de dolibarr ayant les droit de consultation du grand livre
// pour désactivé le module passer la variable $module_connection à 0 dans conf.inc.php
*/

if($module_connection) // si le module de connection est activé
    {
    if(!isset($_SESSION['login_ok'])) //si on est pas connecté
        {
        if(isset($_SESSION['nb_connection'])) // si il y a eu des tentative de connection
            { 
            if($_SESSION['nb_connection']>10) exit("trop de tentative de connexion") ; // si plus de 10 on quitte
            }
        else $_SESSION['nb_connection']=0 ; // si pas initianilisé on initialise
        $_SESSION['nb_connection']+=1 ; //et on incrémente
        
        if(isset($_POST['login']) && isset($_POST['password'])) // si le formulaire a été rempli
            {
            $test=verifier_identification($bdd,$_POST['login'],$_POST['password']) ; //on vérifie
            if ($test) 
                $_SESSION['login_ok'] = 1 ;
            else
                {
                afficher_formulaire() ;
                exit("Erreur d'identification");
                }
            }
        else
            {
            afficher_formulaire() ; //
            exit("Pas encore connecté") ;
            }        
        }
    }
    



function verifier_identification($bdd,$login_to_test,$password_to_test)   //return true si l'utilisateur et valide et a la permission false sinon
{
/* verifie 
si l'utilisateur existe
si son mot de passe est correcte
si il a le droit de consulter la compta
return true si ok false sinon
*/

$ecriture = $bdd->prepare('SELECT rowid,login ,pass_crypted FROM `llx_user` WHERE login = ?');                    
$ecriture->execute( array( $login_to_test ));
$donnees = $ecriture->fetch();
$ecriture->closeCursor(); 
if($donnees) // si l'utilisateur existe
    {
    if(password_verify ($password_to_test,$donnees['pass_crypted']))
        {
        if(droit_consultation($bdd,$donnees['rowid'])) return true ;
        else return false ;
        }
    else
        {
        return false ;
        }
    }
else
    {
    return false ;
    }
}


function afficher_formulaire()
{
/* affiche le formulaire de connection*/
echo 'Vous devez vous identifiez avec un utilisateur ayant les droits de consultation sur le grand livre, La permission ne doit pas être hérité du groupe' ;
echo '<form method="post" action="index.php">' ;
echo '<p><div> Login </div><input type="text" name="login"/></p>' ;
echo '<p><div> Mot de passe </div><input type="password" name="password"/></p>' ;
echo '<input type="submit" value="Valider" />';
}

function droit_consultation($bdd,$id_utilisateur)
{
/* 
return true si l'utilisateur don l'id est fournit a bien la permission de consulter les opération du grand livre
autorisation 50411 
*/

$ecriture = $bdd->prepare('SELECT fk_user,fk_id FROM `llx_user_rights` WHERE fk_user = ? AND fk_id=50411');                    
$ecriture->execute( array( $id_utilisateur ));
$donnees = $ecriture->fetch();
$ecriture->closeCursor(); 

if($donnees) return true ;
else return false ;
}

?>
