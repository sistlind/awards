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

// Navigation faengt hier im Modul an
$gNavigation->clear();
$gNavigation->addUrl(CURRENT_URL);

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');

//Set headline and load template
$headline  = $gL10n->get('AWA_HEADLINE');
$page = new HtmlPage($headline);
$page->addHtml('<h2>'.$gL10n->get('AWA_INSTALL_HEADLINE').'</h2>');

//Prüfen ob Datenbank vorhanden
$sql="SHOW TABLES LIKE '".TBL_USER_AWARDS."'"; 
$query = $gDb->query($sql);
if(mysql_num_rows($query)!=0){//Datenbank vorhanden
	$page->addHtml('<p>'.$gL10n->get('AWA_INSTALL_DB_EXISTS').'</p>'); 
	//echo 'Lösche Tabelle';
	//$sql='DROP TABLE '.TBL_USER_AWARDS;
	//$result=$gDb->query($sql);
} else {//Datenbank nicht vorhanden
	$page->addHtml('<p>'.$gL10n->get('AWA_INSTALL_DB_NOT_READY',TBL_USER_AWARDS).'</p>'); 
	$page->addHtml('<p>'.$gL10n->get('AWA_INSTALL_CREATE_DB').'</p>'); 

	$sql='CREATE TABLE '.TBL_USER_AWARDS.'
	(awa_id int(10) unsigned NOT NULL AUTO_INCREMENT,
	  awa_cat_id int(10) unsigned DEFAULT NULL,
	  awa_org_id int(10) unsigned DEFAULT NULL,
	  awa_usr_id int(10) unsigned DEFAULT NULL,
	  awa_name varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
	  awa_info varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
	  awa_date date DEFAULT NULL,
	  awa_usr_id_create int(10) unsigned DEFAULT NULL,
	  awa_timestamp_create timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	  awa_usr_id_change int(10) unsigned DEFAULT NULL,
	  awa_timestamp_change timestamp NULL DEFAULT NULL,
	  PRIMARY KEY (awa_id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
	//echo $sql;
	$result=$gDb->query($sql);
	//%TODO: Fehlerabfrage?
	$page->addHtml('<h2>'.$gL10n->get('AWA_SUCCESS').'</h2>');
	$page->addHtml('<p>'.$gL10n->get('AWA_INSTALL_DB_READY').'</p>');
} 
$page->show();
?>
