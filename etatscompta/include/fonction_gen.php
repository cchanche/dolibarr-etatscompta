
<?php

function lire_fichier($path)   //charge le fichier bilan_conf
{

if (is_file($path)) 
    {
    $fichier=fopen($path, 'r') ;
    $bilan_conf=array();
    while(!feof($fichier))
        {
        $ligne=(int)fgets($fichier);
        if ($ligne!=0)
            {
            $bilan_conf[]=$ligne ;
            }
        }
    fclose($fichier) ;
    return $bilan_conf ;
    }
else 
	{
    echo "Désolé le fichier n'est pas valide<br>";
    return 0 ;
    }

}


function solde_compte($totaldebit,$totalcredit,$sens) //retourne le solde du compte
//si sens vaut 0 solde debiteur sinon solde crediteur 
{
$diftotal=$totaldebit-$totalcredit ;
if($sens) {return -$diftotal ;}
else {return $diftotal ;}
}




function recuperer_ecriture($bdd,$periode,$compte,$tri) //recupere les écriture du grand livre sur la période pour les comptes ou la plage de compte
//si $tri = 'doc_date" on tri par date si $tri = 'numero_compte' on tri par compte
{
if (is_numeric($compte)) //si il  n'y a qu'un seul compte
    {

    $ecriture = $bdd->prepare('SELECT label_operation, numero_compte , ROUND(debit,2) AS debit2 ,ROUND(credit,2) AS credit2 , doc_date ,
                                        DATE_FORMAT(doc_date, "%d/%m/%Y") AS date 
                                FROM `llx_accounting_bookkeeping` 
                                WHERE doc_date BETWEEN ? AND ? AND numero_compte = ?  
                                ORDER BY ?');
    $ecriture->execute( array(  $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') , $compte  , $tri ) );
    }
    
else 
    {

    $ecriture = $bdd->prepare('SELECT label_operation, numero_compte , ROUND(debit,2) AS debit2 ,ROUND(credit,2) AS credit2 , doc_date ,
                                        DATE_FORMAT(doc_date, "%d/%m/%Y") AS date 
				FROM `llx_accounting_bookkeeping` 
				WHERE doc_date BETWEEN ? AND ? AND numero_compte >= ? AND numero_compte <= ? 
				ORDER BY ?');
    $ecriture->execute(array( $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') , $compte[0] , $compte[1] , $tri));
    }
    
$listeecriture=array(); ;
while($donnees = $ecriture->fetch())
    {
    $listeecriture[]=array( 'date'=> $donnees['date'], 
                            'numerocompte'=> $donnees['numero_compte'],
                            'label' => $donnees['label_operation'],
                            'debit' => $donnees['debit2'],
                            'credit' => $donnees['credit2'] ) ;
    }
    
$ecriture->closeCursor();
return $listeecriture ;
}

//function pour récupérerles écriture des compte auxiliaire 

function recuperer_ecriture_aux($bdd,$periode,$compte,$compteaux,$tri) //recupere les écriture du grand livre sur la période pour les comptes ou la plage de compte
//si $tri = 'doc_date" on tri par date si $tri = 'numero_compte' on tri par compte
{
    
    $ecriture = $bdd->prepare('SELECT label_operation, numero_compte , subledger_account , ROUND(debit,2) AS debit2 ,ROUND(credit,2) AS credit2 , doc_date ,
                                        DATE_FORMAT(doc_date, "%d/%m/%Y") AS date 
                                FROM `llx_accounting_bookkeeping` 
                                WHERE doc_date BETWEEN ? AND ? AND subledger_account = ? AND numero_compte = ? 
                                ORDER BY ?');
    $ecriture->execute( array(  $periode[0]->format('Y-m-d') , $periode[1]->format('Y-m-d') , $compteaux , $compte , $tri ) );
    

    
$listeecriture=array(); 
while($donnees = $ecriture->fetch())
    {
    $listeecriture[]=array( 'date'=> $donnees['date'], 
                            'numerocompte'=> $donnees['numero_compte'],
                            'numerocompteaux' => $donnees['subledger_account'],
                            'label' => $donnees['label_operation'],
                            'debit' => $donnees['debit2'],
                            'credit' => $donnees['credit2'] ) ;
    }
    
$ecriture->closeCursor();
return $listeecriture ;
}



// fonction pour generer les ecritures 

function generer_ecriture($numtransaction,$date,$libelle,$journal,$compte,$compteaux,$debit,$credit)
{
$ecriture=$numtransaction . "," . $date . "," . $libelle . "," .$journal ."," . $compte . "," . $compteaux . ",," . $libelle . "," . $debit . "," . $credit ;
return $ecriture ;
//modele d'ecriture
//2019131,2019-01-31,Paye de janvier 2019,SAL,6453,,,Paye de janvier 2019,156.13,0
}

function purgerfichier()
{
$liste=scandir("fichier");
foreach($liste as $fichier)
    {
    if($fichier<> "." && $fichier <> ".." && $fichier <> "bilan.conf")
        {
        $path= "fichier/" . $fichier ;
        unlink($path) ;
        }
    }
}
?>
