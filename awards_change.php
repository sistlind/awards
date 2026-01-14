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

use Admidio\Infrastructure\Entity\Entity;
use Admidio\Users\Entity\User;

require_once(__DIR__ . '/awards_common.php');


// Berechtigung checken
if (!$gCurrentUser->isAdministratorUsers()) {
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$getAwardID = admFuncVariableIsValid($_GET, 'awa_id', 'numeric', array('defaultValue' => 0));

$EditMode = ($getAwardID > 0);

$gNavigation->addUrl(CURRENT_URL);

if ($EditMode) {
    $headline = $gL10n->get('AWA_HEADLINE_CHANGE');
} else {
    $headline = $gL10n->get('AWA_HEADLINE');
}

$page = new HtmlPage('plg-awards-change', $headline);

// Begin der Seite
// Admidio v5 uses built-in date picker, no need to add external CSS/JS files


// Falls Datenbank nicht vorhanden Install-Skript starten
if (!isAwardsDbInstalled()) {
    // Datenbank nicht vorhanden
    $page->addHtml('<h2>' . $gL10n->get('SYS_ERROR') . '</h2>');
    $page->addHtml($gL10n->get('AWA_ERR_NO_DB'));
    $page->addHtml('<p><a href="awards_install.php">' . $gL10n->get('AWA_INSTALL') . '</a></p>');
    $page->show();
    return;
}

if ($EditMode && !isset($_POST['submit'])) {
    // Use Entity class instead of TableAccess (Admidio v5)
    $AWAObj = new Entity($gDb, TBL_USER_AWARDS, 'awa', $getAwardID);
    $POST_award_user_id = $AWAObj->getValue('awa_usr_id');
    $POST_award_cat_id = $AWAObj->getValue('awa_cat_id');
    $POST_award_name_new = $AWAObj->getValue('awa_name');
    $POST_award_info = $AWAObj->getValue('awa_info');
    $DateObject = date_create($AWAObj->getValue('awa_date'));
    $POST_award_date = date_format($DateObject, 'd.m.Y');
} else {
    // Übergebene POST_variablen speichern
    $POST_award_new_id = admFuncVariableIsValid($_POST, 'award_new_id', 'numeric', array('defaultValue' => 0));
    $POST_award_user_id = admFuncVariableIsValid($_POST, 'award_user_id', 'numeric', array('defaultValue' => 0));
    $POST_award_role_id = admFuncVariableIsValid($_POST, 'award_role_id', 'numeric', array('defaultValue' => 0));
    $POST_award_leader = admFuncVariableIsValid($_POST, 'award_leader', 'numeric', array('defaultValue' => 0));
    $POST_award_cat_id = admFuncVariableIsValid($_POST, 'award_cat_id', 'numeric', array('defaultValue' => 0));
    $POST_award_name_old_id = admFuncVariableIsValid($_POST, 'award_name_old_id', 'numeric', array('defaultValue' => 0));
    $POST_award_name_new = admFuncVariableIsValid($_POST, 'award_name_new', 'string', array('defaultValue' => ''));
    $POST_award_info = admFuncVariableIsValid($_POST, 'award_info', 'string', array('defaultValue' => ''));
    $POST_award_date = admFuncVariableIsValid($_POST, 'award_date', 'string', array('defaultValue' => ''));
    $DateObject = date_create($POST_award_date);
    $InternalDate = $DateObject ? date_format($DateObject, 'Y-m-d') : '';
}

if (isset($POST_award_name_old_id) && ($POST_award_name_old_id > 0)) {
    $sql = 'SELECT awa_name FROM ' . TBL_USER_AWARDS . ' WHERE awa_id = ?';
    $result = $gDb->queryPrepared($sql, array($POST_award_name_old_id))->fetch();
    $POST_award_name_old_name = $result['awa_name'];
}


if ($plg_debug_enabled == 1) { // Debug Teil 2!
    echo '<br>role_enabled: ' . $plg_role_enabled;
    echo '<br>leader_checked: ' . $plg_leader_checked;
    echo '<br>cat_id: ' . $plg_cat_id;
    echo '<br>award new id: ' . ($POST_award_new_id ?? '');
    echo '<br>userid: ' . ($POST_award_user_id ?? '');
    echo '<br>rolid: ' . ($POST_award_role_id ?? '');
    echo '<br>leader: ' . ($POST_award_leader ?? '');
    echo '<br>catid: ' . ($POST_award_cat_id ?? '');
    echo '<br>nameoldid: ' . ($POST_award_name_old_id ?? '');
    echo '<br>namenew: ' . ($POST_award_name_new ?? '');
    echo '<br>info: ' . ($POST_award_info ?? '');
    echo '<br>date: ' . ($POST_award_date ?? '');
    echo '<br>date_internal: ' . ($InternalDate ?? '');
}


// Letzte ID der Datenbank merken um doppelte Einträge zu verhindern
$sql = 'SELECT COUNT(*) AS count FROM ' . TBL_USER_AWARDS;
$result = $gDb->queryPrepared($sql)->fetch();
if ($result['count'] == 0) {
    $newID = 1;
} else {
    $sql = 'SELECT MAX(awa_id) as maxID FROM ' . TBL_USER_AWARDS;
    $result = $gDb->queryPrepared($sql)->fetch();
    $newID = $result['maxID'] + 1;
}

if (isset($_POST['submit'])) {
    $INPUTOK = true;
    $ErrorStr = '<h2>' . $gL10n->get('SYS_ERROR') . '</h2>';
    
    // Eingaben OK?
    if (($POST_award_new_id != $newID) && !$EditMode) {
        // Doppelter Aufruf?
        $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_DOUBLE_ID') . '</span></p>';
        $INPUTOK = false;
    }
    
    if ($plg_role_enabled == 1) {
        if (($POST_award_user_id == 0) && ($POST_award_role_id == 0)) {
            // Mitglied oder Rolle Pflicht!
            $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_NO_USER_OR_ROLE') . '</span></p>';
            $INPUTOK = false;
        }
        if (($POST_award_user_id > 0) && ($POST_award_role_id > 0)) {
            // Rolle oder Mitglied - nicht beides!
            $POST_award_user_id = '';
            $POST_award_role_id = '';
            $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_USER_OR_ROLE') . '</span></p>';
            $INPUTOK = false;
        }
    } else {
        if ($POST_award_user_id == 0) {
            // Name Pflicht!
            $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_NO_USER') . '</span></p>';
            $INPUTOK = false;
        }
    }
    
    if ($POST_award_cat_id == 0) {
        // Kategorie Pflicht!
        $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_NO_CAT') . '</span></p>';
        $INPUTOK = false;
    }
    
    if ((strlen($POST_award_name_new) > 0) && ($POST_award_name_old_id > 0)) {
        // Nur ein Titelfeld füllen!
        $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_DOUBLE_TITLE') . '</span></p>';
        $INPUTOK = false;
    }
    
    if ((strlen($POST_award_name_new) < 1) && ($POST_award_name_old_id == 0)) {
        // Titel Pflicht
        $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_NO_TITLE') . '</span></p>';
        $INPUTOK = false;
    }
    
    if (strlen($POST_award_date) < 4 || empty($InternalDate)) {
        // Datum Pflicht! (must be a valid date)
        $ErrorStr .= '<p><span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_ERR_NO_DATE') . '</span></p>';
        $INPUTOK = false;
    }
    if ($INPUTOK) {
        // User übergeben --> Award für User speichern
        if ($POST_award_user_id > 0) {
            if ($EditMode) {
                // Use Entity class instead of TableAccess (Admidio v5)
                $NewAWAObj = new Entity($gDb, TBL_USER_AWARDS, 'awa', $getAwardID);
            } else {
                $NewAWAObj = new Entity($gDb, TBL_USER_AWARDS, 'awa');
            }
            $NewAWAObj->setValue('awa_cat_id', $POST_award_cat_id);
            $NewAWAObj->setValue('awa_org_id', $gCurrentOrganization->getValue('org_id'));
            $NewAWAObj->setValue('awa_usr_id', $POST_award_user_id);
            
            if ($POST_award_name_old_id > 0) {
                $sql = 'SELECT awa_name FROM ' . TBL_USER_AWARDS . ' WHERE awa_id = ?';
                $result = $gDb->queryPrepared($sql, array($POST_award_name_old_id))->fetch();
                $NewAWAObj->setValue('awa_name', $result['awa_name']);
            } else {
                $NewAWAObj->setValue('awa_name', $POST_award_name_new);
            }
            $NewAWAObj->setValue('awa_info', $POST_award_info);
            $NewAWAObj->setValue('awa_date', $InternalDate);
            $NewAWAObj->save();
            
            $page->addHtml('<h2>' . $gL10n->get('AWA_SUCCESS') . '</h2>');
            if (!$EditMode) {
                $page->addHtml('<p><span class="text-success"><i class="bi bi-check-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_SUCCESS_NEW') . '</span></p>');
                $page->addHtml('<h2>' . $gL10n->get('AWA_NEXT_ENTRY') . '</h2>');
                unset($POST_award_user_id);
                $newID += 1;
            } else {
                $page->addHtml('<p><span class="text-success"><i class="bi bi-check-circle-fill"></i>&nbsp;' . $gL10n->get('AWA_SUCCESS_CHANGE') . '</span></p>');
                $page->addHtml('<h2>' . $gL10n->get('AWA_NEXT_ENTRY') . '</h2>');
            }
        }

        // Rolle übergeben --> FÜR JEDEN USER DER ROLLE EINTRAGEN
        if ($POST_award_role_id > 0) {
            $recordCount = 0;
            // SQL-String - Alle USER-IDs zur Rolle aus der Datenbank finden ohne Leiter der Rolle
            $sql = 'SELECT mem_usr_id
                FROM ' . TBL_MEMBERS . '
                WHERE ' . TBL_MEMBERS . '.mem_rol_id = ?
                AND ' . TBL_MEMBERS . '.mem_begin <= ?
                AND ' . TBL_MEMBERS . '.mem_end >= ?';
            $queryParams = array($POST_award_role_id, DATE_NOW, DATE_NOW);

            if ($POST_award_leader != 1) {
                // ohne leiter
                $sql .= ' AND ' . TBL_MEMBERS . '.mem_leader = 0';
            }

            $query = $gDb->queryPrepared($sql, $queryParams);
            while ($row = $query->fetch()) {
                $POST_award_user_id = $row['mem_usr_id'];

                $NewAWAObj = new Entity($gDb, TBL_USER_AWARDS, 'awa');
                $NewAWAObj->setValue('awa_cat_id', $POST_award_cat_id);
                $NewAWAObj->setValue('awa_org_id', $gCurrentOrganization->getValue('org_id'));
                $NewAWAObj->setValue('awa_usr_id', $POST_award_user_id);
                
                if ($POST_award_name_old_id > 0) {
                    $sql2 = 'SELECT awa_name FROM ' . TBL_USER_AWARDS . ' WHERE awa_id = ?';
                    $result = $gDb->queryPrepared($sql2, array($POST_award_name_old_id))->fetch();
                    $NewAWAObj->setValue('awa_name', $result['awa_name']);
                } else {
                    $NewAWAObj->setValue('awa_name', $POST_award_name_new);
                }
                $NewAWAObj->setValue('awa_info', $POST_award_info);
                $NewAWAObj->setValue('awa_date', $InternalDate);
                $NewAWAObj->save();
                $recordCount += 1;
                unset($POST_award_user_id);
                $newID += 1;
            }
            $POST_award_role_id = 0;
            $page->addHtml('<p><span class="text-success"><i class="bi bi-check-circle-fill"></i>&nbsp;' . $recordCount . ' ' . $gL10n->get('AWA_SUCCESS_NEW') . '</span></p>');
        }
    } else {
        $page->addHtml($ErrorStr);
    }
}

// Html des Modules ausgeben - Updated for Bootstrap 5 (Admidio v5)
$page->addHtml('<form action="' . ADMIDIO_URL . FOLDER_PLUGINS . '/' . $plugin_folder . '/awards_change.php?awa_id=' . $getAwardID . '" method="post">
<input type="hidden" name="award_new_id" value="' . $newID . '">
<div class="card" id="edit_awards_form">
    <div class="card-header">' . $gL10n->get('AWA_HEADLINE_CHANGE') . '</div>
    <div class="card-body">
        <div class="mb-3 row">
            <label for="award_user_id" class="col-sm-3 col-form-label">' . $gL10n->get('AWA_USER') . '<span class="text-danger" title="' . $gL10n->get('SYS_MANDATORY_FIELD') . '">*</span></label>
            <div class="col-sm-9">
                <select id="award_user_id" name="award_user_id" class="form-select">
                    <option value="0">' . $gL10n->get('AWA_USER_SELECT') . '</option>');
// Nutzer auswahl füllen
// only active members
$memberCondition = ' AND EXISTS 
    (SELECT 1
       FROM ' . TBL_MEMBERS . ', ' . TBL_ROLES . ', ' . TBL_CATEGORIES . '
      WHERE mem_usr_id = usr_id
        AND mem_rol_id = rol_id
        AND mem_begin <= ?
        AND mem_end > ?
        AND cat_name_intern <> \'CONFIRMATION_OF_PARTICIPATION\'
        AND rol_valid = 1
        AND rol_cat_id = cat_id
        AND (cat_org_id = ? OR cat_org_id IS NULL)) ';

$sql = 'SELECT usr_id, last_name.usd_value as last_name, first_name.usd_value as first_name, birthday.usd_value as birthday                  
         FROM ' . TBL_USERS . '
         JOIN ' . TBL_USER_DATA . ' as last_name
           ON last_name.usd_usr_id = usr_id
          AND last_name.usd_usf_id = ?
         JOIN ' . TBL_USER_DATA . ' as first_name
           ON first_name.usd_usr_id = usr_id
          AND first_name.usd_usf_id = ?
         LEFT JOIN ' . TBL_USER_DATA . ' as birthday
           ON birthday.usd_usr_id = usr_id
          AND birthday.usd_usf_id = ?
         WHERE usr_valid = 1' . $memberCondition . ' ORDER BY last_name.usd_value, first_name.usd_value';

$queryParams = array(
    $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
    $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
    $gProfileFields->getProperty('BIRTHDAY', 'usf_id'),
    DATE_NOW,
    DATE_NOW,
    $gCurrentOrganization->getValue('org_id')
);

$query = $gDb->queryPrepared($sql, $queryParams);
while ($row = $query->fetch()) {
    $selected = (isset($POST_award_user_id) && ($row['usr_id'] == $POST_award_user_id)) ? 'selected' : '';
    $page->addHtml('<option value="' . $row['usr_id'] . '" ' . $selected . '>' . $row['last_name'] . ', ' . $row['first_name'] . '  (' . ($row['birthday'] ?? '') . ')</option>');
}

if ($plg_role_enabled == 0) {
    $page->addHtml('</select></div></div>');
} else {
    // Wenn Rollen aktiv entsprechende Felder anzeigen
    $page->addHtml('</select></div></div>');

    if ($EditMode && !isset($_POST['submit'])) {
        $page->addHtml('<select id="award_role_id" name="award_role_id" class="form-select d-none" disabled>
                        <option value="0">' . $gL10n->get('AWA_ROLE_SELECT') . '</option></select>');
    } else {
        // Rollen auflisten
        $page->addHtml('<div class="mb-3 row">
            <label for="award_role_id" class="col-sm-3 col-form-label">' . $gL10n->get('AWA_ROLE') . '<span class="text-danger" title="' . $gL10n->get('SYS_MANDATORY_FIELD') . '">*</span></label>
            <div class="col-sm-9">');

        $page->addHtml('<select id="award_role_id" name="award_role_id" class="form-select">
                        <option value="0">' . $gL10n->get('AWA_ROLE_SELECT') . '</option>');

        $sql = 'SELECT rol_id, rol_name FROM ' . TBL_ROLES . ' WHERE rol_valid = 1';
        $queryParams = array();

        if ($plg_cat_id > 0) {
            // Nur Rollen aus bestimmter Kategorien auflisten
            $sql .= ' AND rol_cat_id = ?';
            $queryParams[] = $plg_cat_id;
        }

        $query = $gDb->queryPrepared($sql, $queryParams);
        while ($row = $query->fetch()) {
            $selected = ($row['rol_id'] == $POST_award_role_id) ? 'selected' : '';
            $page->addHtml('<option value="' . $row['rol_id'] . '" ' . $selected . '>' . $row['rol_name'] . '</option>');
        }

        $checked = ($plg_leader_checked == 1) ? 'checked' : '';
        $page->addHtml('</select>
            <div class="form-check mt-2">
                <input type="checkbox" class="form-check-input" name="award_leader" id="award_leader" value="1" ' . $checked . '>
                <label class="form-check-label" for="award_leader">' . $gL10n->get('AWA_LEADER') . '</label>
            </div>
        </div></div>');
    }
}
// Category selection
$page->addHtml('<div class="mb-3 row">
    <label for="award_cat_id" class="col-sm-3 col-form-label">' . $gL10n->get('AWA_CAT') . '<span class="text-danger" title="' . $gL10n->get('SYS_MANDATORY_FIELD') . '">*</span></label>
    <div class="col-sm-9">
        <select id="award_cat_id" name="award_cat_id" class="form-select">
            <option value="0">' . $gL10n->get('AWA_CAT_SELECT') . '</option>');

// Kategorie auswahl füllen
$sql = 'SELECT cat_id, cat_name FROM ' . TBL_CATEGORIES . ' WHERE cat_type = \'AWA\' AND cat_default = 1';
$query = $gDb->queryPrepared($sql);
$default_category = $query->fetch();

$sql = 'SELECT cat_id, cat_name FROM ' . TBL_CATEGORIES . ' WHERE cat_type = \'AWA\' ORDER BY cat_sequence';
$query = $gDb->queryPrepared($sql);
while ($row = $query->fetch()) {
    if ($row['cat_id'] == $POST_award_cat_id) {
        $selected = 'selected';
    } elseif (!isset($POST_award_cat_id) && isset($default_category['cat_id']) && ($row['cat_id'] == $default_category['cat_id'])) {
        $selected = 'selected';
    } else {
        $selected = '';
    }
    $page->addHtml('<option value="' . $row['cat_id'] . '" ' . $selected . '>' . $row['cat_name'] . '</option>');
}

$page->addHtml('</select></div></div>');
// Old title selection
$page->addHtml('<div class="mb-3 row">
    <label for="award_name_old_id" class="col-sm-3 col-form-label">' . $gL10n->get('AWA_HONOR_OLD') . '<span class="text-danger" title="' . $gL10n->get('SYS_MANDATORY_FIELD') . '">*</span></label>
    <div class="col-sm-9">
        <select id="award_name_old_id" name="award_name_old_id" class="form-select">
            <option value="0">' . $gL10n->get('AWA_HONOR_OLD_SELECT') . '</option>
            <option value="0">-------------------</option>');

// Dropdown für alte Einträge füllen
$sql = 'SELECT awa_name, awa_id FROM ' . TBL_USER_AWARDS . ' ORDER BY awa_name ASC';
$query = $gDb->queryPrepared($sql);
$awardoldnames = array();

if ($query !== false) {
    while ($sqlrow = $query->fetch()) {
        $skip = false;
        foreach ($awardoldnames as $entry) {
            if ($sqlrow['awa_name'] == $entry['awa_name']) {
                $skip = true;
                break;
            }
        }
        if (!$skip) {
            $awardoldnames[] = $sqlrow;
        }
    }
}

if (count($awardoldnames) > 0) {
    foreach ($awardoldnames as $row) {
        $selected = (isset($POST_award_name_old_name) && $row['awa_name'] == $POST_award_name_old_name) ? 'selected' : '';
        $page->addHtml('<option value="' . $row['awa_id'] . '" ' . $selected . '>' . $row['awa_name'] . '</option>');
    }
}
unset($awardoldnames);

$page->addHtml('</select></div></div>');
// New title input
$page->addHtml('<div class="mb-3 row">
    <label for="award_name_new" class="col-sm-3 col-form-label">' . $gL10n->get('AWA_HONOR_NEW') . '<span class="text-danger" title="' . $gL10n->get('SYS_MANDATORY_FIELD') . '">*</span></label>
    <div class="col-sm-9">
        <input type="text" class="form-control" id="award_name_new" name="award_name_new" maxlength="100" value="' . ($POST_award_name_new ?? '') . '">
    </div>
</div>');

// Info input
$page->addHtml('<div class="mb-3 row">
    <label for="award_info" class="col-sm-3 col-form-label">' . $gL10n->get('AWA_HONOR_INFO') . '</label>
    <div class="col-sm-9">
        <input type="text" class="form-control" id="award_info" name="award_info" maxlength="100" value="' . ($POST_award_info ?? '') . '">
    </div>
</div>');

// Date input - use HTML5 date input for better cross-browser support
$page->addHtml('<div class="mb-3 row">
    <label for="award_date" class="col-sm-3 col-form-label">' . $gL10n->get('AWA_HONOR_DATE') . '<span class="text-danger" title="' . $gL10n->get('SYS_MANDATORY_FIELD') . '">*</span></label>
    <div class="col-sm-9">
        <input type="text" class="form-control" id="award_date" name="award_date" maxlength="10" value="' . ($POST_award_date ?? '') . '" placeholder="dd.mm.yyyy">
        <div class="form-text">' . $gL10n->get('AWA_HONOR_DATE_FORMAT') . '</div>
    </div>
</div>');

// Submit button
$page->addHtml('<div class="form-group row">
    <div class="col-sm-9 offset-sm-3">
        <button id="btnSave" type="submit" name="submit" value="submit" class="btn btn-primary"><i class="bi bi-save"></i>&nbsp;' . $gL10n->get('SYS_SAVE') . '</button>
    </div>
</div>
    </div>
</div>
</form>');

$page->show();
