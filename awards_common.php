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
define("TBL_USER_AWARDS",$tablename);
unset($tablename);


if(0)//up to v3
{
require_once(SERVER_PATH. '/adm_program/system/classes/form_elements.php');
require_once(SERVER_PATH. '/adm_program/system/classes/table_text.php');
require(SERVER_PATH. '/adm_program/system/overall_header.php');
require_once(SERVER_PATH. '/adm_program/system/classes/list_configuration.php');
}
else if(1)//since v3
{
require_once(SERVER_PATH. '/adm_program/system/classes/formelements.php');
require_once(SERVER_PATH. '/adm_program/system/classes/tabletext.php');
//require(SERVER_PATH. '/adm_program/system/common.php');
}

function awa_load_awards($userid,$show_all)
{
global $gCurrentOrganization;
global $gProfileFields;
global $gDb;

	$restriction="";
	if (intval($userid)>0)
	{
		$restriction=' Where awa_usr_id='.$userid.' ';
	}

	if (!$show_all)
	{ 
		if(empty($restriction))
		{
			$restriction='Where awa_org_id ='.$gCurrentOrganization->getValue('org_id');
		}
		else{ 
			$restriction=$restriction.' And awa_org_id ='.$gCurrentOrganization->getValue('org_id').' ';
		}
	}


$sql    = 'SELECT awa_id, awa_usr_id, awa_org_id, awa_cat_id, awa_name, awa_info, awa_date, 
		awa_cat_seq.cat_sequence as awa_cat_seq,
		awa_cat_name.cat_name as awa_cat_name,
		awa_org_name.org_longname as awa_org_name,
		awa_org_shortname.org_shortname as awa_org_shortname,
		last_name.usd_value as last_name,
		first_name.usd_value as first_name
          FROM '.TBL_USER_AWARDS.' 
             JOIN '. TBL_USER_DATA. ' as last_name
               ON last_name.usd_usr_id = awa_usr_id
              AND last_name.usd_usf_id = '. $gProfileFields->getProperty('LAST_NAME', 'usf_id'). '
             JOIN '. TBL_USER_DATA. ' as first_name
               ON first_name.usd_usr_id = awa_usr_id
              AND first_name.usd_usf_id = '. $gProfileFields->getProperty('FIRST_NAME', 'usf_id'). '
             LEFT JOIN '. TBL_ORGANIZATIONS. ' as awa_org_name
               ON awa_org_name.org_id = awa_org_id
             LEFT JOIN '. TBL_ORGANIZATIONS. ' as awa_org_shortname
               ON awa_org_shortname.org_id = awa_org_id
          	 JOIN '. TBL_CATEGORIES. ' as awa_cat_name
               ON awa_cat_name.cat_id = awa_cat_id
			AND awa_cat_name.cat_type =\'AWA\'
	         JOIN '. TBL_CATEGORIES. ' as awa_cat_seq
               ON awa_cat_seq.cat_id = awa_cat_id
			AND awa_cat_seq.cat_type =\'AWA\'
		'.$restriction.'
	ORDER BY awa_cat_seq, awa_date DESC,last_name,first_name';
	//echo $sql;
	$query=$gDb->query($sql);
	if (mysql_num_rows($query)==0)
	{
		return false;
	}
	$awards=array();
	while($row=$gDb->fetch_array($query))
	{	
		$awards[]=$row;
	}

return $awards;
}
