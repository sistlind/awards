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
require_once(SERVER_PATH. '/adm_program/system/login_valid.php');
require_once(SERVER_PATH. '/adm_program/system/classes/form_elements.php');
require_once(SERVER_PATH. '/adm_program/system/classes/table_text.php');


// Navigation faengt hier im Modul an
$gNavigation->clear();
$gNavigation->addUrl(CURRENT_URL);

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');

//Prüfen ob Datenbank vorhanden
require(SERVER_PATH. '/adm_program/system/overall_header.php');
echo '<h1 class="moduleHeadline">'.$gL10n->get('AWA_HEADLINE').'</h1>';
echo '<h2>'.$gL10n->get('AWA_INSTALL_HEADLINE').'</h2>';
$tablename=$g_tbl_praefix.'_user_awards';
$sql="SHOW TABLES LIKE '".$tablename."'"; 
$query = $gDb->query($sql);
if(mysql_num_rows($query)!=0){
//Datenbank vorhanden
	echo '<p>'.$gL10n->get('AWA_INSTALL_DB_EXISTS').'</p>'; 
	//echo 'Lösche Tabelle';
	//$sql='DROP TABLE '.$tablename;
	//$result=$gDb->query($sql);

} else { 
//Datenbank nicht vorhanden
echo '<p>'.$gL10n->get('AWA_INSTALL_DB_NOT_READY',$tablename).'</p>'; 
echo '<p>'.$gL10n->get('AWA_INSTALL_CREATE_DB').'</p>'; 

$sql='CREATE TABLE '.$tablename.'
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
echo '<h2>'.$gL10n->get('AWA_SUCCESS').'</h2>';
echo '<p>'.$gL10n->get('AWA_INSTALL_DB_READY').'</p>';
} 


require(SERVER_PATH. '/adm_program/system/overall_footer.php');
?>
