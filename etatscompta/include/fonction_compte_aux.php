 <?php
 
function lister_compte_aux($bdd,$periode,$compte) //retourne la liste des comptes auxiliaire utilisé pour un compte sur une période
{
$listecompteaux=array();
$ecriture = $bdd->prepare('SELECT subledger_account FROM `llx_accounting_bookkeeping` WHERE numero_compte = ? AND`doc_date` BETWEEN ? AND ? GROUP BY subledger_account ');
$ecriture->execute( array($compte, $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') ) );

while($donnees = $ecriture->fetch())
    {
    $listecompteaux[]=$donnees['subledger_account'] ;
    }
$ecriture->closeCursor();  
return $listecompteaux; 
}

function totalcompte_aux($bdd,$periode,$compte,$compteaux,$sens,$cloture) //retourne le total des opérations d'un compte auxiliaire sur une période 
{
//si sens vaut 0 total débit sinon total credit
//si cloture est a 1 on prend aussi les écriture du journal cloture

if(!is_null($compteaux))
    {
    if ($cloture)
        {
        if ($sens)
            {
            $ecriture = $bdd->prepare('SELECT SUM(credit) total FROM llx_accounting_bookkeeping WHERE numero_compte = ? AND subledger_account =? AND doc_date BETWEEN ? AND ?');
            }
        else
            {
            $ecriture = $bdd->prepare('SELECT SUM(debit) total FROM llx_accounting_bookkeeping WHERE numero_compte = ? AND subledger_account = ? AND doc_date BETWEEN ? AND ?');
            }
        }
    else
        {
        if (!$sens)
            {
            $ecriture = $bdd->prepare('SELECT SUM(credit) total FROM llx_accounting_bookkeeping WHERE code_journal <> "CL" AND numero_compte = ? AND subledger_account =? AND doc_date BETWEEN ? AND ?');
            }
        else
            {
            $ecriture = $bdd->prepare('SELECT SUM(debit) total FROM llx_accounting_bookkeeping WHERE code_journal <> "CL" AND numero_compte = ? AND subledger_account =? AND doc_date BETWEEN ? AND ?');
            }
        }
    $ecriture->execute(array($compte , $compteaux, $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') ) );
    }
else
    {
    if ($cloture)
        {
        if ($sens)
            {
            $ecriture = $bdd->prepare('SELECT SUM(credit) total FROM llx_accounting_bookkeeping WHERE numero_compte = ? AND subledger_account IS NULL AND doc_date BETWEEN ? AND ?');
            }
        else
            {
            $ecriture = $bdd->prepare('SELECT SUM(debit) total FROM llx_accounting_bookkeeping WHERE numero_compte = ? AND subledger_account IS NULL AND doc_date BETWEEN ? AND ?');
            }
        }
    else
        {
        if (!$sens)
            {
            $ecriture = $bdd->prepare('SELECT SUM(credit) total FROM llx_accounting_bookkeeping WHERE code_journal <> "CL" AND numero_compte= ? AND subledger_account IS NULL AND doc_date BETWEEN ? AND ?');
            }
        else
            {
            $ecriture = $bdd->prepare('SELECT SUM(debit) total FROM llx_accounting_bookkeeping WHERE code_journal <> "CL" AND numero_compte = ? AND subledger_account IS NULL AND doc_date BETWEEN ? AND ?');
            }
        }
    $ecriture->execute(array($compte , $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') ) );
    }
$donnees = $ecriture->fetch();
$total=$donnees['total'];
$ecriture->closeCursor(); 
return round($total,2) ;
} 


function parametre_compte_aux($bdd,$periode,$compte,$compteaux,$cloture) //retourne un tableau contenant le label,les totaux et les soldes pour un compte auxiliaire (le label est celui du compte)
// si cloture est a 1 on prend aussi les écriture de cloture
{
$parametre['label']=label($bdd,$compte) ;
$parametre['totaldebit']=totalcompte_aux($bdd,$periode,$compte,$compteaux,0,$cloture);
$parametre['totalcredit']=totalcompte_aux($bdd,$periode,$compte,$compteaux,1,$cloture);
$parametre['soldedebit']=solde_compte($parametre['totaldebit'],$parametre['totalcredit'],0);
$parametre['soldecredit']=solde_compte($parametre['totaldebit'],$parametre['totalcredit'],1);
return $parametre ;
}
 

 
 ?>
