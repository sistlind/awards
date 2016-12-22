<?php
/******************************************************************************
 * Awards
 *
 * Version 0.0.1
 *
 * Datum        : 29.09.2014  
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 * 
 *                  
 *****************************************************************************/
require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');
require_once(SERVER_PATH. '/adm_plugins/awards/awards_common.php');

//Berechtigung checken
if($gCurrentUser->editUsers() == false)
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$getAwardID  = admFuncVariableIsValid($_GET, 'awa_id', 'numeric', array('defaultValue' => 0));


// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');


//Begin der Seite
$headline  = $gL10n->get('AWA_HEADLINE');
$page = new HtmlPage($headline);



//Falls Datenbank nicht vorhanden Install-Skript starten
$sql_select="SHOW TABLES LIKE '".TBL_USER_AWARDS."'"; 
$query = @mysql_query($sql_select); 
if(mysql_num_rows($query)===0){
//Datenbank nicht vorhanden
$page->addHtml('<h2>'.$gL10n->get('SYS_ERROR').'</h2>');
$page->addHtml($gL10n->get('AWA_ERR_NO_DB'));
$page->addHtml('<p><a href=awards_install.php>'.$gL10n->get('AWA_INSTALL').'</a></p>');
$page->show();
exit;
}

if ($getAwardID<1)
{
$page->addHtml("Falscher Seitenaufruf!");
$page->addHtml('<ul class="iconTextLinkList">
    <li>
        <span class="iconTextLink">
            <a href="'.ADMIDIO_URL .'/adm_program/system/back.php"><img
            src="'. THEME_PATH. '/icons/back.png" alt="'.$gL10n->get('SYS_BACK').'" /></a>
            <a href="'.ADMIDIO_URL .'/adm_program/system/back.php">'.$gL10n->get('SYS_BACK').'</a>
        </span>
    </li>
</ul>');
$page->show();
exit;
}



$NewAWAObj = new TableAccess($gDb, $g_tbl_praefix.'_user_awards', 'awa', $getAwardID);

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
		    <button id="btnSave" type="submit" name="submit_ok" value="submit_ok"><img src="'. THEME_PATH. '/icons/disk.png" alt="OK"/>&nbsp;OK</button>
		</div>
	    </div>
	</div>
	</form>');
}


	$page->addHtml('<ul class="iconTextLinkList">
    <li>
        <span class="iconTextLink">
            <a href="'.ADMIDIO_URL .'/adm_program/system/back.php"><img
            src="'. THEME_PATH. '/icons/back.png" alt="'.$gL10n->get('SYS_BACK').'" /></a>
            <a href="'.ADMIDIO_URL .'/adm_program/system/back.php">'.$gL10n->get('SYS_BACK').'</a>
        </span>
    </li>
</ul>');

$page->show();
?>
