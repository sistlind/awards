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


require_once(__DIR__ . '/awards_common.php');

// Navigation faengt hier im Modul an
$gNavigation->clear();
$gNavigation->addUrl(CURRENT_URL);

if ($gCurrentUser->isAdministrator() == false) {
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Set headline and load template
$headline = $gL10n->get('AWA_HEADLINE');
$page = new HtmlPage('plg-awards-install', $headline);
$page->addHtml('<h2>' . $gL10n->get('AWA_INSTALL_HEADLINE') . '</h2>');

// Prüfen ob Datenbank vorhanden
if (isAwardsDbInstalled()) {
    // Datenbank vorhanden
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_DB_EXISTS') . '</p>');
} else {
    // Datenbank nicht vorhanden
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_DB_NOT_READY', array(TBL_USER_AWARDS)) . '</p>');
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_CREATE_DB') . '</p>');

    $sql = 'CREATE TABLE ' . TBL_USER_AWARDS . '
    (awa_id int(10) unsigned NOT NULL AUTO_INCREMENT,
      awa_cat_id int(10) unsigned DEFAULT NULL,
      awa_org_id int(10) unsigned DEFAULT NULL,
      awa_usr_id int(10) unsigned DEFAULT NULL,
      awa_name varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
      awa_info varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
      awa_date date DEFAULT NULL,
      awa_usr_id_create int(10) unsigned DEFAULT NULL,
      awa_timestamp_create timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      awa_usr_id_change int(10) unsigned DEFAULT NULL,
      awa_timestamp_change timestamp NULL DEFAULT NULL,
      PRIMARY KEY (awa_id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';

    $gDb->queryPrepared($sql);
    $page->addHtml('<h2>' . $gL10n->get('AWA_SUCCESS') . '</h2>');
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_DB_READY') . '</p>');
}

$page->show();
