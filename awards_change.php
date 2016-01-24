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
require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');
require_once(SERVER_PATH. '/adm_plugins/awards/awards_common.php');

//Berechtigung checken
if($gCurrentUser->editUsers() == false)
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$getAwardID  = admFuncVariableIsValid($_GET, 'awa_id', 'numeric', array('defaultValue' => 0));

if ($getAwardID > 0)
{
$EditMode=True;
}else
{
$EditMode=False;
}
// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
$gDb->setCurrentDB();

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');

$gNavigation->addUrl(CURRENT_URL);

if($EditMode)
{
	$headline = $gL10n->get('AWA_HEADLINE_CHANGE');
}else{
	$headline = $gL10n->get('AWA_HEADLINE');
}

$headline  = $gL10n->get('AWA_HEADLINE');
$page = new HtmlPage($headline);

//Begin der Seite

if($gDebug)
{
    $page->addCssFile($g_root_path.'/adm_program/libs/bootstrap-datepicker/css/bootstrap-datepicker3.css');
    $page->addJavascriptFile($g_root_path.'/adm_program/libs/bootstrap-datepicker/js/bootstrap-datepicker.js');
}
else
{
    $page->addCssFile($g_root_path.'/adm_program/libs/bootstrap-datepicker/css/bootstrap-datepicker3.min.css');
    $page->addJavascriptFile($g_root_path.'/adm_program/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js');
}


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





if($EditMode && !isset($_POST['submit']))
{
$AWAObj = new TableAccess($gDb, $tablename.' ', 'awa',$getAwardID);
$POST_award_user_id=$AWAObj->getValue('awa_usr_id');
$POST_award_cat_id=$AWAObj->getValue('awa_cat_id');
$POST_award_name_new=$AWAObj->getValue('awa_name');
$POST_award_info=$AWAObj->getValue('awa_info');
$DateObject=date_create($AWAObj->getValue('awa_date'));
$POST_award_date=date_format($DateObject,'d.m.Y');

}else
{
//Übergebene POST_variablen speichern
$POST_award_new_id=$_POST['award_new_id'];
$POST_award_user_id=$_POST['award_user_id'];
$POST_award_cat_id=$_POST['award_cat_id'];
$POST_award_name_old_id=$_POST['award_name_old_id'];
$POST_award_name_new=$_POST['award_name_new'];
$POST_award_info=$_POST['award_info'];
$POST_award_date=$_POST['award_date'];
$DateObject=date_create($POST_award_date);
$InternalDate=date_format($DateObject,'Y-m-d');
}


if(0)//Debug!
{
echo '<br>award new id: '.$POST_award_new_id;
echo '<br>userid: '.$POST_award_user_id;
echo '<br>catid: '.$POST_award_cat_id;
echo '<br>nameoldid: '.$POST_award_name_old;
echo '<br>namenew: '.$POST_award_name_new;
echo '<br>info: '.$POST_award_info;
echo '<br>date: '.$POST_award_date;
echo '<br>date_internal: '.$InternalDate;
}


//Letzte ID der Datenbank merken um doppelte Einträge zu verhindern
$sql    = 'SELECT COUNT(*) FROM '.$tablename.' ;';
$result= $gDb->fetch_array($gDb->query($sql));
if ($result['COUNT(*)']==0)
{$newID=1;}
else
{
$sql    = 'SELECT MAX(awa_id) as maxID FROM '.$tablename.' ;';
//echo $sql;
$result= $gDb->fetch_array($gDb->query($sql));
$newID=$result['maxID']+1;
}


if (isset($_POST['submit']))
{
$INPUTOK=TRUE;
$ErrorStr= '<h2>'.$gL10n->get('SYS_ERROR').'</h2>';
//echo 'Submit gedrückt!';
//Eingaben OK?
if (($POST_award_new_id !=$newID) && !($EditMode))
	{//Doppelter Aufruf?
	$ErrorStr.='<p>'.$gL10n->get('AWA_ERR_DOUBLE_ID').'</p>';
	$INPUTOK=FALSE;
	}
if ($POST_award_user_id==0)
	{//Name Pflicht!
	$ErrorStr.='<p>'.$gL10n->get('AWA_ERR_NO_USER').'</p>';
	$INPUTOK=FALSE;
	}
if ($POST_award_cat_id==0)
	{//Kategorie Pflicht!
	$ErrorStr.='<p>'.$gL10n->get('AWA_ERR_NO_CAT').'</p>';
	$INPUTOK=FALSE;
	}
if ((strlen($POST_award_name_new)>1)&&($POST_award_name_old_id>0))
	{//Nur ein Titelfeld füllen!
	$ErrorStr.='<p>'.$gL10n->get('AWA_ERR_DOUBLE_TITLE').'</p>';
	$INPUTOK=FALSE;
	}
if ((strlen($POST_award_name_new)<1)&&($POST_award_name_old_id==0))
	{//Titel Pflicht
	$ErrorStr.='<p>'.$gL10n->get('AWA_ERR_NO_TITLE').'</p>';
	$INPUTOK=FALSE;
	}
if (strlen($POST_award_date)<4)//TODO: Besserer Check
	{//Datum Pflicht !
	$ErrorStr.='<p>'.$gL10n->get('AWA_ERR_NO_DATE').'</p>';
	$INPUTOK=FALSE;
	}
if($INPUTOK)
{
//echo 'SAVE!';
if($EditMode)
{
$NewAWAObj = new TableAccess($gDb, $tablename.' ', 'awa',$getAwardID);
}else{
$NewAWAObj = new TableAccess($gDb, $tablename.' ', 'awa');
}
$NewAWAObj->setValue('awa_cat_id',$POST_award_cat_id);
$NewAWAObj->setValue('awa_org_id',$gCurrentOrganization->getValue('org_id'));
$NewAWAObj->setValue('awa_usr_id',$POST_award_user_id);
if($POST_award_name_old_id>0)
{
	$sql    = 'SELECT awa_name FROM '.$tablename.'  Where awa_id=\''.$POST_award_name_old_id.'\';';
	$result= $gDb->fetch_array($gDb->query($sql));
	$NewAWAObj->setValue('awa_name',$result['awa_name']);
}else
{
	$NewAWAObj->setValue('awa_name',$POST_award_name_new);
}
$NewAWAObj->setValue('awa_info',$POST_award_info);
$NewAWAObj->setValue('awa_date',$InternalDate);
$NewAWAObj->save();
$page->addHtml('<h2>'.$gL10n->get('AWA_SUCCESS').'</h2>');
if (!$EditMode){
	$page->addHtml('<p>'.$gL10n->get('AWA_SUCCESS_NEW').'</p>');
	$page->addHtml('<h2>'.$gL10n->get('AWA_NEW_ENTRY').'</h2>');
	unset($POST_award_user_id);
	$newID+=1;
}else
{
	$page->addHtml('<p>'.$gL10n->get('AWA_SUCCESS_CHANGE').'</p>');
	$page->addHtml('<h2>'.$gL10n->get('AWA_NEW_ENTRY').'</h2>');
}
}else
{
	$page->addHtml( $ErrorStr);
}

}

// Html des Modules ausgeben
$page->addHtml('<form action="'.$g_root_path.'/adm_plugins/awards/awards_change.php?awa_id='.$getAwardID.'" method="post">
<input type="hidden" name="award_new_id" value="'.$newID.'">
<div class="formLayout" id="edit_awards_form">
    <div class="formHead">'. $gLayout['title']. '</div>
    <div class="formBody">
        <ul class="formFieldList">
            <li>
                <dl>
<dt><label for="award_user_id">'.$gL10n->get('AWA_USER').'</label><span class="mandatoryFieldMarker" title="'.$gL10n->get('SYS_MANDATORY_FIELD').'">*</span></dt>
                    <dd>
 			 <select id="award_user_id" name="award_user_id" >
			<option value="0">'.$gL10n->get('AWA_USER_SELECT').'</option>');
//Nutzer auswahl füllen
//only active members
    $memberCondition = ' AND EXISTS 
        (SELECT 1
           FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
          WHERE mem_usr_id = usr_id
            AND mem_rol_id = rol_id
            AND mem_begin <= \''.DATE_NOW.'\'
            AND mem_end    > \''.DATE_NOW.'\'
			AND cat_name_intern <> \'CONFIRMATION_OF_PARTICIPATION\'
            AND rol_valid  = 1
            AND rol_cat_id = cat_id
            AND (  cat_org_id = '. $gCurrentOrganization->getValue('org_id'). '
                OR cat_org_id IS NULL )) ';
$sql    = 'SELECT usr_id, last_name.usd_value as last_name, first_name.usd_value as first_name, birthday.usd_value as birthday                  
             FROM '. TBL_USERS. '
             JOIN '. TBL_USER_DATA. ' as last_name
               ON last_name.usd_usr_id = usr_id
              AND last_name.usd_usf_id = '. $gProfileFields->getProperty('LAST_NAME', 'usf_id'). '
             JOIN '. TBL_USER_DATA. ' as first_name
               ON first_name.usd_usr_id = usr_id
              AND first_name.usd_usf_id = '. $gProfileFields->getProperty('FIRST_NAME', 'usf_id'). '
             LEFT JOIN '. TBL_USER_DATA. ' as birthday
               ON birthday.usd_usr_id = usr_id
              AND birthday.usd_usf_id = '. $gProfileFields->getProperty('BIRTHDAY', 'usf_id'). '
             WHERE usr_valid = 1'.$memberCondition.$searchCondition.' ORDER BY last_name.usd_value, first_name.usd_value';
$query=$gDb->query($sql);
while($row=$gDb->fetch_array($query))
{
	if ($row['usr_id']==$POST_award_user_id)
	{
		$selected='selected';
	}else{
		$selected='';
	}
	$page->addHtml('<option value="'.$row['usr_id'].'"'.$selected.'>'.$row['last_name'].', '.$row['first_name'].'  ('.$row['birthday'].')</option>');
}
$page->addHtml('</select></dl>');

$page->addHtml('<dl><dt><label for="award_cat_id">'.$gL10n->get('AWA_CAT').'</label><span class="mandatoryFieldMarker" title="'.$gL10n->get('SYS_MANDATORY_FIELD').'">*</span></dt>
                    <dd>
 			 <select id="award_cat_id" name="award_cat_id" >
			<option value="0">'.$gL10n->get('AWA_CAT_SELECT').'</option>');
//Kategorie auswahl füllen
$sql    = 'SELECT cat_id, cat_name FROM '.$g_tbl_praefix.'_categories WHERE cat_type=\'AWA\' AND cat_default=1;';
$query=$gDb->query($sql);
$default_category=$gDb->fetch_array($query);
$sql    = 'SELECT cat_id, cat_name FROM '.$g_tbl_praefix.'_categories WHERE cat_type=\'AWA\' ORDER BY cat_sequence;';
$query=$gDb->query($sql);
while($row=$gDb->fetch_array($query))
{
	if ($row['cat_id']==$POST_award_cat_id)
	{
		$selected='selected';
	}else if (!isset($POST_award_cat_id) && ($row['cat_id']==$default_category['cat_id'])){
		$selected='selected';
	}else{

		$selected='';
	}
	$page->addHtml('<option value="'.$row['cat_id'].'"'.$selected.'>'.$row['cat_name'].'</option>');
}


$page->addHtml('</select> </dd></dl>');
$page->addHtml('<dl><dt><label for="award_name_old_id">'.$gL10n->get('AWA_HONOR_OLD').'</label><span class="mandatoryFieldMarker" title="'.$gL10n->get('SYS_MANDATORY_FIELD').'">*</span></dt>
                    <dd>
 			 <select id="award_name_old_id" name="award_name_old_id" >
			<option value="0" >'.$gL10n->get('AWA_HONOR_OLD_SELECT').'</option>
			<option value="-1" ></option>');
//Dopdown für alte einträge füllen
$sql    = 'SELECT awa_name, awa_id FROM '.$tablename.'  GROUP BY awa_name ORDER BY awa_date DESC;';

$query=$gDb->query($sql);
while($row=$gDb->fetch_array($query))
{
	if ($row['awa_id']==$POST_award_name_old_id)
	{
		$selected='selected';
	}else{
		$selected='';
	}
	$page->addHtml('<option value="'.$row['awa_id'].'"'.$selected.'>'.$row['awa_name'].'</option>');
}
$page->addHtml('</select></dd>');
$page->addHtml('    <dt><label for="award_name_new">'.$gL10n->get('AWA_HONOR_NEW').'</label><span class="mandatoryFieldMarker" title="'.$gL10n->get('SYS_MANDATORY_FIELD').'">*</span></dt>  
        <dd>
	<input type="text" id="award_name_new" name="award_name_new" style="width: 90%;" maxlength="100" value="'.$POST_award_name_new.'" />
                        </dd> </dl>     
	<dl><dt><label for="award_info">'.$gL10n->get('AWA_HONOR_INFO').'</label></dt>
                   <dd>
                        <input type="text" id="award_info" name="award_info" style="width: 90%;" maxlength="100" value="'.$POST_award_info.'" />
                        </dd></dl>');
$page->addHtml('<dl>
                    <dt><label for="award_date">'.$gL10n->get('AWA_HONOR_DATE').'</label><span class="mandatoryFieldMarker" title="'.$gL10n->get('SYS_MANDATORY_FIELD').'">*</span></dt>
                    <dd>
                    <input type="text" id="award_date" name="award_date" data-provide="datepicker" data-date-format="dd.mm.yyyy" style="width: 80px;" 
                        maxlength="10"  value="'.$POST_award_date.'"  />'.$gL10n->get('AWA_HONOR_DATE_FORMAT').'
                    <span id="calendardiv" style="position: absolute; visibility: hidden;"></span></dd>
                </dl>
            </li>
<div class="formSubmit">
            <button id="btnSave" type="submit" name="submit" value="submit"><img src="'. THEME_PATH. '/icons/disk.png" alt="'.$gL10n->get('SYS_SAVE').'" />&nbsp;'.$gL10n->get('SYS_SAVE').'</button>
        </div>
    </div>
</div>
</form>

<ul class="iconTextLinkList">
    <li>
        <span class="iconTextLink">
            <a href="'.$g_root_path.'/adm_program/system/back.php"><img
            src="'. THEME_PATH. '/icons/back.png" alt="'.$gL10n->get('SYS_BACK').'" /></a>
            <a href="'.$g_root_path.'/adm_program/system/back.php">'.$gL10n->get('SYS_BACK').'</a>
        </span>
    </li>
</ul>');

$page->show();
?>
