<?php 

function lister_compte_ana($bdd,$periode,$plan,$ana,$preg="^[0-7]") //retourne la liste des comptes utiliser sur une periode filtre ceux dont le groupe correspond au code ana
{
$listecompte=array();
$listecompteana=array();
$ecriture = $bdd->prepare('SELECT numero_compte FROM `llx_accounting_bookkeeping` WHERE `numero_compte` REGEXP ? AND `doc_date` BETWEEN ? AND ? GROUP BY numero_compte ');

$ecriture->execute( array($preg, $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') ) );

while($donnees = $ecriture->fetch())
{
  $listecompte[]=$donnees['numero_compte'] ;
}
$ecriture->closeCursor();

foreach($listecompte as $compte)
{
$ecriture = $bdd->prepare('SELECT account_number FROM `llx_accounting_account` WHERE `fk_pcg_version` = ? AND `pcg_subtype` = ? AND `account_number` = ?');

$ecriture->execute(array($plan,$ana,$compte ) );

$donnees = $ecriture->fetch() ;

$ecriture->closeCursor();

if ($donnees) $listecompteana[]=$donnees['account_number'] ;

}

return $listecompteana; 
}


function lister_code_ana($bdd,$periode,$plan,$preg="^[1-7]") //on liste les code anna utiliser sur la période 
{
//on récupere la liste des comptes
$listecodeana=array();

$listecompte=lister_compte($bdd,$periode,$preg) ;

foreach($listecompte as $compte)
    {
    $ecriture = $bdd->prepare('SELECT pcg_subtype FROM `llx_accounting_account` WHERE `fk_pcg_version` = ? AND  `account_number` = ?');
    $ecriture->execute(array($plan,$compte ) );
    $donnees = $ecriture->fetch() ;
    $ecriture->closeCursor();

    if ($donnees) 
        {
         if(array_search ( $donnees['pcg_subtype'] , $listecodeana )===FALSE)
                 $listecodeana[]=$donnees['pcg_subtype'] ;
        }

    }

return $listecodeana ;


//pour chaque compte on récupère le code anna et on l'ajoute si il n'éxiste pas


}

?>
