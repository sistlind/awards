<?php
/******************************************************************************
 * Awards
 *
 * Diese Plugin ordnet Mitgliedern Ehrungen/Auszeichnungen/Lehrgänge zu
 * 
 * https://github.com/sistlind/awards
 *                  
 *****************************************************************************/
//Falls Datenbank nicht vorhanden überspringen
$getUserId = admFuncVariableIsValid($_GET, 'user_id', 'numeric', array('defaultValue' => $gCurrentUser->getValue('usr_id')));

require_once(SERVER_PATH. '/adm_plugins/awards/awards_common.php');

if(!isAwardsDbInstalled())
{return;}
//Ehrungen aus Datenbank laden
$awards=awa_load_awards($getUserId,true);

$user = new User($gDb, $gProfileFields, $getUserId);

if ($awards==false)
{
	return;
}

	$page->addHtml('<div class="card admidio-field-group" id="awards_box">
				<div class="card-header">'.$gL10n->get('AWA_HEADLINE').'&nbsp;</div>
                <div id="awards_box_body" class="card-body">');
//Tabellenkopf
unset($PrevCatName);
foreach($awards as $row)
{
	if (!isset($PrevCatName) || ($PrevCatName!=$row['awa_cat_name']))
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
	$sql='Select COUNT(*) as count FROM '.TBL_ORGANIZATIONS;
	$query=$gDb->query($sql);
	$result=$query->fetch();
	if($result['count']>1)//only show organisation, if multiple organisations are present
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
	 $page->addHtml('<a class="admidio-icon-link" href="'.$g_root_path.'/adm_plugins/awards/awards_delete.php?awa_id='.$row['awa_id'].'">
        <i class="fas fa-trash"></i>'.$gL10n->get('AWA_DELETE_HONOR').'</a>');
	 $page->addHtml('<a class="admidio-icon-link" href="'.$g_root_path.'/adm_plugins/awards/awards_change.php?awa_id='.$row['awa_id'].'">
        <i class="fas fa-edit"></i>'.$gL10n->get('AWA_EDIT_HONOR').'</a>');
	}
	$page->addHtml('</div>');//Float right
	$page->addHtml('<div style="clear:both"></div></li>');
}

$page->addHtml('</ul></div></div>');
//Move content to correct position by jquery
$page->addHtml('<script>$("#awards_box").insertBefore("#profile_roles_box");</script>');


