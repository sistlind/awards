<?php
/**
 ***********************************************************************************************
 * Awards Plugin - Preferences
 *
 * Manage plugin settings including jubilee years
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Plugins\Awards\classes\Config\ConfigTable;
use Admidio\Infrastructure\Utils\SecurityUtils;

require_once(__DIR__ . '/../awards_common.php');

// Only administrators can access
if (!$gCurrentUser->isAdministratorUsers()) {
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Handle AJAX requests for adding/removing years
$getAction = admFuncVariableIsValid($_GET, 'action', 'string');
$getYear = admFuncVariableIsValid($_GET, 'year', 'int');

$config = new ConfigTable();
$config->read();

if ($getAction === 'add' && $getYear > 0) {
    try {
        error_log('Awards Plugin Preferences: Attempting to add year ' . $getYear);
        $yearsBefore = $config->getJubileeYears();
        $result = $config->addJubileeYear($getYear);
        $yearsAfter = $config->getJubileeYears();
        
        $debugMsg = 'DEBUG ADD: Year=' . $getYear . ', Result=' . ($result ? 'true' : 'false') . 
                    ', Before=[' . implode(',', $yearsBefore) . '], After=[' . implode(',', $yearsAfter) . ']';
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'success', 'message' => $gL10n->get('AWA_YEAR_ADDED') . ' | ' . $debugMsg));
        } else {
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'error', 'message' => $gL10n->get('AWA_YEAR_EXISTS') . ' | ' . $debugMsg));
        }
    } catch (Exception $e) {
        error_log('Awards Plugin Preferences: Exception adding year - ' . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => 'Error: ' . $e->getMessage()));
    }
    exit();
}

if ($getAction === 'remove' && $getYear > 0) {
    try {
        error_log('Awards Plugin Preferences: Attempting to remove year ' . $getYear);
        $yearsBefore = $config->getJubileeYears();
        $result = $config->removeJubileeYear($getYear);
        $yearsAfter = $config->getJubileeYears();
        
        $debugMsg = 'DEBUG REMOVE: Year=' . $getYear . ', Result=' . ($result ? 'true' : 'false') . 
                    ', Before=[' . implode(',', $yearsBefore) . '], After=[' . implode(',', $yearsAfter) . ']';
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'success', 'message' => $gL10n->get('AWA_YEAR_REMOVED') . ' | ' . $debugMsg));
        } else {
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'error', 'message' => $gL10n->get('AWA_YEAR_NOT_FOUND') . ' | ' . $debugMsg));
        }
    } catch (Exception $e) {
        error_log('Awards Plugin Preferences: Exception removing year - ' . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => 'Error: ' . $e->getMessage()));
    }
    exit();
}

// Display preferences page
$headline = $gL10n->get('AWA_PREFERENCES');
$gNavigation->addUrl(CURRENT_URL, $headline);

$page = new HtmlPage('plg-awards-preferences', $headline);

// Add JavaScript for dynamic year management
$page->addJavascript('
    window.addYear = function() {
        const yearInput = document.getElementById("new_year");
        const year = parseInt(yearInput.value);
        
        if (!year || year < 1 || year > 150) {
            alert("' . $gL10n->get('AWA_YEAR_INVALID') . '");
            return;
        }
        
        fetch("' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php?action=add&year=" + year)
            .then(response => {
                if (!response.ok) {
                    throw new Error("HTTP error " + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success") {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Error adding year: " + error.message);
            });
    };
    
    window.removeYear = function(year) {
        fetch("' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php?action=remove&year=" + year)
            .then(response => {
                if (!response.ok) {
                    throw new Error("HTTP error " + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success") {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Error removing year: " + error.message);
            });
    };
');

// Add back button to menu
$page->addPageFunctionsMenuItem('menu_item_back', $gL10n->get('SYS_BACK'), 
    ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php', 'bi-arrow-left-circle');

$page->addHtml('<div class="card">');
$page->addHtml('<div class="card-header"><i class="bi bi-gear-fill"></i> ' . $gL10n->get('AWA_JUBILEE_YEARS_CONFIG') . '</div>');
$page->addHtml('<div class="card-body">');

$page->addHtml('<p>' . $gL10n->get('AWA_JUBILEE_YEARS_DESC') . '</p>');

// Display current jubilee years
$jubileeYears = $config->getJubileeYears();

$page->addHtml('<h5>' . $gL10n->get('AWA_CURRENT_JUBILEE_YEARS') . '</h5>');
$page->addHtml('<div class="mb-3">');

if (empty($jubileeYears)) {
    $page->addHtml('<p class="text-muted">' . $gL10n->get('AWA_NO_YEARS_CONFIGURED') . '</p>');
} else {
    foreach ($jubileeYears as $year) {
        $page->addHtml('
            <span class="badge bg-primary me-2 mb-2" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                ' . $year . ' ' . $gL10n->get('AWA_YEARS') . '
                <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.7rem;" 
                        onclick="removeYear(' . $year . ')" aria-label="' . $gL10n->get('SYS_DELETE') . '"></button>
            </span>
        ');
    }
}

$page->addHtml('</div>');

// Add new year form
$page->addHtml('<h5>' . $gL10n->get('AWA_ADD_JUBILEE_YEAR') . '</h5>');
$page->addHtml('<div class="input-group mb-3" style="max-width: 400px;">');
$page->addHtml('<input type="number" class="form-control" id="new_year" placeholder="' . $gL10n->get('AWA_ENTER_YEAR_NUMBER') . '" min="1" max="150" />');
$page->addHtml('<button class="btn btn-primary" type="button" onclick="addYear()">');
$page->addHtml('<i class="bi bi-plus-circle"></i> ' . $gL10n->get('AWA_ADD_YEAR'));
$page->addHtml('</button>');
$page->addHtml('</div>');

$page->addHtml('<p class="text-muted small">' . $gL10n->get('AWA_YEAR_EXAMPLE') . '</p>');

$page->addHtml('</div>'); // card-body
$page->addHtml('</div>'); // card

$page->show();
