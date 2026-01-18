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

// Prevent direct access - this file should only be included by profile.php
if (!defined('ADMIDIO_PATH')) {
    exit('This file cannot be accessed directly.');
}

// Load common file first to get Admidio system functions
require_once(__DIR__ . '/awards_common.php');

// Falls Datenbank nicht vorhanden überspringen
if (!isAwardsDbInstalled()) {
    return;
}

use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Users\Entity\User;

$pluginBaseUrl = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER;

$getUserUuid = admFuncVariableIsValid($_GET, 'user_uuid', 'string', array(
    'defaultValue' => $gCurrentUser->getValue('usr_uuid')
));

$user = new User($gDb, $gProfileFields);
$user->readDataByUuid($getUserUuid);

// Ehrungen aus Datenbank laden
$awards = awa_load_awards($user->getValue('usr_id'), true);

if ($awards === null) {
    return;
}

$awardsTemplateData = array();

// Tabellenkopf
unset($PrevCatName);
foreach ($awards as $row) {
    
    $templateRow = array();
    $templateRow['id'] = $row['awa_id'];
    $templateRow['awa_cat_name'] = $row['awa_cat_name'];
    $templateRow['awa_text'] = $row['awa_name'];
    
    // bei mehr als einer 'action'-Anweisung wird die uuid benötigt (siehe dazu 'sys-template-parts/list.functions.tpl')
    $templateRow['uuid'] = $row['awa_id'];
    
    // Multi-Org-Installation?
    $sql = 'SELECT COUNT(*) as count FROM ' . TBL_ORGANIZATIONS;
    $query = $gDb->queryPrepared($sql);
    $result = $query->fetch();
    
    // only show organisation, if multiple organisations are present
    if ($result['count'] > 1) {
        $templateRow['awa_text'] .= ' (' . $row['awa_org_name'] . ')';
    }
    
    if (isset($row['awa_info']) && strlen($row['awa_info']) > 0) {
        $templateRow['awa_text'] .= ' (' . $row['awa_info'] . ')';
    }
    
    $templateRow['awa_text_date'] = $gL10n->get('AWA_SINCE') . ' ' . date('d.m.Y', strtotime($row['awa_date']));
    
    $templateRow['actions'][] = array(
        'url' => SecurityUtils::encodeUrl($pluginBaseUrl . '/system/awards_delete.php', array(
            'awa_id' => $row['awa_id']
        )),
        'icon' => 'bi-trash',
        'tooltip' => $gL10n->get('AWA_DELETE_HONOR')
    );
    
    $templateRow['actions'][] = array(
        'url' => SecurityUtils::encodeUrl($pluginBaseUrl . '/system/awards_change.php', array(
            'awa_id' => $row['awa_id']
        )),
        'icon' => 'bi-pencil-square',
        'tooltip' => $gL10n->get('AWA_EDIT_HONOR')
    );
    
    $awardsTemplateData[] = $templateRow;
}

// Check if $page is available (should be set by the including profile page)
if (!isset($page) || !is_object($page)) {
    return;
}

$page->assignSmartyVariable('awardsTemplateData', $awardsTemplateData);
$page->assignSmartyVariable('urlAwardsShow', SecurityUtils::encodeUrl($pluginBaseUrl . '/system/awards_show.php'));
$page->assignSmartyVariable('showAwardsOnProfile', $gCurrentUser->isAdministratorUsers());

