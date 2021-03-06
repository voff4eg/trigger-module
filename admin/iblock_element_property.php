<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fevent/include.php");

$CURRENCY_RIGHT = $APPLICATION->GetGroupRight("fevent");
if ($CURRENCY_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fevent/admin/", "/trigger_events.php"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fevent/prolog.php");

$FN = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST["FN"]);
$FC = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST["FC"]);
if($FN == "")
	$FN = "find_form";
if($FC == "")
	$FC = "USER_ID";

if (isset($_REQUEST['JSFUNC']))
{
	$JSFUNC = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST['JSFUNC']);
}
else
{
	$JSFUNC = '';
}
// идентификатор таблицы
$sTableID = "tbl_user_popup";

// инициализация сортировки
$oSort = new CAdminSorting($sTableID, "ID", "asc");
// инициализация списка
$lAdmin = new CAdminList($sTableID, $oSort);

// инициализация параметров списка - фильтры
$arFilterFields = Array(
	"find",
	"find_type",
	"find_id",
	"find_timestamp_1",
	"find_timestamp_2",
	"find_last_login_1",
	"find_last_login_2",
	"find_active",
	"find_login",
	"find_name",
	"find_email",
	"find_keywords",
	"find_group_id"
	);

$lAdmin->InitFilter($arFilterFields);

//инициализация массива фильтра для GetList
function CheckFilter($FilterArr) // проверка введенных полей
{
	global $strError;
	foreach($FilterArr as $f)
		global $$f;

	$str = "";
	if(strlen(trim($find_timestamp_1))>0 || strlen(trim($find_timestamp_2))>0)
	{
		$date_1_ok = false;
		$date1_stm = MkDateTime(FmtDate($find_timestamp_1,"D.M.Y"),"d.m.Y");
		$date2_stm = MkDateTime(FmtDate($find_timestamp_2,"D.M.Y")." 23:59","d.m.Y H:i");
		if (!$date1_stm && strlen(trim($find_timestamp_1))>0)
			$str.= GetMessage("MAIN_WRONG_TIMESTAMP_FROM")."<br>";
		else $date_1_ok = true;
		if (!$date2_stm && strlen(trim($find_timestamp_2))>0)
			$str.= GetMessage("MAIN_WRONG_TIMESTAMP_TILL")."<br>";
		elseif ($date_1_ok && $date2_stm <= $date1_stm && strlen($date2_stm)>0)
			$str.= GetMessage("MAIN_FROM_TILL_TIMESTAMP")."<br>";
	}

	if(strlen(trim($find_last_login_1))>0 || strlen(trim($find_last_login_2))>0)
	{
		$date_1_ok = false;
		$date1_stm = MkDateTime(FmtDate($find_last_login_1,"D.M.Y"),"d.m.Y");
		$date2_stm = MkDateTime(FmtDate($find_last_login_2,"D.M.Y")." 23:59","d.m.Y H:i");
		if(!$date1_stm && strlen(trim($find_last_login_1))>0)
			$str.= GetMessage("MAIN_WRONG_LAST_LOGIN_FROM")."<br>";
		else
			$date_1_ok = true;
		if(!$date2_stm && strlen(trim($find_last_login_2))>0)
			$str.= GetMessage("MAIN_WRONG_LAST_LOGIN_TILL")."<br>";
		elseif($date_1_ok && $date2_stm <= $date1_stm && strlen($date2_stm)>0)
			$str.= GetMessage("MAIN_FROM_TILL_LAST_LOGIN")."<br>";
	}

	$strError .= $str;
	if(strlen($str)>0)
	{
		global $lAdmin;
		$lAdmin->AddFilterError($str);
		return false;
	}

	return true;
}

$arFilter = Array();
if(CheckFilter($arFilterFields))
{
	$arFilter = Array(
		"ID"			=> $find_id,
		"TIMESTAMP_1"	=> $find_timestamp_1,
		"TIMESTAMP_2"	=> $find_timestamp_2,
		"LAST_LOGIN_1"	=> $find_last_login_1,
		"LAST_LOGIN_2"	=> $find_last_login_2,
		"ACTIVE"		=> $find_active,
		"LOGIN"			=>	($find!='' && $find_type == "login"? $find: $find_login),
		"NAME"			=>	($find!='' && $find_type == "name"? $find: $find_name),
		"EMAIL"			=>	($find!='' && $find_type == "email"? $find: $find_email),
		"KEYWORDS"		=> $find_keywords,
		"GROUPS_ID"		=> $find_group_id
		);
}

if(!$USER->CanDoOperation('view_all_users'))
{
	$arUserSubordinateGroups = array();
	$arUserGroups = CUser::GetUserGroup($USER->GetID());
	foreach($arUserGroups as $grp)
		$arUserSubordinateGroups = array_merge($arUserSubordinateGroups, CGroup::GetSubordinateGroups($grp));

	$arFilter["CHECK_SUBORDINATE"] = array_unique($arUserSubordinateGroups);
}

// инициализация списка - выборка данных
if (CModule::IncludeModule("iblock")){
	// здесь необходимо использовать функции модуля "Информационные блоки"
	$rsData = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>5));	
    //$rsData = CIBlockElement::GetList(array($by => $order), $arFilter);    
	$rsData = new CAdminResult($rsData, $sTableID);
	$rsData->NavStart();
	
	// построение списка
	while($arRes = $rsData->NavNext(true, "f_"))
	{		
		/*$row =& $lAdmin->AddRow($f_ID, $arRes, "group_edit.php?lang=".LANGUAGE_ID."&ID=".$f_ID, GetMessage("MAIN_EDIT_TITLE"));
		$row->AddViewField("ID", "<a href='group_edit.php?lang=".LANGUAGE_ID."&ID=".$f_ID."' title='".GetMessage("MAIN_EDIT_TITLE")."'>".$f_ID."</a>");


		if ($USER->CanDoOperation('edit_groups'))
		{
			if($f_ID <= 2)
				$row->AddCheckField("ACTIVE", false);
			else
				$row->AddCheckField("ACTIVE");

			$row->AddInputField("C_SORT");
			$row->AddInputField("NAME");
			$row->AddInputField("DESCRIPTION");
		}
		else
		{
			$row->AddCheckField("ACTIVE", false);
			$row->AddViewField("C_SORT", $f_C_SORT);
			$row->AddViewField("NAME", $f_NAME);
			$row->AddViewField("DESCRIPTION", $f_DESCRIPTION);
		}

		if ($f_ID!=2)
			$row->AddViewField("USERS", "<a href='user_admin.php?lang=".LANGUAGE_ID."&find_group_id[]=".$f_ID."&set_filter=Y' title='".GetMessage("USERS_OF_GROUP")."'>".$f_USERS."</a>");

		$arActions = Array();

		if(IntVal($f_ID)>2 && $USER->CanDoOperation('edit_groups'))
		{
			//$arActions[] = array("ICON"=>"edit", "TEXT"=>GetMessage("MAIN_ADMIN_MENU_EDIT"), "ACTION"=>$lAdmin->ActionRedirect("group_edit.php?ID=".$f_ID));
			$arActions[] = array("ICON"=>"edit", "TEXT"=>GetMessage("MAIN_ADMIN_MENU_EDIT"), "ACTION"=>"javascript:SelEl('".CUtil::JSEscape($f_ID)."', '".CUtil::JSEscape($f_NAME)."')");
			//$arActions[] = array("ICON"=>"copy", "TEXT"=>GetMessage("MAIN_ADMIN_MENU_COPY"), "ACTION"=>$lAdmin->ActionRedirect("group_edit.php?COPY_ID=".$f_ID));
			$arActions[] = array("ICON"=>"copy", "TEXT"=>GetMessage("MAIN_ADMIN_MENU_COPY"), "ACTION"=>"javascript:SelEl('".CUtil::JSEscape($f_ID)."', '".CUtil::JSEscape($f_NAME)."')");
			$arActions[] = array("SEPARATOR"=>true);
			$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("MAIN_ADMIN_MENU_DELETE"), "ACTION"=>"if(confirm('".GetMessage('CONFIRM_DEL_GROUP')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"));
		}
		else
		{
			$arActions[] = array("ICON" => "view", "TEXT" => GetMessage("VIEW"), "ACTION" => "javascript:SelEl('".CUtil::JSEscape($f_ID)."', '".CUtil::JSEscape($f_NAME)."')");
		}

		$row->AddActions($arActions);*/
		$row =& $lAdmin->AddRow($arRes["ID"], $arRes);
		$row->AddViewField("NAME", $arRes["NAME"]."<input type=hidden name='n".$arRes["ID"]."' id='name_".$arRes["ID"]."' value='".CUtil::JSEscape(htmlspecialcharsbx($arRes["NAME"]))."'>");
		$row->AddCheckField("ACTIVE");

		$row->AddActions(array(
			array(
				"DEFAULT" => "Y",
				"TEXT" => GetMessage("IBLOCK_ELSEARCH_SELECT"),
				"ACTION"=>"javascript:SelEl('".CUtil::JSEscape($get_xml_id? $arRes["XML_ID"]: $arRes["ID"])."', '".CUtil::JSEscape($arRes["NAME"])."')",
			),
		));
	}
}

// установке параметров списка
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));

// заголовок списка
$lAdmin->AddHeaders(array(
	array("id"=>"ID",				"content"=>"ID", 	"sort"=>"id", "default"=>true, "align"=>"right"),
	array("id"=>"TIMESTAMP_X",		"content"=>GetMessage('TIMESTAMP'), "sort"=>"timestamp_x", "default"=>true),
	array("id"=>"ACTIVE", 			"content"=>GetMessage('ACTIVE'),	"sort"=>"active", "default"=>true),
	array("id"=>"C_SORT", 			"content"=>GetMessage("MAIN_C_SORT"),  "sort"=>"c_sort", "default"=>true, "align"=>"right"),
	array("id"=>"NAME",				"content"=>GetMessage("NAME"), "sort"=>"name",	"default"=>true),
	array("id"=>"DESCRIPTION", 		"content"=>GetMessage("MAIN_DESCRIPTION"),  "sort"=>"description", "default"=>false),
	array("id"=>"USERS", 			"content"=>GetMessage('MAIN_USERS'),  "sort"=>"users", "default"=>true, "align"=>"right"),

));

// "подвал" списка
$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

$lAdmin->AddAdminContextMenu(array());

// проверка на вывод только списка (в случае списка, скрипт дальше выполняться не будет)
$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("MAIN_PAGE_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php")
?>
<script language="JavaScript">
<!--
function SetValue(id)
{
	<?if ($JSFUNC <> ''){?>
	window.opener.SUV<?=$JSFUNC?>(id);
	<?}else{?>
	window.opener.document.<?echo $FN;?>["<?echo $FC;?>"].value=id;
	if (window.opener.BX)
		window.opener.BX.fireEvent(window.opener.document.<?echo $FN;?>["<?echo $FC;?>"], 'change');
	window.close();
	<?}?>
}
//-->
</script>
<script type="text/javascript">
function SelEl(id, name)
{
<?
	if ('' != $lookup)
	{
		if ('' != $m)
		{
			?>window.opener.<? echo $lookup; ?>.AddValue(id);<?
		}
		else
		{
			?>
	window.opener.<? echo $lookup; ?>.AddValue(id);
	window.close();<?
		}
	}
	else
	{
		?><?if($m):?>
	window.opener.InS<? echo md5($n)?>(id, name);
	<?else:?>
	el = window.opener.document.getElementById('[]');
	if(!el)
		el = window.opener.document.getElementById('<?echo $n?>');
	if(el)
	{
		el.value = id;
		if (window.opener.BX)
			window.opener.BX.fireEvent(el, 'change');
	}
	el = window.opener.document.getElementById('sp_<?echo md5($n)?>_<?echo $k?>');
	if(!el)
		el = window.opener.document.getElementById('sp_<?echo $n?>');
	if(!el)
		el = window.opener.document.getElementById('<?echo $n?>_link');
	if(el)
		el.innerHTML = name;
	window.close();
		<?endif;?><?
	}
	?>
}

function SelAll()
{
	var frm = document.getElementById('form_<?echo $sTableID?>');
	if(frm)
	{
		var e = frm.elements['ID[]'];
		if(e && e.nodeName)
		{
			var v = e.value;
			var n = document.getElementById('name_'+v).value;
			SelEl(v, n);
		}
		else if(e)
		{
			var l = e.length;
			for(i=0;i<l;i++)
			{
				var a = e[i].checked;
				if (a == true)
				{
					var v = e[i].value;
					var n = document.getElementById('name_'+v).value;
					SelEl(v, n);
				}
			}
		}
		window.close();
	}
}
</script>

<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?
$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array(
		GetMessage('MAIN_FLT_USER_ID'),
		GetMessage('MAIN_FLT_MOD_DATE'),
		GetMessage('MAIN_FLT_AUTH_DATE'),
		GetMessage('MAIN_FLT_ACTIVE'),
		GetMessage('MAIN_FLT_LOGIN'),
		GetMessage('MAIN_FLT_EMAIL'),
		GetMessage('MAIN_FLT_FIO'),
		GetMessage('MAIN_FLT_PROFILE_FIELDS'),
		GetMessage('MAIN_FLT_USER_GROUP')
	)
);

$oFilter->Begin();
?>
<tr>
	<td><b><?=GetMessage("MAIN_FLT_SEARCH")?></b></td>
	<td nowrap>
		<input type="text" size="25" name="find" value="<?echo htmlspecialcharsbx($find)?>" title="<?=GetMessage("MAIN_FLT_SEARCH_TITLE")?>">
		<select name="find_type">
			<option value="login"<?if($find_type=="login") echo " selected"?>><?=GetMessage('MAIN_FLT_LOGIN')?></option>
			<option value="email"<?if($find_type=="email") echo " selected"?>><?=GetMessage('MAIN_FLT_EMAIL')?></option>
			<option value="name"<?if($find_type=="name") echo " selected"?>><?=GetMessage('MAIN_FLT_FIO')?></option>
		</select>
	</td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_ID")?></td>
	<td><input type="text" name="find_id" size="47" value="<?echo htmlspecialcharsbx($find_id)?>"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_TIMESTAMP").":"?></td>
	<td><?echo CalendarPeriod("find_timestamp_1", htmlspecialcharsbx($find_timestamp_1), "find_timestamp_2", htmlspecialcharsbx($find_timestamp_2), "find_form","Y")?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_LAST_LOGIN").":"?></td>
	<td><?echo CalendarPeriod("find_last_login_1", htmlspecialcharsbx($find_last_login_1), "find_last_login_2", htmlspecialcharsbx($find_last_login_2), "find_form","Y")?></td>
</tr>
<tr>
	<td><?echo GetMessage("F_ACTIVE")?></td>
	<td><?
		$arr = array("reference"=>array(GetMessage("MAIN_YES"), GetMessage("MAIN_NO")), "reference_id"=>array("Y","N"));
		echo SelectBoxFromArray("find_active", $arr, htmlspecialcharsbx($find_active), GetMessage('MAIN_ALL'));
		?>
	</td>
</tr>
<tr>
	<td><?echo GetMessage("F_LOGIN")?></td>
	<td><input type="text" name="find_login" size="47" value="<?echo htmlspecialcharsbx($find_login)?>"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_EMAIL")?></td>
	<td><input type="text" name="find_email" value="<?echo htmlspecialcharsbx($find_email)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("F_NAME")?></td>
	<td><input type="text" name="find_name" value="<?echo htmlspecialcharsbx($find_name)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td><?echo GetMessage("MAIN_F_KEYWORDS")?></td>
	<td><input type="text" name="find_keywords" value="<?echo htmlspecialcharsbx($find_keywords)?>" size="47"><?=ShowFilterLogicHelp()?></td>
</tr>
<tr valign="top">
	<td><?echo GetMessage("F_GROUP")?><br><img src="/bitrix/images/main/mouse.gif" width="44" height="21" border="0" alt=""></td>
	<td><?
	$z = CGroup::GetDropDownList("AND ID!=2");
	echo SelectBoxM("find_group_id[]", $z, $find_group_id, "", false, 10);
	?></td>
</tr>
<input type="hidden" name="FN" value="<?echo htmlspecialcharsbx($FN)?>">
<input type="hidden" name="FC" value="<?echo htmlspecialcharsbx($FC)?>">
<input type="hidden" name="JSFUNC" value="<?echo htmlspecialcharsbx($JSFUNC)?>">
<?
$oFilter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"find_form"));
$oFilter->End();
?>
</form>
<?
// место для вывода списка
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>
