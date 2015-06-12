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
// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

if($gCurrentUser->editUsers() == false)
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}


// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
$gDb->setCurrentDB();

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');



$gLayout['title']  = 'Ehrungen & Auszeichnungen';//$gL10n->get('AWA_INSTALL_TITLE');
//Begin der Seite
echo '<h1 class="moduleHeadline">'.$gLayout['title'].'</h1>';

//Falls Datenbank nicht vorhanden Install-Skript starten
$tablename=$g_tbl_praefix.'_user_awards';
$sql_select="SHOW TABLES LIKE '".$tablename."'"; 
$query = @mysql_query($sql_select); 
if(mysql_num_rows($query)===0){
//Datenbank vorhanden
echo 'Datenbank nicht gefunden!<br>';
echo '<a href=install.php>INSTALLIEREN</a>';
require(SERVER_PATH. '/adm_program/system/overall_footer.php');
exit;
}

$getAwardID  = admFuncVariableIsValid($_GET, 'awa_id', 'numeric', 1);
if ($getAwardID<1)
{
echo "Falscher Seitenaufruf!";
echo '<ul class="iconTextLinkList">
    <li>
        <span class="iconTextLink">
            <a href="'.$g_root_path.'/adm_program/system/back.php"><img
            src="'. THEME_PATH. '/icons/back.png" alt="'.$gL10n->get('SYS_BACK').'" /></a>
            <a href="'.$g_root_path.'/adm_program/system/back.php">'.$gL10n->get('SYS_BACK').'</a>
        </span>
    </li>
</ul>';
require(SERVER_PATH. '/adm_program/system/overall_footer.php');
exit;
}



$NewAWAObj = new TableAccess($gDb, $g_tbl_praefix.'_user_awards', 'awa', $getAwardID);

$userobj= new User($gDb, $gProfileFields,$NewAWAObj->getValue('awa_usr_id'));

if (isset($_POST['submit_ok']))
{
	if ($NewAWAObj->delete())
	{
		echo '<h2>Ehrung gelöscht</h2>';
	}else
	{
		echo '<h2>Fehler beim Löschen</h2>';
	}
}
else
{
$gNavigation->addUrl(CURRENT_URL);
	echo 'Ehrung vom '.$NewAWAObj->getValue('awa_date').': <b>'.$NewAWAObj->getValue('awa_name');
	if (strlen($NewAWAObj->getValue('awa_info'))>0)
	{
	echo ' ('.$NewAWAObj->getValue('awa_info').')';
	}

	echo'</b> an '.$userobj->getValue('FIRST_NAME').' '. $userobj->getValue('LAST_NAME').' wirklich löschen?';
	echo '<form action="'.$g_root_path.'/adm_plugins/awards/awards_delete.php?awa_id='.$getAwardID.'" method="post">
	<input type="hidden" name="delete_ID" value="'.$getAwardID.'">
	<div class="formLayout" id="edit_awards_form">
	    <div class="formBody">
		<div class="formSubmit">
		    <button id="btnSave" type="submit" name="submit_ok" value="submit_ok"><img src="'. THEME_PATH. '/icons/disk.png" alt="OK"/>&nbsp;OK</button>
		</div>
	    </div>
	</div>
	</form>';
}







echo '<ul class="iconTextLinkList">
    <li>
        <span class="iconTextLink">
            <a href="'.$g_root_path.'/adm_program/system/back.php"><img
            src="'. THEME_PATH. '/icons/back.png" alt="'.$gL10n->get('SYS_BACK').'" /></a>
            <a href="'.$g_root_path.'/adm_program/system/back.php">'.$gL10n->get('SYS_BACK').'</a>
        </span>
    </li>
</ul>';


require(SERVER_PATH. '/adm_program/system/overall_footer.php');
?>
