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
use Admidio\Users\Entity\User;

$getUserUuid   = admFuncVariableIsValid($_GET, 'user_uuid', 'string', array('defaultValue' => $gCurrentUser->getValue('usr_uuid')));

require_once(__DIR__ .'/awards_common.php');

if(!isAwardsDbInstalled())
{return;}

$user = new User($gDb, $gProfileFields);
$user->readDataByUuid($getUserUuid);

//Ehrungen aus Datenbank laden
$awards=awa_load_awards($user->getValue('usr_id'),true);

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
        <i class="bi bi-trash" data-bs-toggle="tooltip" title="'.$gL10n->get('AWA_DELETE_HONOR').'"></i></a>');
	 $page->addHtml('<a class="admidio-icon-link" href="'.$g_root_path.'/adm_plugins/awards/awards_change.php?awa_id='.$row['awa_id'].'">
        <i class="bi bi-pencil-square" data-bs-toggle="tooltip" title="'.$gL10n->get('AWA_EDIT_HONOR').'"></i></a>');
	}
	$page->addHtml('</div>');//Float right
	$page->addHtml('<div style="clear:both"></div></li>');
}

$page->addHtml('</ul></div></div>');
//Move content to correct position by jquery
$page->addHtml('<script>$("#awards_box").insertBefore("#profile_roles_box");</script>');


