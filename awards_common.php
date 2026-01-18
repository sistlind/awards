<?php
/******************************************************************************
 * Awards
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 * 
 * https://github.com/sistlind/awards
 * 
 * Compatible with Admidio v5.03+
 *                  
 *****************************************************************************/
<<<<<<< HEAD

use Admidio\Infrastructure\Entity\Entity;
=======
require_once(__DIR__ . '/../../system/common.php');
>>>>>>> 6a48a71b68e5352ae2cce1c4452bde1868ea752f

// Admidio v5 path structure - common.php is now in /system/common.php
$rootPath = dirname(__DIR__, 2);
require_once($rootPath . '/system/common.php');

// Register autoloader for Awards plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'Plugins\\Awards\\classes\\';
    $base_dir = __DIR__ . '/classes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Check if the Awards database table is installed
 * @return bool Returns true if the table exists
 */
function isAwardsDbInstalled(): bool
{
    global $gDb;
    
    // Use Admidio v5 database API to check table existence
    try {
        return $gDb->tableExists(TBL_USER_AWARDS);
    } catch (Exception $e) {
        return false;
    }
}

$plugin_folder = '/' . basename(__DIR__);
$plugin_path = dirname(__DIR__);

// Define PLUGIN_FOLDER constant for use throughout the plugin
if (!defined('PLUGIN_FOLDER')) {
    define('PLUGIN_FOLDER', $plugin_folder);
}

$awa_debug_config_exists = 'False';
if (file_exists($plugin_path . $plugin_folder . '/awards_config.php')) {
    $awa_debug_config_exists = 'True';
    require_once($plugin_path . $plugin_folder . '/awards_config.php');
}
// pruefen, ob alle Einstellungen in config.php gesetzt wurden
// falls nicht, hier noch mal die Default-Werte setzen
if (!isset($plg_role_enabled) || !is_numeric($plg_role_enabled)) {
    $plg_role_enabled = 0;
}

if (!isset($plg_leader_checked) || !is_numeric($plg_leader_checked)) {
    $plg_leader_checked = 1;
}

if (!isset($plg_cat_id) || !is_numeric($plg_cat_id)) {
    $plg_cat_id = 0;
}

if (!isset($plg_debug_enabled) || !is_numeric($plg_debug_enabled)) {
    $plg_debug_enabled = 0;
}

if ($plg_debug_enabled == 1) { // Debug Teil 1!
    echo '<br>Plugin-Path: ' . $plugin_path . $plugin_folder . '/';
    echo '<br>Config-Path: ' . $plugin_path . $plugin_folder . '/config.php';
    echo '<br>Config-exists: ' . $awa_debug_config_exists;
}

<<<<<<< HEAD




// Define the user awards table constant using TABLE_PREFIX (Admidio v5)
$tablename = TABLE_PREFIX . '_user_awards';
define("TBL_USER_AWARDS", $tablename);
unset($tablename);



/**
 * Load awards for a user or all users
 * @param int $userid User ID to load awards for, or 0 for all
 * @param bool $show_all Show awards from all organizations
 * @return array|null Returns array of awards or null if none found
 */
function awa_load_awards(int $userid, bool $show_all): ?array
=======
$tablename=$g_tbl_praefix.'_user_awards';
define("TBL_USER_AWARDS",$tablename);
unset($tablename);

function awa_load_awards($userid,$show_all)
>>>>>>> 6a48a71b68e5352ae2cce1c4452bde1868ea752f
{
    global $gCurrentOrganization;
    global $gProfileFields;
    global $gDb;

    $restriction = "";
    $queryParams = array();
    
    if ($userid > 0) {
        $restriction = ' WHERE awa_usr_id = ? ';
        $queryParams[] = $userid;
    }

    if (!$show_all) {
        $orgId = $gCurrentOrganization->getValue('org_id');
        if (empty($restriction)) {
            $restriction = ' WHERE awa_org_id = ? ';
        } else {
            $restriction .= ' AND awa_org_id = ? ';
        }
        $queryParams[] = $orgId;
    }

    $sql = 'SELECT awa_id, awa_usr_id, awa_org_id, awa_cat_id, awa_name, awa_info, awa_date, 
        awa_cat_seq.cat_sequence as awa_cat_seq,
        awa_cat_name.cat_name as awa_cat_name,
        awa_org_name.org_longname as awa_org_name,
        awa_org_shortname.org_shortname as awa_org_shortname,
        last_name.usd_value as last_name,
        first_name.usd_value as first_name
          FROM ' . TBL_USER_AWARDS . ' 
             JOIN ' . TBL_USER_DATA . ' as last_name
               ON last_name.usd_usr_id = awa_usr_id
              AND last_name.usd_usf_id = ? 
             JOIN ' . TBL_USER_DATA . ' as first_name
               ON first_name.usd_usr_id = awa_usr_id
              AND first_name.usd_usf_id = ? 
             LEFT JOIN ' . TBL_ORGANIZATIONS . ' as awa_org_name
               ON awa_org_name.org_id = awa_org_id
             LEFT JOIN ' . TBL_ORGANIZATIONS . ' as awa_org_shortname
               ON awa_org_shortname.org_id = awa_org_id
           	 JOIN ' . TBL_CATEGORIES . ' as awa_cat_name
               ON awa_cat_name.cat_id = awa_cat_id
            AND awa_cat_name.cat_type = \'AWA\'
             JOIN ' . TBL_CATEGORIES . ' as awa_cat_seq
               ON awa_cat_seq.cat_id = awa_cat_id
            AND awa_cat_seq.cat_type = \'AWA\'
        ' . $restriction . '
    ORDER BY awa_cat_seq, awa_date DESC, last_name, first_name';

    // Prepend profile field IDs to query params
    $fullParams = array_merge(
        array(
            $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
            $gProfileFields->getProperty('FIRST_NAME', 'usf_id')
        ),
        $queryParams
    );

    $query = $gDb->queryPrepared($sql, $fullParams);
    
    if ($query->rowCount() === 0) {
        return null;
    }
    
    return $query->fetchAll();
}
