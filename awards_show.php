<?php
/******************************************************************************
 * Awards
 *
 * Version 0.0.1
 *
 * Datum        : 29.09.2014  
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 * 
 *                  
 *****************************************************************************/
// Pfad des Plugins ermitteln

require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');
require_once(SERVER_PATH. '/adm_plugins/awards/awards_common.php');
$plugin_folder=getFolder(__FILE__);
$plugin_path=getPath(__FILE__);



if($gCurrentUser->editUsers() == false)//%TODO: Berechtigungen
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Export?
$get_req=  admFuncVariableIsValid($_GET, 'export_mode', 'string', array('defaultValue'=> '','validValues' => array('csv-ms', 'csv-oo','NULL')));
switch($get_req){
	case 'csv-ms':
		$separator    = ';'; // Microsoft Excel 2007 und neuer braucht ein Semicolon
    		$value_quotes = '"';
    		$getCSV     = TRUE;
		$charset      = 'iso-8859-1';
        	break;
	case 'csv-oo':
		$separator    = ','; // Microsoft Excel 2007 und neuer braucht ein Semicolon
    		$value_quotes = '"';
    		$getCSV     = TRUE;
		$charset      = 'utf-8';
        	break;
	default:
    		$getCSV     = FALSE;
		$CSVstr	='';

}

// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
$gDb->setCurrentDB();

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');

if (!$getCSV)
{
	$gNavigation->addUrl(CURRENT_URL);
}

$headline  = $gL10n->get('AWA_HEADLINE');
$page = new HtmlPage($headline);

//Begin der Seite

//Falls Datenbank nicht vorhanden Install-Skript starten
$sql_select="SHOW TABLES LIKE '".$tablename."'"; 
$query = @mysql_query($sql_select); 
if(mysql_num_rows($query)===0){
//Datenbank nicht vorhanden
$page->addHtml('<h2>'.$gL10n->get('SYS_ERROR').'</h2>');
$page->addHtml($gL10n->get('AWA_ERR_NO_DB'));
$page->addHtml('<p><a href=awards_install.php>'.$gL10n->get('AWA_INSTALL').'</a></p>');
$page->show();
exit;
}

$sql    = 'SELECT awa_id, awa_usr_id, awa_cat_id,awa_name, awa_info, awa_date, 
		awa_cat_seq.cat_sequence as awa_cat_seq,
		awa_cat_name.cat_name as awa_cat_name,
		last_name.usd_value as last_name,
		first_name.usd_value as first_name
          FROM '.$tablename.' 
             JOIN '. TBL_USER_DATA. ' as last_name
               ON last_name.usd_usr_id = awa_usr_id
              AND last_name.usd_usf_id = '. $gProfileFields->getProperty('LAST_NAME', 'usf_id'). '
             JOIN '. TBL_USER_DATA. ' as first_name
               ON first_name.usd_usr_id = awa_usr_id
              AND first_name.usd_usf_id = '. $gProfileFields->getProperty('FIRST_NAME', 'usf_id'). '
 	     JOIN '. TBL_CATEGORIES. ' as awa_cat_name
               ON awa_cat_name.cat_id = awa_cat_id
		AND awa_cat_name.cat_type =\'AWA\'
	     JOIN '. TBL_CATEGORIES. ' as awa_cat_seq
               ON awa_cat_seq.cat_id = awa_cat_id
		AND awa_cat_seq.cat_type =\'AWA\'
	ORDER BY awa_cat_seq, awa_date DESC,last_name,first_name';
//echo $sql;
$query=$gDb->query($sql);
if (mysql_num_rows($query)==0)
{
	$page->addHtml('<p>'.$gL10n->get('AWA_NO_DATA').'</p>');
	$page->show();
	exit;
}
//Buttons für Export

/*	$page->addHtml('<form method="get" action="awards_show.php">
     <img src="'. THEME_PATH. '/icons/download.png" alt="'.$gL10n->get('LST_EXPORT_TO').'" />
                    <select size="1" name="export_mode" onChange="this.form.submit()">
                        <option value="" selected="selected">'.$gL10n->get('LST_EXPORT_TO').' ...</option>
                        <option value="csv-ms">'.$gL10n->get('LST_MICROSOFT_EXCEL').' ('.$gL10n->get('SYS_ISO_8859_1').')</option>
                        <option value="csv-oo">'.$gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')</option> </form>');
*/
//Tabellenkopf
unset($PrevCatName);
while($row=$gDb->fetch_array($query))
	{
	if($getCSV)
		{//Fill CSVStr
		if ($CSVstr=='')
		{//Print Header
			$CSVstr=$value_quotes.$gL10n->get('AWA_CAT').$value_quotes.$separator.$value_quotes.$gL10n->get('AWA_USER').$value_quotes.$separator.$value_quotes.$gL10n->get('SYS_DATE').$value_quotes.$separator.$value_quotes.$gL10n->get('AWA_HONOR_TITLE').$value_quotes.$separator.$value_quotes.$gL10n->get('AWA_HONOR_INFO').$value_quotes;
		}
		$CSVstr.="\n";//Newline
		$CSVstr.=$value_quotes.$row['awa_cat_name'].$value_quotes.$separator.$value_quotes.$row['last_name'].', '.$row['first_name'].$value_quotes.$separator.$value_quotes.date('d.m.Y',strtotime($row['awa_date'])).$value_quotes.$separator.$value_quotes.$row['awa_name'].$value_quotes.$separator.$value_quotes.$row['awa_info'].$value_quotes;

	}else
	{//Output data to table
		 if (!isset($PrevCatName)||($PrevCatName!=$row['awa_cat_name']))
		{
			if (isset($PrevCatName))
			{//Beim ersten Durchgang gibt es noch nichts zu schließen
					$page->addHtml('</table>');
			}
		$PrevCatName=$row['awa_cat_name'];

			$page->addHtml('<h2>'.$row['awa_cat_name'].'</h2>');
			//Tabellenkopf anlegen
			$page->addHtml('<table>
				<colgroup>
				    <col width="150"/>
				    <col width="90"/>
				    <col width="120"/>
				    <col width="150"/>
				    <col width="50"/>
				</colgroup>');
			$page->addHtml('<tr><th>'.$gL10n->get('AWA_USER').'</th><th>'.$gL10n->get('SYS_DATE').'</th>
				<th>'.$gL10n->get('AWA_HONOR_TITLE').'</th><th>'.$gL10n->get('AWA_HONOR_INFO').'</th><th></th></tr>');
		}

		$page->addHtml('<tr>');
		$page->addHtml('<td><a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='.$row['awa_usr_id'].'">'.
			$row['last_name'].',&nbsp'.$row['first_name'].'</a></td>');
		$page->addHtml('<td>'.date('d.m.Y',strtotime($row['awa_date'])).'</td>');
		$page->addHtml('<td>'.$row['awa_name'].'</td>');
		$page->addHtml('<td>'.$row['awa_info'].'</td>');

		$page->addHtml('<td>');
		if($gCurrentUser->editUsers() == true)//Ändern/Löschen Buttons für berechtigte User
		{
			$page->addHtml('<a class="iconLink" href="'.$g_root_path.'/adm_plugins/awards/awards_delete.php?awa_id='.$row['awa_id'].'">
				<img src="'.THEME_PATH.'/icons/delete.png" alt="'.$gL10n->get('AWA_DELETE_HONOR').'" title="'.$gL10n->get('AWA_DELETE_HONOR').'" />
				</a>');
			$page->addHtml('<a class="iconLink" href="'.$g_root_path.'/adm_plugins/awards/awards_change.php?awa_id='.$row['awa_id'].'">
				<img src="'.THEME_PATH.'/icons/edit.png" alt="'.$gL10n->get('AWA_EDIT_HONOR').'" title="'.$gL10n->get('AWA_EDIT_HONOR').'" />
				</a>');
		}
		$page->addHtml('</td>');
		$page->addHtml('</tr>');
	}
}//end while

if($getCSV)
{
 // nun die erstellte CSV-Datei an den User schicken
    $filename = $g_organization. '-'.$gL10n->get('AWA_DOWNLOAD_NAME').'_'.date('Ymd_Hm').'.csv';
    header('Content-Type: text/comma-separated-values; charset='.$charset);
    header('Content-Disposition: attachment; filename="'.$filename.'"');
	ob_clean();
	ob_flush();
	flush();
	if($charset == 'iso-8859-1')
	{
		echo utf8_decode($CSVstr);
	}
	else
	{
		echo $CSVstr;
	}
}else
{
	$page->addHtml('</table>');

	$page->addHtml('<ul class="iconTextLinkList">
	    <li>
		<span class="iconTextLink">
		    <a href="'.$g_root_path.'/adm_program/system/back.php"><img
		    src="'. THEME_PATH. '/icons/back.png" alt="'.$gL10n->get('SYS_BACK').'" /></a>
		    <a href="'.$g_root_path.'/adm_program/system/back.php">'.$gL10n->get('SYS_BACK').'</a>
		</span>
	    </li>
	</ul>');
	$page->show();
}

?>
