<?php
/******************************************************************************
 * Awards - Jubilee Report
 *
 * Show members with long-term membership (configurable years)
 * 
 * Compatible with Admidio v5.03+
 *                  
 *****************************************************************************/

use Admidio\Users\Entity\User;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Plugins\Awards\classes\Config\ConfigTable;

require_once(__DIR__ . '/../awards_common.php');

if (!$gCurrentUser->isAdministratorUsers()) {
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Get parameters
$get_req = admFuncVariableIsValid($_GET, 'export_mode', 'string', array('defaultValue' => 'html', 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf')));
$getRoleUuid = admFuncVariableIsValid($_GET, 'role_uuid', 'uuid');

// Load configurable jubilee years
$config = new ConfigTable();
$config->read();
$jubileeYears = $config->getJubileeYears();

// Define title and headline
$title = $gL10n->get('AWA_HEADLINE') . ' - ' . $gL10n->get('AWA_JUBILEE_REPORT');
$headline = $gL10n->get('AWA_JUBILEE_REPORT');

// Set navigation only for HTML mode to clear breadcrumbs
if ($get_req == 'html') {
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-award');
}

// Initialize export parameters
$separator = '';
$valueQuotes = '';
$charset = '';
$classTable = '';

switch ($get_req) {
    case 'csv-ms':
        $separator = ';';
        $valueQuotes = '"';
        $get_req = 'csv';
        $charset = 'iso-8859-1';
        break;
    case 'csv-oo':
        $separator = ',';
        $valueQuotes = '"';
        $get_req = 'csv';
        $charset = 'utf-8';
        break;
    case 'pdf':
        $classTable = 'table';
        $get_req = 'pdf';
        break;
    case 'html':
        $classTable = 'table table-sm table-hover';
        break;
    case 'print':
        $classTable = 'table table-sm table-striped';
        break;
}

$CSVstr = '';

// if html mode and last url was not this page then save to navigation stack
if ($get_req == 'html' && strpos($gNavigation->getUrl(), 'awards_jubilee.php') === false) {
    $gNavigation->addUrl(CURRENT_URL);
}

$page = new HtmlPage('plg-awards-jubilee', $headline);

if ($get_req != 'csv') {
    $datatable = false;
    $hoverRows = false;

    if ($get_req == 'print') {
        $page->setInlineMode();
        $page->setPrintMode();
        $page->setTitle($title);
        $table = new HtmlTable('adm_jubilee_table', $page, $hoverRows, $datatable, $classTable);
    } elseif ($get_req == 'pdf') {
        // TCPDF via Composer autoload
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Admidio');
        $pdf->setTitle($title);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(false);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetHeaderData('', 0, $headline, '');
        $pdf->SetFont('times', '', 10);
        $pdf->AddPage();
        $table = new HtmlTable('adm_jubilee_table', $page, $hoverRows, $datatable, $classTable);
        $table->addAttribute('border', '1');
    } elseif ($get_req == 'html') {
        $datatable = true;
        $hoverRows = true;
        $page->setTitle($title);

        // Menu items
        $page->addPageFunctionsMenuItem('menu_item_back', $gL10n->get('SYS_BACK'), 
            ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php', 'bi-arrow-left-circle');
        
        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 
            ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder . '/system/awards_jubilee.php?role_uuid=' . $getRoleUuid . '&export_mode=print', 'bi-printer');

        // Export dropdown
        $page->addPageFunctionsMenuItem('jubilee_lists_export', $gL10n->get('AWA_EXPORT'), '#', 'bi-download');
        $page->addPageFunctionsMenuItem('jubilee_lists_csv_ms', $gL10n->get('SYS_MICROSOFT_EXCEL'), 
            ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder . '/system/awards_jubilee.php?role_uuid=' . $getRoleUuid . '&export_mode=csv-ms', 'bi-file-earmark-excel', 'jubilee_lists_export');
        $page->addPageFunctionsMenuItem('jubilee_lists_pdf', $gL10n->get('SYS_PDF'), 
            ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder . '/system/awards_jubilee.php?role_uuid=' . $getRoleUuid . '&export_mode=pdf', 'bi-file-earmark-pdf', 'jubilee_lists_export');
        $page->addPageFunctionsMenuItem('jubilee_lists_csv', $gL10n->get('SYS_CSV') . ' (' . $gL10n->get('SYS_UTF8') . ')', 
            ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder . '/system/awards_jubilee.php?role_uuid=' . $getRoleUuid . '&export_mode=csv-oo', 'bi-file-earmark-text', 'jubilee_lists_export');

        $table = new HtmlTable('adm_jubilee_table', $page, $hoverRows, $datatable, $classTable);
    } else {
        $table = new HtmlTable('adm_jubilee_table', $page, $hoverRows, $datatable, $classTable);
    }
}

// Function to get members for a specific jubilee year
function getJubileeMembers($jubileeYear, $getRoleUuid, $gDb, $gCurrentOrganization, $gProfileFields) {
    // Use DateTime diff like BirthdayList plugin to calculate accurate years
    // Use ACCESSION profile field (Beitrittsdatum) instead of mem_begin
    
    // Find the ACCESSION field ID for the current organization
    $accessionFieldName = 'ACCESSION' . $gCurrentOrganization->getValue('org_id');
    $accessionFieldId = $gProfileFields->getProperty($accessionFieldName, 'usf_id');
    
    if (!$accessionFieldId) {
        // Fallback: try without org_id suffix
        $accessionFieldId = $gProfileFields->getProperty('ACCESSION', 'usf_id');
    }
    
    // If still no ACCESSION field found, return empty array
    if (!$accessionFieldId) {
        return array();
    }
    
    $sql = 'SELECT usr.usr_id,
                   usr.usr_uuid,
                   last_name.usd_value as last_name,
                   first_name.usd_value as first_name,
                   accession.usd_value as accession_date,
                   address.usd_value as address,
                   postcode.usd_value as postcode,
                   city.usd_value as city
              FROM ' . TBL_USERS . ' usr
         LEFT JOIN ' . TBL_USER_DATA . ' as last_name
                ON last_name.usd_usr_id = usr.usr_id
               AND last_name.usd_usf_id = ?
         LEFT JOIN ' . TBL_USER_DATA . ' as first_name
                ON first_name.usd_usr_id = usr.usr_id
               AND first_name.usd_usf_id = ?
         LEFT JOIN ' . TBL_USER_DATA . ' as accession
                ON accession.usd_usr_id = usr.usr_id
               AND accession.usd_usf_id = ?
         LEFT JOIN ' . TBL_USER_DATA . ' as address
                ON address.usd_usr_id = usr.usr_id
               AND address.usd_usf_id = ?
         LEFT JOIN ' . TBL_USER_DATA . ' as postcode
                ON postcode.usd_usr_id = usr.usr_id
               AND postcode.usd_usf_id = ?
         LEFT JOIN ' . TBL_USER_DATA . ' as city
                ON city.usd_usr_id = usr.usr_id
               AND city.usd_usf_id = ?
             WHERE usr.usr_valid = true
               AND accession.usd_value IS NOT NULL
               AND accession.usd_value != \'\'
               AND EXISTS (
                   SELECT 1 FROM ' . TBL_MEMBERS . ' mem
                   INNER JOIN ' . TBL_ROLES . ' rol ON rol.rol_id = mem.mem_rol_id
                   WHERE mem.mem_usr_id = usr.usr_id
                     AND mem.mem_begin <= ?
                     AND mem.mem_end >= ?
                     AND rol.rol_valid = true
                     AND rol.rol_cat_id IN (
                         SELECT cat_id FROM ' . TBL_CATEGORIES . ' 
                         WHERE (cat_org_id = ' . $gCurrentOrganization->getValue('org_id') . ' 
                            OR cat_org_id IS NULL)
                     )';

    $queryParams = array(
        $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
        $accessionFieldId,
        $gProfileFields->getProperty('ADDRESS', 'usf_id'),
        $gProfileFields->getProperty('POSTCODE', 'usf_id'),
        $gProfileFields->getProperty('CITY', 'usf_id'),
        DATE_NOW,
        DATE_NOW
    );

    if ($getRoleUuid !== '') {
        $sql .= ' AND rol.rol_uuid = ?';
        $queryParams[] = $getRoleUuid;
    }

    $sql .= ')
          ORDER BY last_name, first_name';

    $query = $gDb->queryPrepared($sql, $queryParams);
    
    // Filter members by calculating years based on year difference only
    // Awards are presented once a year for the whole year, so month/day doesn't matter
    $members = array();
    $currentYear = (int)date('Y');
    
    while ($row = $query->fetch()) {
        if ($row['accession_date']) {
            try {
                $dateBegin = new DateTime($row['accession_date']);
                $joinYear = (int)$dateBegin->format('Y');
                
                // Calculate years based on year difference only
                $years = $currentYear - $joinYear;
                
                if ($years == $jubileeYear) {
                    $row['mem_begin'] = $row['accession_date']; // Use accession date as display date
                    $row['years_member'] = $years;
                    $members[] = $row;
                }
            } catch (Exception $e) {
                // Skip invalid dates
                continue;
            }
        }
    }
    
    return $members;
}

// Generate output for CSV/PDF
if ($get_req == 'csv') {
    $CSVstr = '';
    $CSVstr .= $valueQuotes . $gL10n->get('AWA_JUBILEE_YEARS') . $valueQuotes . $separator;
    $CSVstr .= $valueQuotes . $gL10n->get('SYS_LASTNAME') . $valueQuotes . $separator;
    $CSVstr .= $valueQuotes . $gL10n->get('SYS_FIRSTNAME') . $valueQuotes . $separator;
    $CSVstr .= $valueQuotes . $gL10n->get('SYS_STREET') . $valueQuotes . $separator;
    $CSVstr .= $valueQuotes . $gL10n->get('SYS_POSTCODE') . $valueQuotes . $separator;
    $CSVstr .= $valueQuotes . $gL10n->get('SYS_CITY') . $valueQuotes . $separator;
    $CSVstr .= $valueQuotes . $gL10n->get('AWA_MEMBER_SINCE') . $valueQuotes . $separator;
    $CSVstr .= $valueQuotes . $gL10n->get('AWA_JUBILEE_YEARS') . $valueQuotes . "\n";

    foreach ($jubileeYears as $years) {
        $members = getJubileeMembers($years, $getRoleUuid, $gDb, $gCurrentOrganization, $gProfileFields);
        
        foreach ($members as $row) {
            $CSVstr .= $valueQuotes . $years . ' ' . $gL10n->get('AWA_YEARS') . $valueQuotes . $separator;
            $CSVstr .= $valueQuotes . $row['last_name'] . $valueQuotes . $separator;
            $CSVstr .= $valueQuotes . $row['first_name'] . $valueQuotes . $separator;
            $CSVstr .= $valueQuotes . ($row['address'] ?? '') . $valueQuotes . $separator;
            $CSVstr .= $valueQuotes . ($row['postcode'] ?? '') . $valueQuotes . $separator;
            $CSVstr .= $valueQuotes . ($row['city'] ?? '') . $valueQuotes . $separator;
            $CSVstr .= $valueQuotes . date('d.m.Y', strtotime($row['mem_begin'])) . $valueQuotes . $separator;
            $CSVstr .= $valueQuotes . $row['years_member'] . $valueQuotes . "\n";
        }
    }

    // Download CSV
    if ($charset == 'iso-8859-1') {
        $CSVstr = mb_convert_encoding($CSVstr, 'ISO-8859-1', 'UTF-8');
    }
    
    header('Content-Type: text/comma-separated-values; charset=' . $charset);
    header('Content-Disposition: attachment; filename="' . $gL10n->get('AWA_JUBILEE_REPORT') . '.csv"');
    echo $CSVstr;
    exit();
} elseif ($get_req == 'pdf') {
    foreach ($jubileeYears as $years) {
        $members = getJubileeMembers($years, $getRoleUuid, $gDb, $gCurrentOrganization, $gProfileFields);
        
        $memberCount = count($members);
        if ($memberCount > 0) {
            $tableHtml = '<h3>' . $years . ' ' . $gL10n->get('AWA_YEARS') . '</h3>';
            $tableHtml .= '<table border="1" cellpadding="3">';
            $tableHtml .= '<tr><th>' . $gL10n->get('SYS_LASTNAME') . '</th>';
            $tableHtml .= '<th>' . $gL10n->get('SYS_FIRSTNAME') . '</th>';
            $tableHtml .= '<th>' . $gL10n->get('SYS_STREET') . '</th>';
            $tableHtml .= '<th>' . $gL10n->get('SYS_POSTCODE') . '</th>';
            $tableHtml .= '<th>' . $gL10n->get('SYS_CITY') . '</th>';
            $tableHtml .= '<th>' . $gL10n->get('AWA_MEMBER_SINCE') . '</th>';
            $tableHtml .= '<th>' . $gL10n->get('AWA_JUBILEE_YEARS') . '</th></tr>';
            
            foreach ($members as $row) {
                $tableHtml .= '<tr>';
                $tableHtml .= '<td>' . $row['last_name'] . '</td>';
                $tableHtml .= '<td>' . $row['first_name'] . '</td>';
                $tableHtml .= '<td>' . ($row['address'] ?? '') . '</td>';
                $tableHtml .= '<td>' . ($row['postcode'] ?? '') . '</td>';
                $tableHtml .= '<td>' . ($row['city'] ?? '') . '</td>';
                $tableHtml .= '<td>' . date('d.m.Y', strtotime($row['mem_begin'])) . '</td>';
                $tableHtml .= '<td>' . $row['years_member'] . ' ' . $gL10n->get('AWA_YEARS') . '</td>';
                $tableHtml .= '</tr>';
            }
            
            $tableHtml .= '</table>';
            $pdf->writeHTML($tableHtml, true, false, true, false, '');
        }
    }
    $pdf->Output($gL10n->get('AWA_JUBILEE_REPORT') . '.pdf', 'D');
    exit();
} else {
    // HTML output - separate tables for each jubilee year
    $totalMembers = 0;
    $jubileeSummary = array(); // Track members per jubilee year
    
    foreach ($jubileeYears as $years) {
        $members = getJubileeMembers($years, $getRoleUuid, $gDb, $gCurrentOrganization, $gProfileFields);
        
        $memberCount = count($members);
        if ($memberCount > 0) {
            $totalMembers += $memberCount;
            $jubileeSummary[$years] = $memberCount;
            
            // Create table for this jubilee year
            $page->addHtml('<h3>' . $years . ' ' . $gL10n->get('AWA_YEARS') . ' (' . $memberCount . ' ' . $gL10n->get('AWA_MEMBERS_FOUND') . ')</h3>');
            
            $datatable = ($get_req == 'html');
            $hoverRows = ($get_req == 'html');
            $table = new HtmlTable('adm_jubilee_table_' . $years, $page, $hoverRows, $datatable, $classTable);
            
            $table->addRowHeadingByArray(array(
                $gL10n->get('SYS_LASTNAME'),
                $gL10n->get('SYS_FIRSTNAME'),
                $gL10n->get('SYS_STREET'),
                $gL10n->get('SYS_POSTCODE'),
                $gL10n->get('SYS_CITY'),
                $gL10n->get('AWA_MEMBER_SINCE'),
                $gL10n->get('AWA_JUBILEE_YEARS')
            ));
            
            foreach ($members as $row) {
                $columnValues = array();
                // Link to profile
                $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $row['usr_uuid'])) . '">' . $row['last_name'] . '</a>';
                $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $row['usr_uuid'])) . '">' . $row['first_name'] . '</a>';
                $columnValues[] = $row['address'] ?? '';
                $columnValues[] = $row['postcode'] ?? '';
                $columnValues[] = $row['city'] ?? '';
                $columnValues[] = date('d.m.Y', strtotime($row['mem_begin']));
                $columnValues[] = $row['years_member'] . ' ' . $gL10n->get('AWA_YEARS');
                
                $table->addRowByArray($columnValues);
            }
            
            $page->addHtml($table->show());
        }
    }
    
    if ($totalMembers == 0) {
        $page->addHtml('<div class="alert alert-warning">' . $gL10n->get('AWA_JUBILEE_NO_MEMBERS') . '</div>');
    } else {
        // Display simple one-line summary with total count
        $page->addHtml('<div class="alert alert-info mt-3">');
        $page->addHtml('<strong>' . $totalMembers . '</strong> ' . $gL10n->get('AWA_MEMBERS_FOUND'));
        $page->addHtml('</div>');
    }
    
    $page->show();
}
