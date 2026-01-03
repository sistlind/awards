<?php
/******************************************************************************
 * Awards
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 * 
 * https://github.com/sistlind/awards
 *                  
 *****************************************************************************/
use Admidio\Infrastructure\Entity\Entity;
use Admidio\Users\Entity\User;

require_once(__DIR__ .'/awards_common.php');

//Berechtigung checken
if($gCurrentUser->isAdministratorUsers() == false)
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$getAwardID  = admFuncVariableIsValid($_GET, 'awa_id', 'numeric', array('defaultValue' => 0));

//Begin der Seite
$headline  = $gL10n->get('AWA_HEADLINE');
$page = new HtmlPage($headline);



//Falls Datenbank nicht vorhanden Install-Skript starten
if(!isAwardsDbInstalled()){
	//Datenbank nicht vorhanden
	$page->addHtml('<h2>'.$gL10n->get('SYS_ERROR').'</h2>');
	$page->addHtml($gL10n->get('AWA_ERR_NO_DB'));
	$page->addHtml('<p><a href=awards_install.php>'.$gL10n->get('AWA_INSTALL').'</a></p>');
	$page->show();
	return;
}


if ($getAwardID<1)
{
    $page->addHtml("Falscher Seitenaufruf!");
    $page->show();
    exit;
}



$NewAWAObj = new Entity($gDb, $g_tbl_praefix.'_user_awards', 'awa', $getAwardID);

$userobj= new User($gDb, $gProfileFields,$NewAWAObj->getValue('awa_usr_id'));

if (isset($_POST['submit_ok']))
{
	if ($NewAWAObj->delete())
	{
		$page->addHtml('<h2>Ehrung gelöscht</h2>');
	}else
	{
		$page->addHtml('<h2>Fehler beim Löschen</h2>');
	}
}
else
{
	$gNavigation->addUrl(CURRENT_URL);
	$page->addHtml('Ehrung vom '.$NewAWAObj->getValue('awa_date').': <b>'.$NewAWAObj->getValue('awa_name'));
	if (strlen($NewAWAObj->getValue('awa_info'))>0)
	{
		$page->addHtml(' ('.$NewAWAObj->getValue('awa_info').')');
	}

	$page->addHtml('</b> an '.$userobj->getValue('FIRST_NAME').' '. $userobj->getValue('LAST_NAME').' wirklich löschen?');
	$page->addHtml('<form action="'.ADMIDIO_URL . FOLDER_PLUGINS .'/'.$plugin_folder.'/awards_delete.php?awa_id='.$getAwardID.'" method="post">
	<input type="hidden" name="delete_ID" value="'.$getAwardID.'">
	<div class="formLayout" id="edit_awards_form">
	    <div class="formBody">
		<div class="formSubmit">
		    <button id="btnSave" type="submit" name="submit_ok" value="submit_ok"><i class="bi bi-trash"></i>&nbsp;OK</button>
		</div>
	    </div>
	</div>
	</form>');
}



$page->show();
?>
