<?php
/******************************************************************************
 * Awards
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 *
 * https://github.com/sistlind/awards
 *
 *****************************************************************************/

use Admidio\Infrastructure\Entity\Entity;

require_once(__DIR__ .'/awards_common.php');


//Berechtigung checken
if($gCurrentUser->isAdministratorUsers() == false)
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

$gNavigation->addUrl(CURRENT_URL);

if($EditMode)
{
	$headline = $gL10n->get('AWA_HEADLINE_CHANGE');
}else{
	$headline = $gL10n->get('AWA_HEADLINE');
}

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
if(!isAwardsDbInstalled()){
	//Datenbank nicht vorhanden
	$page->addHtml('<h2>'.$gL10n->get('SYS_ERROR').'</h2>');
	$page->addHtml($gL10n->get('AWA_ERR_NO_DB'));
	$page->addHtml('<p><a href=awards_install.php>'.$gL10n->get('AWA_INSTALL').'</a></p>');
	$page->show();
	return;
}

if($EditMode && !isset($_POST['submit']))
{
	$AWAObj = new Entity($gDb, TBL_USER_AWARDS.' ', 'awa',$getAwardID);
	$POST_award_user_id=$AWAObj->getValue('awa_usr_id');
	$POST_award_cat_id=$AWAObj->getValue('awa_cat_id');
	$POST_award_name_new=$AWAObj->getValue('awa_name');
	$POST_award_info=$AWAObj->getValue('awa_info');
	$DateObject=date_create($AWAObj->getValue('awa_date'));
	$POST_award_date=date_format($DateObject,'d.m.Y');
}else
{
	//Übergebene POST_variablen speichern
	$POST_award_new_id=admFuncVariableIsValid($_POST, 'award_new_id', 'numeric', array('defaultValue' => 0));
	$POST_award_user_id=admFuncVariableIsValid($_POST, 'award_user_id', 'numeric', array('defaultValue' => 0));
	$POST_award_role_id=admFuncVariableIsValid($_POST, 'award_role_id', 'numeric', array('defaultValue' => 0));
	$POST_award_leader=admFuncVariableIsValid($_POST, 'award_leader', 'numeric', array('defaultValue' => 0));
	$POST_award_cat_id=admFuncVariableIsValid($_POST, 'award_cat_id', 'numeric', array('defaultValue' => 0));
	$POST_award_name_old_id=admFuncVariableIsValid($_POST, 'award_name_old_id', 'numeric', array('defaultValue' => 0));
	$POST_award_name_new=admFuncVariableIsValid($_POST, 'award_name_new', 'string', array('defaultValue' => ''));
	$POST_award_info=admFuncVariableIsValid($_POST, 'award_info', 'string', array('defaultValue' => ''));
	$POST_award_date=admFuncVariableIsValid($_POST, 'award_date', 'string', array('defaultValue' => ''));
	$DateObject=date_create($POST_award_date);
	$InternalDate=date_format($DateObject,'Y-m-d');
}

if(isset($POST_award_name_old_id) && ($POST_award_name_old_id>0))
	{
		$sql    = 'SELECT awa_name FROM '.TBL_USER_AWARDS.'  Where awa_id=\''.$POST_award_name_old_id.'\';';
		$result= $gDb->query($sql)->fetch();
		$POST_award_name_old_name=$result['awa_name'];
	}


if($plg_debug_enabled == 1)//Debug Teil 2!
{
	echo '<br>role_enabled: '.$plg_role_enabled;
	echo '<br>leader_checked: '.$plg_leader_checked;
	echo '<br>cat_id: '.$plg_cat_id;
	echo '<br>award new id: '.$POST_award_new_id;
	echo '<br>userid: '.$POST_award_user_id;
	echo '<br>rolid: '.$POST_award_role_id;
	echo '<br>leader: '.$POST_award_leader;
	echo '<br>catid: '.$POST_award_cat_id;
	echo '<br>nameoldid: '.$POST_award_name_old_id;
	echo '<br>namenew: '.$POST_award_name_new;
	echo '<br>info: '.$POST_award_info;
	echo '<br>date: '.$POST_award_date;
	echo '<br>date_internal: '.$InternalDate;
}


//Letzte ID der Datenbank merken um doppelte Einträge zu verhindern
$sql    = 'SELECT COUNT(*) FROM '.TBL_USER_AWARDS.' ;';
$result= $gDb->query($sql)->fetch();
if ($result['COUNT(*)']==0)
{
	$newID=1;
}
else
{
	$sql    = 'SELECT MAX(awa_id) as maxID FROM '.TBL_USER_AWARDS.' ;';
	//echo $sql;
	$result= $gDb->query($sql)->fetch();
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
	$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_DOUBLE_ID').'</font></p>';
	$INPUTOK=FALSE;
	}
if ($plg_role_enabled==1)
	{
	if (($POST_award_user_id==0)&&($POST_award_role_id==0) )
		{//Mitglied oder Rolle Pflicht!
		$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_NO_USER_OR_ROLE').'</font></p>';
		$INPUTOK=FALSE;
		}
	if (($POST_award_user_id>0)&&($POST_award_role_id>0) )
		{//Rolle oder Mitglied - nicht beides!
		$POST_award_user_id='';
		$POST_award_role_id='';
		$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_USER_OR_ROLE').'</font></p>';
		$INPUTOK=FALSE;
		}
	/* Abfrage kollidiert mit Standardwert für leader
	if(($POST_award_user_id>0)&&($POST_award_leader==1) )
		{//Mitglied und Checkbox Leader geht nicht)
		$POST_award_user_id='';
		$POST_award_leader='';
		$ErrorStr.='<p><img src="'. THEME_PATH. '/icons/error_big.png"><font color="#FF0000">'.$gL10n->get('AWA_ERR_USER_LEADER').'</font></p>';
		$INPUTOK=FALSE;
		}
	*/
	}
else
	{
	if ($POST_award_user_id==0)
		{//Name Pflicht!
		$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_NO_USER').'</font></p>';
		$INPUTOK=FALSE;
		}
	}
if ($POST_award_cat_id==0)
	{//Kategorie Pflicht!
	$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_NO_CAT').'</font></p>';
	$INPUTOK=FALSE;
	}
if ((strlen($POST_award_name_new)>0)&&($POST_award_name_old_id>0))
	{//Nur ein Titelfeld füllen!
	$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_DOUBLE_TITLE').'</font></p>';
	$INPUTOK=FALSE;
	}
if ((strlen($POST_award_name_new)<1)&&($POST_award_name_old_id==0))
	{//Titel Pflicht
	$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_NO_TITLE').'</font></p>';
	$INPUTOK=FALSE;
	}
if (strlen($POST_award_date)<4)//TODO: Besserer Check
	{//Datum Pflicht !
	$ErrorStr.='<p><font color="#FF0000"><i class="fas fa-exclamation-circle"></i>&nbsp;'.$gL10n->get('AWA_ERR_NO_DATE').'</font></p>';
	$INPUTOK=FALSE;
	}
if($INPUTOK)
{
//echo 'SAVE!';
// User übergeben --> Award für User speichern
if ($POST_award_user_id>0)
{
	if($EditMode)
	{
		$NewAWAObj = new Entity($gDb, TBL_USER_AWARDS.' ', 'awa',$getAwardID);
	}else
	{
		$NewAWAObj = new Entity($gDb, TBL_USER_AWARDS.' ', 'awa');
	}
	$NewAWAObj->setValue('awa_cat_id',$POST_award_cat_id);
	$NewAWAObj->setValue('awa_org_id',$gCurrentOrganization->getValue('org_id'));
	$NewAWAObj->setValue('awa_usr_id',$POST_award_user_id);
	if($POST_award_name_old_id>0)
	{
		$sql    = 'SELECT awa_name FROM '.TBL_USER_AWARDS.'  Where awa_id=\''.$POST_award_name_old_id.'\';';
		$result= $gDb->query($sql)->fetch();
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
		$page->addHtml('<p><font color="#3ADF00"><i class="fas fa-check-circle"></i>&nbsp;'.$gL10n->get('AWA_SUCCESS_NEW').'</font></p>');
		$page->addHtml('<h2>'.$gL10n->get('AWA_NEXT_ENTRY').'</h2>');
		unset($POST_award_user_id);
		$newID+=1;
	}else
	{
		$page->addHtml('<p><font color="#3ADF00"><i class="fas fa-check-circle"></i>&nbsp;'.$gL10n->get('AWA_SUCCESS_CHANGE').'</font></p>');
		$page->addHtml('<h2>'.$gL10n->get('AWA_NEXT_ENTRY').'</h2>');
	}
}

//Rolle übergeben --> FÜR JEDEN USER DER ROLLE EINTRAGEN
if ($POST_award_role_id>0)
{
		$recordCount=0;
		//SQL-String - Alle USER-IDs zur Rolle aus der Datenbank finden ohne Leiter der Rolle
		$sql = 'SELECT mem_usr_id
		FROM '.TBL_MEMBERS.'
		WHERE '.TBL_MEMBERS.'.mem_rol_id ='.$POST_award_role_id.'
		AND '.TBL_MEMBERS.'.mem_begin <= \''.DATE_NOW.'\' 
		AND '.TBL_MEMBERS.'.mem_end >= \''.DATE_NOW.'\'';

		if ($POST_award_leader!=1)
		{//ohne leiter
			$sql.=' AND '.TBL_MEMBERS.'.mem_leader = 0';
		}
		
		$query=$gDb->query($sql);
		while($row=$query->fetch())
		{//TODO: direkt in einer Datenbankabfrage ohne schleife !
		$POST_award_user_id = $row['mem_usr_id'];
		
		$NewAWAObj = new Entity($gDb, TBL_USER_AWARDS.' ', 'awa');
		$NewAWAObj->setValue('awa_cat_id',$POST_award_cat_id);
		$NewAWAObj->setValue('awa_org_id',$gCurrentOrganization->getValue('org_id'));
		$NewAWAObj->setValue('awa_usr_id',$POST_award_user_id);
		if($POST_award_name_old_id>0)
		{
			$sql    = 'SELECT awa_name FROM '.TBL_USER_AWARDS.'  Where awa_id=\''.$POST_award_name_old_id.'\';';
			$result= $gDb->query($sql)->fetch();
			$NewAWAObj->setValue('awa_name',$result['awa_name']);
		}else
		{
			$NewAWAObj->setValue('awa_name',$POST_award_name_new);
		}
		$NewAWAObj->setValue('awa_info',$POST_award_info);
		$NewAWAObj->setValue('awa_date',$InternalDate);
		$NewAWAObj->save();
		$recordCount+=1;
		unset($POST_award_user_id);
		$newID+=1;
		}
		$POST_award_role_id = 0;
		//unset($POST_award_cat_id);
		//unset($POST_award_leader);
		//unset($POST_award_name_old_id);
		//unset($POST_award_name_new);
		//unset($POST_award_info);
		//unset($POST_award_date);
		$page->addHtml('<p><font color="#3ADF00"><i class="fas fa-check-circle"></i>&nbsp;'.$recordCount.' '.$gL10n->get('AWA_SUCCESS_NEW').'</font></p>');
}
	}else
{
	$page->addHtml( $ErrorStr);
}

}

// Html des Modules ausgeben

$page->addHtml('<form action="'.ADMIDIO_URL . FOLDER_PLUGINS .'/'.$plugin_folder.'/awards_change.php?awa_id='.$getAwardID.'" method="post">
<input type="hidden" name="award_new_id" value="'.$newID.'">
<div class="panel panel-default" id="edit_awards_form">
    <div class="panel-heading">'.$gL10n->get('AWA_HEADLINE_CHANGE').'</div>
    <div class="panel-body">

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
             WHERE usr_valid = 1'.$memberCondition.' ORDER BY last_name.usd_value, first_name.usd_value';
$query=$gDb->query($sql);
while($row=$query->fetch())
{
	if (isset($POST_award_user_id) && ($row['usr_id']==$POST_award_user_id))
	{
		$selected='selected';
	}else{
		$selected='';
	}
	$page->addHtml('<option value="'.$row['usr_id'].'"'.$selected.'>'.$row['last_name'].', '.$row['first_name'].'  ('.$row['birthday'].')</option>');
}

if ($plg_role_enabled ==0)
{
	$page->addHtml('</select></dl>');
}
else                                         // Wenn Rollen aktiv entsprechende Felder anzeigen   
{
	$page->addHtml('</select>');
	
	if($EditMode && !isset($_POST['submit']))
	{
		$page->addHtml('<select id="award_role_id" name="award_role_id" disabled hidden>
						<option value="0">'.$gL10n->get('AWA_ROLE_SELECT').'</option></select>');
	}
	else
	{
	    //Rollen auflisten
	    $page->addHtml('<dt><label for="award_rol_id">'.$gL10n->get('AWA_ROLE').'</label><span class="mandatoryFieldMarker" title="'.$gL10n->get('SYS_MANDATORY_FIELD').'">*</span></dt>
						<dd>');
	    
		$page->addHtml('<select id="award_role_id" name="award_role_id">
						<option value="0">'.$gL10n->get('AWA_ROLE_SELECT').'</option>');
	
        $sql    = 	'SELECT rol_id, rol_name FROM '. TBL_ROLES .' WHERE rol_valid =1';

        if ($plg_cat_id>0)
        {// Nur Rollen aus bestimmter Kategorien auflisten
            $sql    .= ' AND rol_cat_id ='.$plg_cat_id;
        }

        $query=$gDb->query($sql);
        while($row=$query->fetch())
        {
            if ($row['rol_id']==$POST_award_role_id)
            {
			$selected='selected';
            }else{
			$selected='';
            }
            $page->addHtml('<option value="'.$row['rol_id'].'"'.$selected.'>'.$row['rol_name'].'</option>');
        }
        if ($plg_leader_checked == 1)
        {
            $page->addHtml('</select><br>
						<label for="award_leader">'.$gL10n->get('AWA_LEADER').':  </label>
						<input type=checkbox name="award_leader" value="1" checked>');
        }
        else
        {
            $page->addHtml('</select><br>
					<label for="award_leader">'.$gL10n->get('AWA_LEADER').':  </label>
					<input type=checkbox name="award_leader" value="1">');
        }
	}
	$page->addHtml('</dl>');
}
$page->addHtml('<dl><dt><label for="award_cat_id">'.$gL10n->get('AWA_CAT').'</label><span class="mandatoryFieldMarker" title="'.$gL10n->get('SYS_MANDATORY_FIELD').'">*</span></dt>
                    <dd>
 			 <select id="award_cat_id" name="award_cat_id" >
			<option value="0">'.$gL10n->get('AWA_CAT_SELECT').'</option>');
//Kategorie auswahl füllen
$sql    = 'SELECT cat_id, cat_name FROM '.$g_tbl_praefix.'_categories WHERE cat_type=\'AWA\' AND cat_default=1;';
$query=$gDb->query($sql);
$default_category=$query->fetch();
$sql    = 'SELECT cat_id, cat_name FROM '.$g_tbl_praefix.'_categories WHERE cat_type=\'AWA\' ORDER BY cat_sequence;';
$query=$gDb->query($sql);
while($row=$query->fetch())
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
			<option value="0" >-------------------</option>');
//Dopdown für alte einträge füllen
$sql    = 'SELECT awa_name, awa_id FROM '.TBL_USER_AWARDS.'  ORDER BY awa_name ASC;';

$query=$gDb->query($sql);
$awardoldnames=array();
if($query != false){
    while($sqlrow=$query->fetch())
         {
           //echo "<pre>";
	   //print_r( $sqlrow);
           //print_r ($awardoldnames);
           //echo json_encode($awardoldnames);
           //echo "----------------------------------";
	   //echo "</pre>";
           $skip=False;
           foreach ($awardoldnames as $entry)
	   {
              if ($sqlrow['awa_name']==$entry['awa_name']) 
                {
                 $skip=True;
                 break;
                }
           }
           if (!$skip){
           	$awardoldnames[]=$sqlrow;
           }
        }
}

if (count($awardoldnames)>0)//list old entries if there are any
{
	foreach ($awardoldnames as $row)
	{
		if (isset($POST_award_name_old_name) && $row['awa_name']==$POST_award_name_old_name)
		{
			$selected='selected';
		}else{
			$selected='';
		}
		$page->addHtml('<option value="'.$row['awa_id'].'"'.$selected.'>'.$row['awa_name'].'</option>');
    }
}
unset($awardoldnames);

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
                    <input type="text" id="award_date" name="award_date" data-bs-provide="datepicker" data-date-format="dd.mm.yyyy" style="width: 90px;" 
                        maxlength="10"  value="'.$POST_award_date.'"  />'.$gL10n->get('AWA_HONOR_DATE_FORMAT').'
                    <span id="calendardiv" style="position: absolute; visibility: hidden;"></span></dd>
                </dl>
            
<div class="formSubmit">
            <button id="btnSave" type="submit" name="submit" value="submit"><i class="fas fa-save"></i>&nbsp;'.$gL10n->get('SYS_SAVE').'</button>
        </div>
    </div>
</div>
</form>
</p>');

$page->show();
?>
