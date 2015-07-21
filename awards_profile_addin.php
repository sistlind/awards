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
$getUserId = admFuncVariableIsValid($_GET, 'user_id', 'numeric', array('defaultValue' => $gCurrentUser->getValue('usr_id')));
if(mysql_num_rows($query)==0)
{return;}
//Ehrungen aus DAtenbank laden
$awards=awa_load_awards($getUserId,true);

if ($awards==false)
{
	return;
}
	//Daten vorhanden, Ehrungen ausgeben!
	$gL10n->addLanguagePath(SERVER_PATH. '/adm_plugins/awards/languages');
	$page->addHtml('<div class="panel panel-default" id="awards_box">
				<div class="panel-heading">'.$gL10n->get('AWA_HEADLINE').'&nbsp;</div>
                <div id="awards_box_body" class="panel-body">');
//Tabellenkopf
unset($PrevCatName);
foreach($awards as $row)
{
print_r($row);
	if ($PrevCatName!=$row['awa_cat_name'])
	{
		if(isset($PrevCatName))
		{//close last <ul>
			$page->addHtml('</ul>'); 		
		}
		$PrevCatName=$row['awa_cat_name'];
		$page->addHtml('<b>'.$row['awa_cat_name'].'</b>');
		$page->addHtml('<ul id="awards_cat_list" class="list-group admidio-list-roles-assign" style="padding-left:10px;">');
	}
	$page->addHtml('<li class= "list-group-item">');
	$page->addHtml('<div style="text-align: left;float:left;">');
	$page->addHtml($row['awa_name']);
//Multi-Org-Installation?
	$sql='Select COUNT(*) FROM '.TBL_ORGANIZATIONS;
	$query=$gDb->query($sql);
	$result=$gDb->fetch_array($query);
	if($result[0]>1)//only show organisation, if multiple organisations are present
	{
		$page->addHtml(' ('.$row['awa_org_name'].')');
	}
	if(strlen($row['awa_info'])>0)
	{
	   $page->addHtml('&nbsp;('.$row['awa_info'].')');
	}

	$page->addHtml('</div><div style="text-align: right;float:right;">');
	$page->addHtml($gL10n->get('AWA_SINCE').' '.date('d.m.Y',strtotime($row['awa_date'])).' ');
	if($gCurrentUser->hasRightEditProfile($user))//Ändern/Löschen Buttons für berechtigte User
	{
	 $page->addHtml('<a class="iconLink" href="'.$g_root_path.'/adm_plugins/awards/awards_delete.php?awa_id='.$row['awa_id'].'"><img src="'.THEME_PATH.'/icons/delete.png" alt="'.$gL10n->get('AWA_DELETE_HONOR').'" title="'.$gL10n->get('AWA_DELETE_HONOR').'" /></a>');
	 $page->addHtml('<a class="iconLink" href="'.$g_root_path.'/adm_plugins/awards/awards_change.php?awa_id='.$row['awa_id'].'"><img src="'.THEME_PATH.'/icons/edit.png" alt="'.$gL10n->get('AWA_EDIT_HONOR').'" title="'.$gL10n->get('AWA_EDIT_HONOR').'" /></a>');
	}
	$page->addHtml('</div>');//Float right
	$page->addHtml('<div style="clear:both"></div></li>');
}

$page->addHtml('	</ul>
</div>
</div>');
//Move content to correct position by jquery
$page->addHtml('<script>$("#awards_box").insertBefore(".admidio-admidio-info-created-edited");</script>');


