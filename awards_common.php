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

function getPath($filepath){
$plugin_folder_pos = strpos($filepath, 'adm_plugins') + 11;
$plugin_file_pos   = strpos($filepath, basename($filepath));
$plugin_path       = substr($filepath, 0, $plugin_folder_pos);
return $plugin_path;
}
function getFolder($filepath){
$plugin_folder_pos = strpos($filepath, 'adm_plugins') + 11;
$plugin_file_pos   = strpos($filepath, basename($filepath));
$plugin_folder     = substr($filepath, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);
return $plugin_folder;
}

$tablename=$g_tbl_praefix.'_user_awards';


if(0)//up to v3
{
require_once(SERVER_PATH. '/adm_program/system/classes/form_elements.php');
require_once(SERVER_PATH. '/adm_program/system/classes/table_text.php');
require(SERVER_PATH. '/adm_program/system/overall_header.php');
require_once(SERVER_PATH. '/adm_program/system/classes/list_configuration.php');
}
else if(1)//sinv v3
{
require_once(SERVER_PATH. '/adm_program/system/classes/formelements.php');
require_once(SERVER_PATH. '/adm_program/system/classes/tabletext.php');
//require(SERVER_PATH. '/adm_program/system/common.php');
}


