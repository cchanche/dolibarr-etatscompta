<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2018      Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2020      Tobias Sekan			<tobias.sekan@startmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/compta/bank/list.php
 *       \ingroup    banque
 *       \brief      Home page of bank module
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
if (!empty($conf->categorie->enabled)) require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';





// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'accountancy', 'compta'));

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'bankaccountlist'; // To manage different context of search

$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_number = GETPOST('search_number', 'alpha');
$search_status = GETPOST('search_status') ?GETPOST('search_status', 'alpha') : 'opened'; // 'all' or ''='opened'
$optioncss = GETPOST('optioncss', 'alpha');

if (!empty($conf->categorie->enabled))
{
	$search_category_list = GETPOST("search_category_".Categorie::TYPE_ACCOUNT."_list", "array");
}

// Security check
if ($user->socid) $socid = $user->socid;
if (!empty($user->rights->accounting->chartofaccount)) $allowed = 1; // Dictionary with list of banks accounting account allowed to manager of chart account
if (!$allowed) $result = restrictedArea($user, 'banque');

$diroutputmassaction = $conf->bank->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'b.label';
if (!$sortorder) $sortorder = 'ASC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Account($db);
$hookmanager->initHooks(array('bankaccountlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'b.ref'=>'Ref',
    'b.label'=>'Label',
);

$checkedtypetiers = 0;
$arrayfields = array(
    'b.ref'=>array('label'=>$langs->trans("BankAccounts"), 'checked'=>1),
    'accountype'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
    'b.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
    'b.number'=>array('label'=>$langs->trans("AccountIdShort"), 'checked'=>1),
    'b.account_number'=>array('label'=>$langs->trans("AccountAccounting"), 'checked'=>(!empty($conf->accounting->enabled) || !empty($conf->accounting->enabled))),
    'b.fk_accountancy_journal'=>array('label'=>$langs->trans("AccountancyJournal"), 'checked'=>(!empty($conf->accounting->enabled) || !empty($conf->accounting->enabled))),
    'toreconcile'=>array('label'=>$langs->trans("TransactionsToConciliate"), 'checked'=>1),
    'b.currency_code'=>array('label'=>$langs->trans("Currency"), 'checked'=>0),
	'b.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'b.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'b.clos'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
    'balance'=>array('label'=>$langs->trans("Balance"), 'checked'=>1, 'position'=>1010),
);
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */
/*
if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $search_ref = '';
    $search_label = '';
    $search_number = '';
    $search_status = '';
}


/*
 * View
 */

$form = new FormCategory($db);

$title = $langs->trans('BankAccounts');

// Load array of financial accounts (opened by default)
$accounts = array();

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

// $a = \Module\FonctionDeBase::AffectatioDB($sql);
// $accounts = Datotheca::AffectatioDB($db,$sql);












// $result = $db->query('SELECT id, nom, forcePerso, degats, niveau, experience FROM personnages');
// while ($accounts = $result->fetch()) // Chaque entrée sera récupérée et placée dans un array.
// while ($accounts = $result->fetch(PDO::FETCH_ASSOC)) // Chaque entrée sera récupérée et placée dans un array.
// {
// echo $perso['nom'], ' a ', $perso['forcePerso'], ' de force, ',
// $perso['degats'], ' de dégâts, ', $perso['experience'], '
// d\'expérience et est au niveau ', $perso['niveau'];
// }

// $sql .= $db->plimit($limit + 1, $offset);
/*
$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($resql);
        $accounts[$objp->rowid] = $objp->rowid;
        $accounts[$objp->label] = $objp->label;
        $accounts[$objp->amount] = $objp->amount;
        $i++;
    }
    $db->free($resql);
}
else dol_print_error($db);

*/

$help_url = 'EN:Module_Banks_and_Cash|FR:Module_Banques_et_Caisses|ES:M&oacute;dulo_Bancos_y_Cajas';
llxHeader('', $title, $help_url);

$link = '';


$num_rows = count($accounts);

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
if ($search_ref != '')      $param .= '&search_ref='.$search_ref;
if ($search_label != '')    $param .= '&search_label='.$search_label;
if ($search_number != '')   $param .= '&search_number='.$search_number;
if ($search_status != '')   $param .= '&search_status='.$search_status;
if ($show_files)            $param .= '&show_files='.$show_files;
if ($optioncss != '')       $param .= '&optioncss='.$optioncss;
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
//    'presend'=>$langs->trans("SendByMail"),
//    'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->banque->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$newcardbutton = '';
if ($user->rights->banque->configurer)
{
    $newcardbutton .= dolGetButtonTitle($langs->trans('NewFinancialAccount'), '', 'fa fa-plus-circle', 'card.php?action=create');
}

/* Début du propre de la page : Exercices */





// fichier de conf

include("include/include.php"); 
  echo '<h1>' . $titre_du_site . '</h1>' 
        . '<h3>Du ' . $_SESSION['periode'][0]->format('j/m/Y') .' Au '  . $_SESSION['periode'][1]->format('j/m/Y') . '</h3>' ;  

//on verifie les variable du formulaire et on met a jour les variable de session

if (isset($_POST['date_debut']))
{
$_SESSION['periode'][0]=DateTime::createFromFormat('Y-m-d',$_POST['date_debut']);
}


if (isset($_POST['date_fin']))
{
$_SESSION['periode'][1]=DateTime::createFromFormat('Y-m-d',$_POST['date_fin']);
}

//on traite la valeur a ajouter dans bilan.conf

if (isset($_POST['ajouter']))
    {
    $ajouter=(int)$_POST['ajouter'] ;
    if($ajouter!=0)
        {
        $_SESSION['bilan_conf'][]=$ajouter ;
        }
    }

// on traite la valeur a enlever dans bilan.conf


if (isset($_POST['enlever']))
    {
    $enlever=(int)$_POST['enlever'] ;
    if($enlever!=0)
    {
        //si la valeur est dans le tableau
        if (in_array($enlever,$_SESSION['bilan_conf']))
        {
            //on trouve sa clef
            $cle = array_search($enlever,$_SESSION['bilan_conf']) ;
            //on la supprimer
            unset($_SESSION['bilan_conf'][$cle]) ;
           }
    }
} 

// on ecrit bilan_conf dans bilan.conf

$fichier=fopen('fichier/bilan.conf','w') ;

foreach($_SESSION['bilan_conf'] as $key => $value)
{
fwrite($fichier, $value) ;
fwrite($fichier,"\n");
}
fclose($fichier) ;



// on affiche le formulaire



echo "<h2>Configuration</h2>";
echo "<h4>Choix des dates d'affichage</h4>";
echo "<form method='post' action=''>";
echo "<p><div> Date de début </div>";
echo "<input type='date' name='date_debut' value=".$_SESSION['periode'][0]->format('Y-m-d')." />";
echo "<div> Date de fin </div>";
echo "<input type='date' name='date_fin' value=".$_SESSION['periode'][1]->format('Y-m-d')." /><br>";
echo "<div> Configuration des comptes de tiers à l'actif du bilan (mettre un numéro de compte par ligne) </div>";
echo "Liste actuel de compte : <br><br>";

foreach($_SESSION['bilan_conf'] as $key => $value)
    {
    echo $value ;
    echo '<br>' ;
    }

echo "<br>";
echo "Compte a ajouter :<input type='text' name='ajouter' /><br>";
echo "Compte a supprimer : <input type='text' name='enlever' />";
echo "<br><br>";
echo "<input type='submit' value='Valider' />";
echo "</p>";
echo "</form>";
echo "</body></html>";




/* Fin du propre de la page */

// End of page
llxFooter();
$db->close();

