<?php
/**
 * Awards Plugin - Preferences
 */

use Plugins\Awards\classes\Config\ConfigTable;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Exception;

require_once(__DIR__ . '/../awards_common.php');

if (!$gCurrentUser->isAdministratorUsers()) {
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$gNavigation->addStartUrl(CURRENT_URL);

$configTable = new ConfigTable();
$configTable->init();
$config = $configTable->config;

$previousProfileTabEnabled = (int) ($config['Optionen']['profile_tab_enabled'] ?? 0);

/**
 * Inject awards tab/accordion into profile templates and addin include into profile.php.
 * Mirrors logic from awards_install_addin.php but runs inline for preferences toggle.
 */
function awaInstallProfileIntegration(string $pluginFolder): void
{
    $zeilenumbruch = "\r\n";

    // Update profile.view.tpl (simple theme)
    $templateFile = ADMIDIO_PATH . FOLDER_THEMES . '/simple/templates/modules/profile.view';
    if (!file_exists($templateFile . '_awards_save.tpl')) {
        FileSystemUtils::copyFile($templateFile . '.tpl', $templateFile . '_awards_save.tpl');

        $templateString = file_get_contents($templateFile . '.tpl');
        $substArray = array(
            '{if $showRelations}' => '{include file="../../../..' . FOLDER_PLUGINS . $pluginFolder . '/templates/profile.view.include.button.plugin.awards.tpl"}' . $zeilenumbruch,
            '<!-- User Relations Tab -->' => '{include file="../../../..' . FOLDER_PLUGINS . $pluginFolder . '/templates/profile.view.include.awards.tab.plugin.awards.tpl"}' . $zeilenumbruch,
            '<!-- User Relations Accordion -->' => '{include file="../../../..' . FOLDER_PLUGINS . $pluginFolder . '/templates/profile.view.include.awards.accordion.plugin.awards.tpl"}' . $zeilenumbruch
        );
        foreach ($substArray as $needle => $subst) {
            $pos = strpos($templateString, $needle);
            if ($pos === false) {
                throw new Exception('Profile template anchor not found for awards tab.');
            }
            $templateString = substr_replace($templateString, $subst, $pos, 0);
        }

        file_put_contents($templateFile . '.tpl', $templateString);
    }

    // Update profile.php
    $profileFile = ADMIDIO_PATH . FOLDER_MODULES . '/profile/profile';
    if (!file_exists($profileFile . '_awards_save.php')) {
        FileSystemUtils::copyFile($profileFile . '.php', $profileFile . '_awards_save.php');

        $profileString = file_get_contents($profileFile . '.php');
        $needle = '$page->show();';
        $pos = strpos($profileString, $needle);
        if ($pos === false) {
            throw new Exception('Profile.php anchor not found for awards tab include.');
        }
        $subst = "require_once(ADMIDIO_PATH . FOLDER_PLUGINS . '" . $pluginFolder . "/awards_profile_addin.php');";
        $profileString = substr_replace($profileString, $subst . $zeilenumbruch, $pos, 0);

        file_put_contents($profileFile . '.php', $profileString);
    }
}

/**
 * Remove awards tab integration and restore backups if present.
 */
function awaUninstallProfileIntegration(string $pluginFolder): void
{
    $zeilenumbruch = "\r\n";
    $templateFile = ADMIDIO_PATH . FOLDER_THEMES . '/simple/templates/modules/profile.view';
    $profileFile = ADMIDIO_PATH . FOLDER_MODULES . '/profile/profile';

    if (file_exists($templateFile . '_awards_save.tpl')) {
        $templateString = file_get_contents($templateFile . '.tpl');
        $substArray = array(
            '{include file="../../../..' . FOLDER_PLUGINS . $pluginFolder . '/templates/profile.view.include.button.plugin.awards.tpl"}' . $zeilenumbruch,
            '{include file="../../../..' . FOLDER_PLUGINS . $pluginFolder . '/templates/profile.view.include.awards.tab.plugin.awards.tpl"}' . $zeilenumbruch,
            '{include file="../../../..' . FOLDER_PLUGINS . $pluginFolder . '/templates/profile.view.include.awards.accordion.plugin.awards.tpl"}' . $zeilenumbruch
        );
        foreach ($substArray as $subst) {
            $templateString = str_replace($subst, '', $templateString);
        }
        file_put_contents($templateFile . '.tpl', $templateString);
        FileSystemUtils::deleteFileIfExists($templateFile . '_awards_save.tpl');
    }

    if (file_exists($profileFile . '_awards_save.php')) {
        $profileString = file_get_contents($profileFile . '.php');
        $subst = "require_once(ADMIDIO_PATH . FOLDER_PLUGINS . '" . $pluginFolder . "/awards_profile_addin.php');" . $zeilenumbruch;
        $profileString = str_replace($subst, '', $profileString);
        file_put_contents($profileFile . '.php', $profileString);
        FileSystemUtils::deleteFileIfExists($profileFile . '_awards_save.php');
    }
}

if (isset($_POST['save'])) {
    $config['Optionen']['role_enabled'] = isset($_POST['role_enabled']) ? 1 : 0;
    $config['Optionen']['leader_checked'] = isset($_POST['leader_checked']) ? 1 : 0;
    $config['Optionen']['debug_enabled'] = isset($_POST['debug_enabled']) ? 1 : 0;
    $config['Optionen']['show_all_default'] = isset($_POST['show_all_default']) ? 1 : 0;
    $config['Optionen']['default_category'] = admFuncVariableIsValid($_POST, 'default_category', 'numeric', array('defaultValue' => 0));
    $config['Optionen']['profile_tab_enabled'] = isset($_POST['profile_tab_enabled']) ? 1 : 0;

    try {
        if ($config['Optionen']['profile_tab_enabled'] === 1 && $previousProfileTabEnabled !== 1) {
            awaInstallProfileIntegration(PLUGIN_FOLDER);
        }
        if ($config['Optionen']['profile_tab_enabled'] === 0 && $previousProfileTabEnabled === 1) {
            awaUninstallProfileIntegration(PLUGIN_FOLDER);
        }
    } catch (Exception $e) {
        $gMessage->show($e->getMessage());
    }

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
$profileTabEnabled = (int) ($config['Optionen']['profile_tab_enabled'] ?? 0);

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

$page->addHtml('<div class="form-check mb-3">');
$page->addHtml('<input class="form-check-input" type="checkbox" id="profile_tab_enabled" name="profile_tab_enabled" value="1"' . ($profileTabEnabled === 1 ? ' checked' : '') . '>');
$page->addHtml('<label class="form-check-label" for="profile_tab_enabled">' . $gL10n->get('AWA_HEADLINE') . ' (' . $gL10n->get('SYS_PROFILE') . ' ' . $gL10n->get('SYS_VIEW') . ')</label>');
$page->addHtml('<div class="form-text">' . $gL10n->get('SYS_NOTE') . ': Activates a tab in the member profile and injects plugin templates into the profile view.</div>');
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
