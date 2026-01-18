<?php
/**
 * Awards Plugin - Preferences
 */

use Plugins\Awards\classes\Config\ConfigTable;
use Admidio\Infrastructure\Utils\SecurityUtils;

require_once(__DIR__ . '/../awards_common.php');

if (!$gCurrentUser->isAdministratorUsers()) {
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$gNavigation->addStartUrl(CURRENT_URL);

$configTable = new ConfigTable();
$configTable->init();
$config = $configTable->config;

if (isset($_POST['save'])) {
    $config['Optionen']['role_enabled'] = isset($_POST['role_enabled']) ? 1 : 0;
    $config['Optionen']['leader_checked'] = isset($_POST['leader_checked']) ? 1 : 0;
    $config['Optionen']['debug_enabled'] = isset($_POST['debug_enabled']) ? 1 : 0;
    $config['Optionen']['show_all_default'] = isset($_POST['show_all_default']) ? 1 : 0;
    $config['Optionen']['default_category'] = admFuncVariableIsValid($_POST, 'default_category', 'numeric', array('defaultValue' => 0));

    $configTable->config = $config;
    $configTable->save();

    $gMessage->setForwardUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', 2000);
    $gMessage->show($gL10n->get('SYS_SAVE_DATA'));
}

$roleEnabled = (int) ($config['Optionen']['role_enabled'] ?? 0);
$leaderChecked = (int) ($config['Optionen']['leader_checked'] ?? 1);
$debugEnabled = (int) ($config['Optionen']['debug_enabled'] ?? 0);
$showAllDefault = (int) ($config['Optionen']['show_all_default'] ?? 0);
$defaultCategory = (int) ($config['Optionen']['default_category'] ?? 0);

$page = new HtmlPage('plg-awards-preferences', $gL10n->get('SYS_SETTINGS'));

$page->addHtml('<div class="mb-3">');
$page->addHtml('<a href="' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php" class="btn btn-secondary">');
$page->addHtml('<i class="bi bi-arrow-left-circle"></i> ' . $gL10n->get('SYS_BACK') . '</a>');
$page->addHtml('</div>');

$page->addHtml('<form method="post" action="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php') . '">');
$page->addHtml('<div class="card mb-3">');
$page->addHtml('<div class="card-header"><i class="bi bi-gear"></i> ' . $gL10n->get('SYS_SETTINGS') . '</div>');
$page->addHtml('<div class="card-body">');

$page->addHtml('<div class="form-check mb-3">');
$page->addHtml('<input class="form-check-input" type="checkbox" id="role_enabled" name="role_enabled" value="1"' . ($roleEnabled === 1 ? ' checked' : '') . '>');
$page->addHtml('<label class="form-check-label" for="role_enabled">' . $gL10n->get('AWA_ROLE') . ' (' . $gL10n->get('SYS_ACTIVE') . ')</label>');
$page->addHtml('</div>');

$page->addHtml('<div class="form-check mb-3">');
$page->addHtml('<input class="form-check-input" type="checkbox" id="leader_checked" name="leader_checked" value="1"' . ($leaderChecked === 1 ? ' checked' : '') . '>');
$page->addHtml('<label class="form-check-label" for="leader_checked">' . $gL10n->get('AWA_LEADER') . ' (' . $gL10n->get('SYS_DEFAULT') . ')</label>');
$page->addHtml('</div>');

$page->addHtml('<div class="form-check mb-3">');
$page->addHtml('<input class="form-check-input" type="checkbox" id="show_all_default" name="show_all_default" value="1"' . ($showAllDefault === 1 ? ' checked' : '') . '>');
$page->addHtml('<label class="form-check-label" for="show_all_default">' . $gL10n->get('AWA_SHOW_ALL') . ' (' . $gL10n->get('SYS_DEFAULT') . ')</label>');
$page->addHtml('</div>');

$page->addHtml('<div class="mb-3">');
$page->addHtml('<label class="form-label" for="default_category">' . $gL10n->get('AWA_CAT') . ' (' . $gL10n->get('SYS_DEFAULT') . ')</label>');
$page->addHtml('<select id="default_category" name="default_category" class="form-select">');
$page->addHtml('<option value="0">' . $gL10n->get('SYS_PLEASE_CHOOSE') . '</option>');

$sql = 'SELECT cat_id, cat_name FROM ' . TBL_CATEGORIES . ' WHERE cat_type = \'AWA\' ORDER BY cat_sequence';
$query = $gDb->queryPrepared($sql);
while ($row = $query->fetch()) {
    $selected = ($row['cat_id'] == $defaultCategory) ? ' selected' : '';
    $page->addHtml('<option value="' . $row['cat_id'] . '"' . $selected . '>' . $row['cat_name'] . '</option>');
}

$page->addHtml('</select>');
$page->addHtml('</div>');

$page->addHtml('<div class="form-check mb-3">');
$page->addHtml('<input class="form-check-input" type="checkbox" id="debug_enabled" name="debug_enabled" value="1"' . ($debugEnabled === 1 ? ' checked' : '') . '>');
$page->addHtml('<label class="form-check-label" for="debug_enabled">Debug</label>');
$page->addHtml('</div>');

$page->addHtml('</div>');
$page->addHtml('<div class="card-footer d-flex justify-content-between align-items-center">');
$page->addHtml('<div class="text-muted">v' . ($config['Plugininformationen']['version'] ?? '') . ' (' . ($config['Plugininformationen']['stand'] ?? '') . ')</div>');
$page->addHtml('<button type="submit" name="save" value="save" class="btn btn-primary"><i class="bi bi-check-circle"></i> ' . $gL10n->get('SYS_SAVE') . '</button>');
$page->addHtml('</div>');
$page->addHtml('</div>');
$page->addHtml('</form>');

$page->show();
