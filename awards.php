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
GLOBAL $g_tbl_praefix;

require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');
require_once(SERVER_PATH. '/adm_plugins/awards/awards_common.php');
$plugin_folder=getFolder(__FILE__);
$plugin_path=getPath(__FILE__);


if($gCurrentUser->editUsers() == true){

// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
//$gDb->setCurrentDB();

$awardmenu = new Menu('awardmenu', $gL10n->get('AWA_HEADLINE'));

//Falls Datenbank nicht vorhanden Install-Skript starten
if(!isAwardsDbInstalled()){
	//Datenbank nicht vorhanden
	$awardmenu->addItem('categories', '/adm_plugins/'.$plugin_folder.'/awards_install.php',
			$gL10n->get('AWA_INSTALL'), '/icons/options.png');
}else{
	//echo 'Lösche Tabelle';
	//$sql='DROP TABLE '.TBL_USER_AWARDS;
	//$result=$gDb->query($sql);
	$awardmenu->addItem('awards_show', '/adm_plugins/'.$plugin_folder.'/awards_show.php',
			$gL10n->get('AWA_LIST_AWARDS'), '/icons/lists.png');
	$awardmenu->addItem('awards_new', '/adm_plugins/'.$plugin_folder.'/awards_change.php',
			$gL10n->get('AWA_HONOR'), '/icons/profile.png');
	$awardmenu->addItem('categories', '/adm_program/modules/categories/categories.php?type=AWA',
			$gL10n->get('AWA_CAT_EDIT'), '/icons/options.png');
	//Display profile information
	if(strstr($_SERVER['REQUEST_URI'], 'adm_program/modules/profile/profile.php?user_id=')!=null)
	{
		include_once($plugin_path.'/'.$plugin_folder.'/awards_profile_addin.php');
	}

}
echo '<div id="plgAwards" class="admidio-plugin-content">';
echo $awardmenu->show();  
echo '</div>';

}  
?>
