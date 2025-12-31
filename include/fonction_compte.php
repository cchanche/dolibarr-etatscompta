<?php 
function label($bdd,$compte) // retourn le label d'un compte
{
$ecriture = $bdd->prepare('SELECT label FROM `'.MAIN_DB_PREFIX.'accounting_account` WHERE fk_pcg_version = ? AND account_number = ?');                    
$ecriture->execute( array($_SESSION['plan_comptable'],$compte ) );
$donnees = $ecriture->fetch();
$label=$donnees['label'] ;
$ecriture->closeCursor(); 
return $label;
}

function lister_compte($bdd,$periode,$preg="^[0-7]") //retourne la liste des comptes utiliser sur une periode
{
$listecompte=array();
$ecriture = $bdd->prepare('SELECT numero_compte FROM `'.MAIN_DB_PREFIX.'accounting_bookkeeping` WHERE `numero_compte` REGEXP ? AND `doc_date` BETWEEN ? AND ? GROUP BY numero_compte ');

$ecriture->execute( array($preg, $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') ) );

while($donnees = $ecriture->fetch())
{
  $listecompte[]=$donnees['numero_compte'] ;
}
$ecriture->closeCursor();       
return $listecompte; 
}


function totalcompte($bdd,$periode,$compte,$sens,$cloture) //retourne le total des opérations d'un compte sur une période 
{
//si sens vaut 0 total débit sinon total credit
//si cloture est a 1 on prend aussi les écriture du journal cloture

if ($cloture)
    {
    if ($sens)
        {
        $ecriture = $bdd->prepare("SELECT SUM(credit) total FROM `".MAIN_DB_PREFIX."accounting_bookkeeping` WHERE numero_compte = ? AND `doc_date` BETWEEN ? AND ?");
        }
    else
        {
        $ecriture = $bdd->prepare("SELECT SUM(debit) total FROM `".MAIN_DB_PREFIX."accounting_bookkeeping` WHERE numero_compte = ? AND `doc_date` BETWEEN ? AND ?");
        }
    }
else
    {
    if (!$sens)
        {
        $ecriture = $bdd->prepare("SELECT SUM(credit) total FROM `".MAIN_DB_PREFIX."accounting_bookkeeping` WHERE code_journal <> 'CL' AND numero_compte = ? AND `doc_date` BETWEEN ? AND ?");
        }
    else
        {
        $ecriture = $bdd->prepare("SELECT SUM(debit) total FROM `".MAIN_DB_PREFIX."accounting_bookkeeping` WHERE code_journal <> 'CL' AND numero_compte = ? AND `doc_date` BETWEEN ? AND ?");
        }
    }

$ecriture->execute(array($compte , $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') ) );
$donnees = $ecriture->fetch();
$total=$donnees['total'];
$ecriture->closeCursor(); 

return $total ;
}


function parametre_compte($bdd,$periode,$compte,$cloture) //retourne un tableau contenant le label,les totaux et les soldes pour un compte
// si cloture est a 1 on prend aussi les écriture de cloture
{
$parametre['label']=label($bdd,$compte) ;
$parametre['totaldebit']=totalcompte($bdd,$periode,$compte,0,$cloture);
$parametre['totalcredit']=totalcompte($bdd,$periode,$compte,1,$cloture);
$parametre['soldedebit']=solde_compte($parametre['totaldebit'],$parametre['totalcredit'],0);
$parametre['soldecredit']=solde_compte($parametre['totaldebit'],$parametre['totalcredit'],1);




return $parametre ;
}
 
?>
