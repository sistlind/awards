<?php
/******************************************************************************
 * Awards
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 * 
 * https://github.com/sistlind/awards
 *                  
 *****************************************************************************/
require_once(__DIR__ . '/../../system/common.php');
//require_once(__DIR__ . '/../../system/classes/TableAccess.php');


function isAwardsDbInstalled(){
    global $gDb;
    $sql_select="SHOW TABLES LIKE '".TBL_USER_AWARDS."'"; 
    $query=$gDb->query($sql_select);
    return ($query->rowcount()===0)?false:true;
}

$plugin_folder='/'.basename(__DIR__);
$plugin_path=dirname(__DIR__);

	$awa_debug_config_exists ='False';
if(file_exists($plugin_path.$plugin_folder.'/awards_config.php')) {
	$awa_debug_config_exists ='True';
	require_once($plugin_path.$plugin_folder.'/awards_config.php');
}
// pruefen, ob alle Einstellungen in config.php gesetzt wurden
// falls nicht, hier noch mal die Default-Werte setzen
if(isset($plg_role_enabled) == false || is_numeric($plg_role_enabled) == false)
{
    $plg_role_enabled = 0;
}

if(isset($plg_leader_checked) == false || is_numeric($plg_leader_checked) == false)
{
    $plg_leader_checked = 1;
}

if(isset($plg_cat_id) == false || is_numeric($plg_cat_id) == false)
{
    $plg_cat_id = 0;
}

if(isset($plg_debug_enabled) == false || is_numeric($plg_debug_enabled) == false)
{
    $plg_debug_enabled = 0;
}
if($plg_debug_enabled == 1)//Debug Teil 1!
{
	echo '<br>Plugin-Path: '.$plugin_path.$plugin_folder.'/';
	echo '<br>Config-Path: '.$plugin_path.$plugin_folder.'/config.php';
	echo '<br>Config-exists: '.$awa_debug_config_exists;
}





$tablename=$g_tbl_praefix.'_user_awards';
define("TBL_USER_AWARDS",$tablename);
unset($tablename);



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
	if ($query->rowcount()===0)
	{
		return false;
	}
	$awards=$query->fetchAll();

return $awards;
}
