<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fevent/classes/general/ftrigger_actions.php");

class CFTriggerActions extends CAllFTriggerActions{
	//TODO: 
	function __GetList(&$by, &$order, $lang = LANGUAGE_ID)
	{
		global $DB;

		$strSql =
			"SELECT * ".
			"FROM f_actions ";

		if (strtolower($by) == "name") $strSqlOrder = " ORDER BY NAME ";
		else
		{
			$strSqlOrder = " ORDER BY SORT ";
			$by = "sort";
		}

		if ($order=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}
}

class CFTriggerActionTypes extends CAllFTriggerActionTypes{
	function __GetList(&$by, &$order, $lang = LANGUAGE_ID){
		global $DB;

		$strSql =
			"SELECT * ".
			"FROM f_action_types ";

		if (strtolower($by) == "name") $strSqlOrder = " ORDER BY NAME ";
		else
		{
			$strSqlOrder = " ORDER BY SORT ";
			$by = "sort";
		}

		if ($order=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;	
	}
}
?>
