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
//Falls Datenbank nicht vorhanden überspringen
$tablename=$g_tbl_praefix.'_user_awards';
$sql_dBcheck="SHOW TABLES LIKE '".$tablename."'"; 
$query=$gDb->query($sql_dBcheck);
if(mysql_num_rows($query)>0)
{//erst ausführen, wenn Tabelle vorhanden, $query wird hier überschrieben!
	$sql    = 'SELECT awa_id, awa_usr_id, awa_cat_id,awa_name, awa_info, awa_date, 
		awa_cat_seq.cat_sequence as awa_cat_seq,
		awa_cat_name.cat_name as awa_cat_name,
		last_name.usd_value as last_name,
		first_name.usd_value as first_name
          FROM '.$g_tbl_praefix.'_user_awards
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
	Where awa_usr_id='.$getUserId.'
	ORDER BY awa_cat_seq, awa_date';
	//echo $sql;
	$query=$gDb->query($sql);
}
if (mysql_num_rows($query)>0)
{//Daten vorhanden, Ehrungen ausgeben!
$gL10n->addLanguagePath(SERVER_PATH. '/adm_plugins/awards/languages');
echo '<div class="groupBox profileRolesBox" id="awards_box">
				<div class="groupBoxHeadline">'.$gL10n->get('AWA_HEADLINE').'&nbsp;</div>
                <div id="awards_box_body" class="groupBoxBody">';
echo '<ul class="formFieldList">';
//Tabellenkopf
unset($PrevCatName);
while($row=$gDb->fetch_array($query))
{
echo '<li>';
if ($PrevCatName!=$row['awa_cat_name'])
{
	$PrevCatName=$row['awa_cat_name'];
	echo '<dt>'.$row['awa_cat_name'].'</dt>';
}

echo '<div style="text-align: left;float:left;">';
echo '<dd>';
echo $row['awa_name'];
if(strlen($row['awa_info'])>0)
{
   echo '&nbsp;('.$row['awa_info'].')';
}
echo '</dd>';

echo '</div><div style="text-align: right;float:right;">';
echo $gL10n->get('AWA_SINCE').' '.date('d.m.Y',strtotime($row['awa_date'])).' ';
if($gCurrentUser->editProfile($user))//Ändern/Löschen Buttons für berechtigte User
{
 echo '<a class="iconLink" href="'.$g_root_path.'/adm_plugins/awards/awards_delete.php?awa_id='.$row['awa_id'].'"><img src="'.THEME_PATH.'/icons/delete.png" alt="'.$gL10n->get('AWA_DELETE_HONOR').'" title="'.$gL10n->get('AWA_DELETE_HONOR').'" /></a>';
 echo '<a class="iconLink" href="'.$g_root_path.'/adm_plugins/awards/awards_change.php?awa_id='.$row['awa_id'].'"><img src="'.THEME_PATH.'/icons/edit.png" alt="'.$gL10n->get('AWA_EDIT_HONOR').'" title="'.$gL10n->get('AWA_EDIT_HONOR').'" /></a>';
}
echo '</div>';//Float right
echo '<div style="clear:both"></div></li>';
}

echo '	</ul>
</div>
</div>';

}
