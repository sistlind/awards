<?php
/******************************************************************************
 * Awards
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 * 
 * https://github.com/sistlind/awards
 *                  
 *****************************************************************************/

require_once(__DIR__ .'/awards_common.php');


if($gCurrentUser->editUsers() == false)//%TODO: Berechtigungen
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}
$gNavigation->addUrl(CURRENT_URL);

$show_all	   = admFuncVariableIsValid($_GET, 'awa_show_all', 'string', array('defaultValue'=> 'false'));
$get_req       = admFuncVariableIsValid($_GET, 'export_mode', 'string', array('defaultValue' => 'html', 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf', 'pdfl' )));
$getFullScreen = admFuncVariableIsValid($_GET, 'full_screen', 'numeric');
$getFilter     = admFuncVariableIsValid($_GET, 'filter', 'string' , array('defaultValue' => ''));
$getAwaCat     = admFuncVariableIsValid($_GET, 'awa_cat', 'numeric');
$getAwaName    = admFuncVariableIsValid($_GET, 'awa_name', 'string');

// define title (html) and headline
$title = $gL10n->get('AWA_HEADLINE');
$headline = $gL10n->get('AWA_HEADLINE');

$user = new User($gDb, $gProfileFields);

// initialize some special mode parameters
$separator   = '';
$valueQuotes = '';
$charset     = '';
$classTable  = '';
$orientation = '';

//Normalize to boolean
if($show_all==="true"||$show_all==="1")
{
	$show_all=true;
}else
{
	$show_all=false;
}

switch ($get_req)
{
	case 'csv-ms':
		$separator   = ';';  // Microsoft Excel 2007 or new needs a semicolon
		$valueQuotes = '"';  // all values should be set with quotes
		$get_req     = 'csv';
		$charset     = 'iso-8859-1';
		break;
	case 'csv-oo':
		$separator   = ',';   // a CSV file should have a comma
		$valueQuotes = '"';   // all values should be set with quotes
		$get_req     = 'csv';
		$charset     = 'utf-8';
		break;
	case 'pdf':
		$classTable  = 'table';
		$orientation = 'P';
		$get_req     = 'pdf';
		break;
	case 'pdfl':
		$classTable  = 'table';
		$orientation = 'L';
		$get_req     = 'pdf';
		break;
	case 'html':
		$classTable  = 'table table-condensed';
		break;
	case 'print':
		$classTable  = 'table table-condensed table-striped';
		break;
	default:
		break;
}

$CSVstr = '';   // enthaelt die komplette CSV-Datei als String

// if html mode and last url was not a list view then save this url to navigation stack
if($get_req == 'html' && strpos($gNavigation->getUrl(), 'awards_show.php') === false)
{
	$gNavigation->addUrl(CURRENT_URL);
}

$page = new HtmlPage($headline);
if ($get_req != 'csv')
{
	$datatable = false;
	$hoverRows = false;


	if ($get_req == 'print')
	{
		// create html page object without the custom theme files
		$page->setInlineMode();
		$page->setPrintMode();

		$page->setTitle($title);

		$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
	}
	elseif ($get_req == 'pdf')
	{
		require_once(ADMIDIO_PATH. FOLDER_LIBS_SERVER .'/tecnickcom/tcpdf/tcpdf.php');
		$pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Admidio');
		$pdf->setTitle($title);

		// remove default header/footer
		$pdf->setPrintHeader(true);
		$pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set auto page breaks
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
		$pdf->SetMargins(10, 20, 10);
		$pdf->SetHeaderMargin(10);
		$pdf->SetFooterMargin(0);

		//headline for PDF
		$pdf->SetHeaderData('', 0, $headline, '');

		// set font
		$pdf->SetFont('times', '', 10);

		// add a page
		$pdf->AddPage();

		// Create table object for display
		$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
		$table->addAttribute('border', '1');
		$table->addTableHeader();
		$table->addRow();
	}
	elseif ($get_req == 'html')
	{
		$datatable = true;
		$hoverRows = true;

		if ($getFullScreen == true)
		{
		    $page->setInlineMode();
		}

		$page->setTitle($title);

		$page->addJavascript('
            $("#export_list_to").change(function () {
                if($(this).val().length > 1) {
                    self.location.href = "'. ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/awards_show.php?" +
                        "awa_show_all='.$show_all.'&filter='.$getFilter.'&awa_cat='.$getAwaCat.'&awa_name='.$getAwaName.'&export_mode=" + $(this).val();
                }
            });
            $("#menu_item_print_view").click(function () {
                window.open("'. ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/awards_show.php?" +
                 "awa_show_all='.$show_all.'&filter='.$getFilter.'&awa_cat='.$getAwaCat.'&awa_name='.$getAwaName.'&export_mode=print", "_blank");
            });', true);
		// get module menu
		if ($getFullScreen == true)
		{
            $page->addPageFunctionsMenuItem('menu_item_normal_picture', $gL10n->get('AWA_NORMAL_SCREEN'),ADMIDIO_URL.FOLDER_PLUGINS.$plugin_folder.'/awards_show.php?export_mode=html&amp;awa_show_all='.$show_all.'&amp;filter='.$getFilter.'&amp;awa_cat='.$getAwaCat.'&amp;awa_name='.$getAwaName.'&amp;full_screen=0','fa-compress');
		}
		else
		{
            $page->addPageFunctionsMenuItem('menu_item_full_screen', $gL10n->get('AWA_FULL_SCREEN'),ADMIDIO_URL.FOLDER_PLUGINS.$plugin_folder.'/awards_show.php?export_mode=html&amp;awa_show_all='.$show_all.'&amp;filter='.$getFilter.'&amp;awa_cat='.$getAwaCat.'&amp;awa_name='.$getAwaName.'&amp;full_screen=1','fa-expand');
		}
		 
         $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'),'javascript:void(0);','fa-print');
		 
		if ($show_all == true)
		{ 
            $page->addPageFunctionsMenuItem('awa_show_only_my_org', $gL10n->get('AWA_SHOW_ALL'),ADMIDIO_URL.FOLDER_PLUGINS.$plugin_folder.'/awards_show.php?export_mode=html&amp;filter='.$getFilter.'&amp;awa_cat='.$getAwaCat.'&amp;awa_name='.$getAwaName.'&amp;full_screen='.$getFullScreen.'&amp;awa_show_all=0','fa-check-square');
		}
		else
		{
            $page->addPageFunctionsMenuItem('awa_show_all', $gL10n->get('AWA_SHOW_ALL'),ADMIDIO_URL.FOLDER_PLUGINS.$plugin_folder.'/awards_show.php?export_mode=html&amp;filter='.$getFilter.'&amp;awa_cat='.$getAwaCat.'&amp;awa_name='.$getAwaName.'&amp;full_screen='.$getFullScreen.'&amp;awa_show_all=1','fa-square');
		}
		
        // dropdown menu item with all export possibilities
        $page->addPageFunctionsMenuItem('awa_item_lists_export', $gL10n->get('SYS_EXPORT_TO'), '#', 'fa-file-download');
        $export_link=ADMIDIO_URL.FOLDER_PLUGINS.$plugin_folder.'/awards_show.php?filter='.$getFilter.'&amp;awa_cat='.$getAwaCat.'&amp;awa_name='.$getAwaName.'&amp;full_screen='.$getFullScreen.'&amp;awa_show_all=0&amp;export_mode';
        $page->addPageFunctionsMenuItem('awa_item_lists_csv_ms', $gL10n->get('SYS_MICROSOFT_EXCEL'),$export_link.'=csv-ms','fa-file-excel', 'awa_item_lists_export');
        $page->addPageFunctionsMenuItem('awa_item_lists_pdf', $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_PORTRAIT').')',$export_link.'=pdf','fa-file-pdf', 'awa_item_lists_export');
        $page->addPageFunctionsMenuItem('awa_item_lists_pdfl', $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_LANDSCAPE').')',$export_link.'=pdfl','fa-file-pdf','awa_item_lists_export');
        $page->addPageFunctionsMenuItem('awa_item_lists_csv', $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')',$export_link.'=csv-oo','fa-file-pdf', 'awa_item_lists_export');

		$filterNavbar = new HtmlNavbar('menu_list_filter', 'show_awards', null, 'filter');
			
		$form = new HtmlForm('navbar_filter_form', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/awards_show.php', $page, array('type' => 'navbar', 'setFocus' => false));
		$form->addInput('filter', '', $getFilter);
		
		$sql = 'SELECT cat_id, cat_name
                  FROM '.TBL_CATEGORIES.'
                 WHERE cat_type=\'AWA\'
                   AND ( cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                    OR cat_org_id IS NULL )';
		$form->addSelectBoxFromSql('awa_cat', '', $gDb, $sql, array('defaultValue' => $getAwaCat, 'showContextDependentFirstEntry' => false, 'firstEntry' => $gL10n->get('AWA_CAT')));
			
		$sql = 'SELECT awa_name, awa_name
                  FROM '.TBL_USER_AWARDS.'
                 WHERE ( awa_org_id = '.$gCurrentOrganization->getValue('org_id').'
                    OR awa_org_id IS NULL )';
		$form->addSelectBoxFromSql('awa_name', '', $gDb, $sql, array('defaultValue' => $getAwaName, 'showContextDependentFirstEntry' => false, 'firstEntry' => $gL10n->get('AWA_HONOR_TITLE')));
			
		$form->addInput('awa_show_all', '', $show_all, array('property' => HtmlForm::FIELD_HIDDEN));
		$form->addInput('export_mode', '', 'html', array('property' => HtmlForm::FIELD_HIDDEN));
		$form->addInput('full_screen', '', $getFullScreen, array('property' => HtmlForm::FIELD_HIDDEN));
		$form->addSubmitButton('btn_send', $gL10n->get('SYS_OK'));
		$filterNavbar->addForm($form->show(false));
		$page->addHtml($filterNavbar->show());

		$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
		#$table->setDatatablesRowsPerPage($gSettingsManager->getInt('members_users_per_page'));

	}
	else
	{
		$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
	}
}


//Falls Datenbank nicht vorhanden Install-Skript starten
if(!isAwardsDbInstalled()){
	//Datenbank nicht vorhanden
	$page->addHtml('<h2>'.$gL10n->get('SYS_ERROR').'</h2>');
	$page->addHtml($gL10n->get('AWA_ERR_NO_DB'));
	$page->addHtml('<p><a href=awards_install.php>'.$gL10n->get('AWA_INSTALL').'</a></p>');
	$page->show();
	return;
}

$awards=awa_load_awards(false,$show_all);

if ($awards===false)
{
	$page->addHtml('<p>'.$gL10n->get('AWA_NO_DATA').'</p>');
	$page->show();
	return;
}

//generate headlines
if ($get_req == 'csv')
{
	if ($show_all)
	{
		$CSVstr .= $valueQuotes. $gL10n->get('AWA_ORG_ID'). $valueQuotes. $separator;
		$CSVstr .= $valueQuotes. $gL10n->get('AWA_ORG_NAME'). $valueQuotes. $separator;
	}	
		
	$CSVstr .= $valueQuotes. $gL10n->get('AWA_CAT'). $valueQuotes. $separator;
	$CSVstr .= $valueQuotes. $gL10n->get('AWA_USER'). $valueQuotes. $separator;
	$CSVstr .= $valueQuotes. $gL10n->get('SYS_DATE'). $valueQuotes. $separator;
	$CSVstr .= $valueQuotes. $gL10n->get('AWA_HONOR_TITLE'). $valueQuotes. $separator;
	$CSVstr .= $valueQuotes. $gL10n->get('AWA_HONOR_INFO'). $valueQuotes;
}
elseif ($get_req == 'pdf')
{
	$table->addColumn($gL10n->get('AWA_CAT'),         array('style' => 'text-align: left;font-size:12;background-color:#C7C7C7;'), 'th');
	$table->addColumn($gL10n->get('AWA_USER'),        array('style' => 'text-align: left;font-size:12;background-color:#C7C7C7;'), 'th');
	$table->addColumn($gL10n->get('SYS_DATE'),        array('style' => 'text-align: left;font-size:12;background-color:#C7C7C7;'), 'th');
	$table->addColumn($gL10n->get('AWA_HONOR_TITLE'), array('style' => 'text-align: left;font-size:12;background-color:#C7C7C7;'), 'th');
	$table->addColumn($gL10n->get('AWA_HONOR_INFO'),  array('style' => 'text-align: left;font-size:12;background-color:#C7C7C7;'), 'th');
}
elseif ($get_req == 'html' || $get_req == 'print')
{
	$columnAlign  = array('left');
	$columnValues = array($gL10n->get('AWA_CAT'));

	if ($show_all)
	{
		$columnAlign[]  = 'left';
		$columnValues[] = $gL10n->get('AWA_ORG_NAME');
	}

	$columnAlign[]  = 'left';
	$columnValues[] = $gL10n->get('AWA_USER');
	$columnAlign[]  = 'left';
	$columnValues[] = $gL10n->get('SYS_DATE');
	$columnAlign[]  = 'left';
	$columnValues[] = $gL10n->get('AWA_HONOR_TITLE');
	$columnAlign[]  = 'left';
	$columnValues[] = $gL10n->get('AWA_HONOR_INFO');
	
	if ($gCurrentUser->editUsers() == true && $get_req == 'html')    //Ändern/Löschen Buttons für berechtigte User
	{
		$columnAlign[]  = 'center';
		$columnValues[] = '&nbsp;';
		$table->disableDatatablesColumnsSort(array(count($columnValues)));
	}
}

if ($get_req == 'csv')
{
	$CSVstr = $CSVstr. "\n";
}
elseif ($get_req == 'html' || $get_req == 'print')
{
	$table->setDatatablesGroupColumn(1);
	$table->setColumnAlignByArray($columnAlign);
	$table->addRowHeadingByArray($columnValues);
}
else
{
	$table->addTableBody();
}

foreach ($awards as $row)
{
	if ((!empty($getAwaName) && $getAwaName != $row['awa_name']) || ($getAwaCat != 0 && $getAwaCat != $row['awa_cat_id']))
	{
		continue;
	}
	
	$columnValues = array();
	$tmp_csv = '';
	
	/*****************************************************************/
	// create output format
	/*****************************************************************/
	if ($get_req === 'html' || $get_req === 'print' || $get_req === 'pdf')
	{
		$columnValues[] = $row['awa_cat_name'];
		if ($show_all)
		{
			$columnValues[] = $row['awa_org_name'];
		}
				
		if ($get_req == 'html' )
		{
		    $user->readDataById($row['awa_usr_id']);
		    $columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.
					$row['last_name'].', '.$row['first_name'].'</a>';
		}
		else 
		{
			$columnValues[] = $row['last_name'].', '.$row['first_name'];
		}
				
		$columnValues[] = date('d.m.Y',strtotime($row['awa_date']));
		$columnValues[] = $row['awa_name'];
		$columnValues[] = $row['awa_info'];
				
		if ($gCurrentUser->editUsers() == true && $get_req == 'html')//Ändern/Löschen Buttons für berechtigte User
		{
			$tempValue = '';
			$tempValue .= '<a class="admidio-icon-link" href="'.ADMIDIO_URL.FOLDER_PLUGINS.$plugin_folder.'/awards_delete.php?awa_id='.$row['awa_id'].'">';
			$tempValue .='<i class="fas fa-trash" data-toggle="tooltip" title="'.$gL10n->get('AWA_DELETE_HONOR').'"></i></a>';
			$tempValue .='&nbsp;&nbsp;';
			$tempValue .='<a class="admidio-icon-link" href="'.ADMIDIO_URL.FOLDER_PLUGINS.$plugin_folder.'/awards_change.php?awa_id='.$row['awa_id'].'">';
			$tempValue .='<i class="fas fa-edit" data-toggle="tooltip" title="'.$gL10n->get('AWA_EDIT_HONOR').'"></i></a>';
			             
			$columnValues[] = $tempValue;
		}
	}
	else
	{
		if ($show_all)
		{
			$tmp_csv .= $valueQuotes. $row['awa_org_id']. $valueQuotes. $separator;
			$tmp_csv .= $valueQuotes. $row['awa_org_name']. $valueQuotes. $separator;
		}
		$tmp_csv .= $valueQuotes. $row['awa_cat_name']. $valueQuotes. $separator;
		$tmp_csv .= $valueQuotes. $row['last_name'].', '.$row['first_name']. $valueQuotes. $separator;
		$tmp_csv .= $valueQuotes. date('d.m.Y',strtotime($row['awa_date'])). $valueQuotes. $separator;
		$tmp_csv .= $valueQuotes. $row['awa_name']. $valueQuotes. $separator;
		$tmp_csv .= $valueQuotes. $row['awa_info']. $valueQuotes;
	}

	//pruefung auf filterstring
	if ($getFilter == '' || ($getFilter <> '' && (stristr(implode('',$columnValues), $getFilter  ) || stristr($tmp_csv, $getFilter))))
	{
		if ($get_req == 'csv')
		{
			$CSVstr .= $tmp_csv. "\n";
		}
		else
		{
			$table->addRowByArray($columnValues, 'show_awards', array('nobr' => 'true'));
		}
	}
}

// Settings for export file
if ($get_req == 'csv' || $get_req == 'pdf')
{
	$filename = $gCurrentOrganization->getValue('org_shortname'). '-'.$gL10n->get('AWA_DOWNLOAD_NAME').'_'.date('Ymd_Hm');
	$filename .= '.'.$get_req;

	// for IE the filename must have special chars in hexadecimal
	if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT']))
	{
		$filename = urlencode($filename);
	}

	header('Content-Disposition: attachment; filename="'.$filename.'"');

	// neccessary for IE6 to 8, because without it the download with SSL has problems
	header('Cache-Control: private');
	header('Pragma: public');
}

if ($get_req == 'csv')
{
	// nun die erstellte CSV-Datei an den User schicken
	header('Content-Type: text/comma-separated-values; charset='.$charset);
	ob_clean();
	ob_flush();
	flush();
	if ($charset == 'iso-8859-1')
	{
		echo utf8_decode($CSVstr);
	}
	else
	{
		echo $CSVstr;
	}
}
// send the new PDF to the User
elseif ($get_req == 'pdf')
{
	// output the HTML content
	$pdf->writeHTML($table->getHtmlTable(), true, false, true, false, '');

	//Save PDF to file
	$pdf->Output(ADMIDIO_PATH. FOLDER_DATA .'/'.$filename, 'F');

	//Redirect
	header('Content-Type: application/pdf');

	readfile(ADMIDIO_PATH. FOLDER_DATA .'/'.$filename);
	ignore_user_abort(true);
	unlink(ADMIDIO_PATH. FOLDER_DATA .'/'.$filename);
}
elseif ($get_req == 'html' || $get_req == 'print')
{
	// add table list to the page
	$page->addHtml($table->show(false));
	// show complete html page
	$page->show();
}

?>
