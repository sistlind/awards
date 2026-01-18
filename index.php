<?php
/**
 ***********************************************************************************************
 * Awards Plugin - Main Entry Point
 *
 * Version 5.0.0
 *
 * This plugin assigns awards/honors/achievements to members
 *
 * Compatible with Admidio version 5.03+
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;

try {
    require_once(__DIR__ . '/../../system/common.php');
    require_once(__DIR__ . '/awards_common.php');

    if (!$gCurrentUser->isAdministratorUsers()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $gNavigation->addStartUrl(CURRENT_URL);

    // Get the active tab from URL parameter
    $getTab = admFuncVariableIsValid($_GET, 'tab', 'string', array('defaultValue' => 'manage', 'validValues' => array('manage', 'list')));

    $headline = $gL10n->get('AWA_HEADLINE');

    // Create page object
    $page = new HtmlPage('plg-awards-main', $headline);

    // Add tab navigation
    $manageUrl = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php', array('tab' => 'manage'));
    $listUrl = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php', array('tab' => 'list'));

    $page->addHtml('
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link' . ($getTab === 'manage' ? ' active' : '') . '" href="' . $manageUrl . '">
                <i class="bi bi-award-fill"></i> ' . $gL10n->get('AWA_AWARDS_MANAGEMENT') . '
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link' . ($getTab === 'list' ? ' active' : '') . '" href="' . $listUrl . '">
                <i class="bi bi-list-ul"></i> ' . $gL10n->get('AWA_LIST_AWARDS') . '
            </a>
        </li>
    </ul>
    ');

    // Include content based on active tab
    switch ($getTab) {
        case 'manage':
            // Manage awards - show quick links
            $page->addHtml('<div class="row">');
            $page->addHtml('<div class="col-lg-6 col-md-8 col-sm-12">');
            $page->addHtml('<div class="card mb-3">');
            $page->addHtml('<div class="card-header"><i class="bi bi-award-fill"></i> ' . $gL10n->get('AWA_AWARDS_MANAGEMENT') . '</div>');
            $page->addHtml('<div class="card-body">');
            $page->addHtml('<p><a href="' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/awards_change.php" class="btn btn-primary btn-lg w-100 mb-2">');
            $page->addHtml('<i class="bi bi-award"></i> ' . $gL10n->get('AWA_HONOR') . '</a></p>');
            $page->addHtml('<p><a href="' . ADMIDIO_URL . FOLDER_MODULES . '/categories.php?type=AWA" class="btn btn-secondary w-100">');
            $page->addHtml('<i class="bi bi-tag"></i> ' . $gL10n->get('AWA_CAT_EDIT') . '</a></p>');
            $page->addHtml('</div></div></div>');
            $page->addHtml('</div>'); // row
            break;
            
        case 'list':
            // Redirect to awards_show.php which has all export/filter logic
            admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/awards_show.php');
            break;
    }

    $page->show();
    
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

