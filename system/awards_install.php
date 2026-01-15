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


require_once(__DIR__ . '/../awards_common.php');

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

$awardsTableExists = isAwardsDbInstalled();
$configTableName = TABLE_PREFIX . '_awards_config';
$configTableExists = $gDb->tableExists($configTableName);

// Check if this is a fresh install or an update
if (!$awardsTableExists && !$configTableExists) {
    // Fresh installation - create both tables
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_DB_NOT_READY', array(TBL_USER_AWARDS)) . '</p>');
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_CREATE_DB') . '</p>');

    // Create awards table
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

    // Create config table
    $sql = 'CREATE TABLE ' . $configTableName . ' (
        cfg_id INT AUTO_INCREMENT PRIMARY KEY,
        cfg_org_id INT NOT NULL,
        cfg_name VARCHAR(100) NOT NULL,
        cfg_value TEXT,
        cfg_type VARCHAR(20) NOT NULL DEFAULT \'string\',
        INDEX idx_org_name (cfg_org_id, cfg_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
    $gDb->queryPrepared($sql);

    // Initialize default jubilee years
    $defaultYears = json_encode(array(25, 40, 50, 60, 70));
    $sql = 'INSERT INTO ' . $configTableName . ' 
            (cfg_org_id, cfg_name, cfg_value, cfg_type) 
            VALUES (?, ?, ?, ?)';
    $gDb->queryPrepared($sql, array($gCurrentOrganization->getValue('org_id'), 'jubilee_years', $defaultYears, 'json'));

    $page->addHtml('<h2>' . $gL10n->get('AWA_SUCCESS') . '</h2>');
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_DB_READY') . '</p>');
    $page->addHtml('<p>Configuration table created with default jubilee years: 25, 40, 50, 60, 70</p>');
} elseif ($awardsTableExists && !$configTableExists) {
    // Update from older version - only create config table
    $page->addHtml('<h2>Update Installation</h2>');
    $page->addHtml('<p>Awards table exists. Creating configuration table for version 5.0.0...</p>');

    // Create config table
    $sql = 'CREATE TABLE ' . $configTableName . ' (
        cfg_id INT AUTO_INCREMENT PRIMARY KEY,
        cfg_org_id INT NOT NULL,
        cfg_name VARCHAR(100) NOT NULL,
        cfg_value TEXT,
        cfg_type VARCHAR(20) NOT NULL DEFAULT \'string\',
        INDEX idx_org_name (cfg_org_id, cfg_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
    $gDb->queryPrepared($sql);

    // Initialize default jubilee years
    $defaultYears = json_encode(array(25, 40, 50, 60, 70));
    $sql = 'INSERT INTO ' . $configTableName . ' 
            (cfg_org_id, cfg_name, cfg_value, cfg_type) 
            VALUES (?, ?, ?, ?)';
    $gDb->queryPrepared($sql, array($gCurrentOrganization->getValue('org_id'), 'jubilee_years', $defaultYears, 'json'));

    $page->addHtml('<h2>' . $gL10n->get('AWA_SUCCESS') . '</h2>');
    $page->addHtml('<p>Configuration table created successfully!</p>');
    $page->addHtml('<p>Default jubilee years configured: 25, 40, 50, 60, 70</p>');
    $page->addHtml('<p>You can now use the Jubilee Report feature and configure jubilee years in the main plugin page.</p>');
} else {
    // Both tables exist - installation is complete
    $page->addHtml('<h2>Installation Status</h2>');
    $page->addHtml('<p>' . $gL10n->get('AWA_INSTALL_DB_EXISTS') . '</p>');
    $page->addHtml('<p>Configuration table exists.</p>');
    $page->addHtml('<p><strong>Installation is already complete!</strong></p>');
}

$page->show();
