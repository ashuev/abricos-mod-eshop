<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage EShop
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$brick = Brick::$builder->brick;
$db = Brick::$db;
$p = &$brick->param->param;
$v = &$brick->param->var;

$mod = EShopModule::$instance;

$catalog = $mod->GetCatalog();
$catalogManager = $mod->GetCatalogManager(); 

$smMenu = CMSRegistry::$instance->modules->GetModule('sitemap')->GetManager()->GetMenu();

$catItemMenu = $smMenu->menuLine[count($smMenu->menuLine)-1];
$catItem = $catItemMenu->source;

$link = $baseUrl = $catItemMenu->link; 
$imgWidth = bkint($p['imgw']);
$imgHeight = bkint($p['imgh']);

$listData = $mod->GetManager()->GetProductListData();
$listPage = $listData['listPage'];
if (intval($p['page'])>0){
	$listPage = intval($p['page']);
}

$catids = $listData['catids'];

if ($p['notchildlist']){
	$catids = array($catItem['id']);
}

$tempArr = array();

$custOrder = empty($p['custorder']) ? "fld_ord DESC" : $p['custorder'];

$rows = $catalogManager->ElementList($catids, $listPage, bkint($p['count']), $p['custwhere'], $custOrder, $p['overfields']);

$elTypeList = $catalogManager->ElementTypeListArray();

$lstResult = "";
$strList = array();

$etArr0 = $catalogManager->ElementOptionListByType($el['eltid'], true);

while (($row = $db->fetch_array($rows))){
	$el = $catalogManager->Element($row['id'], true);
	if (empty($tempArr[$el['catid']])){
		$tempArr[$el['catid']] = $smMenu->FindSource('id', $el['catid']);
	}
	$link = $tempArr[$el['catid']]->link;
	// Проверка, является ли товар Новинкой, Акцией или Хитом продаж
	$pr_spec = $el['fld_akc'] != 0 ? $v["pr_akc"] : "";
	$pr_spec .= $el['fld_new'] != 0 ? $v["pr_new"] : "";
	$pr_spec .= $el['fld_hit'] != 0 ? $v["pr_hit"] : "";
	$pr_spec11 = Brick::ReplaceVar($v["pr_spec0"], "pr_spec", $pr_spec);

	$imginfo = $db->fetch_array($catalogManager->FotoListThumb($el['elid'], $imgWidth, $imgHeight, 1));

	if (empty($imginfo)){
		$image = $brick->param->var["imgempty"];
		$image = Brick::ReplaceVar($brick->param->var["imgempty"], "pr_spec1", $pr_spec11);
	}else{
		$thumb = CatalogModule::FotoThumbInfoParse($imginfo['thumb']);
		
		$image = Brick::ReplaceVarByData($brick->param->var["img"], array(
			"src" => CatalogModule::FotoThumbLink($imginfo['fid'], $imgWidth, $imgHeight, $imginfo['fn']), 
			"w" => ($thumb['w']>0 ? $thumb['w']."px" : ""),
			"h" => ($thumb['h']>0 ? $thumb['h']."px" : ""),
			"pr_spec1" => $pr_spec11
		));
	}
	$replace = array(
		"tpl_btn" => $brick->param->var[$el['fld_sklad']==0 ? 'btnnotorder' : 'btnorder'],
		"image" => $image, 
		"title" => addslashes(htmlspecialchars($el['fld_name'])),
		"price" => $el['fld_price'],
		"desc" => $el['fld_desc'],
		"link" => $link."product_".$row['id']."/",
		"productid" => $row['id']
	);
	
	
	$etArr = $catalogManager->ElementOptionListByType($el['eltid'], true);
	$etArr = array_merge($etArr0, $etArr);
	
	foreach ($etArr as $etRow){
		$fld = "fld_".$etRow['nm'];
		
		// Если опция пуста - пробел, чтобы не рушить верстку
		$el[$fld] = !empty($el[$fld]) ? $el[$fld] : '&nbsp;';
		if ($etRow['nm'] != 'desc'){
			// $el[$fld] = htmlspecialchars($el[$fld]);
		}
		$replace[$fld] = $el[$fld];
		/*
		// Если тип опции - таблица (fldtp = 5), то необходимо получить значение опции из таблицы
		if	($row['fldtp'] == 5){
			// Получаем значение опции 'tl'. '' - т.к. тип товара - default 
			$val = $catalogManager->ElementOptionFieldTableValue('', $row['nm'], $el[$fld]);
			$replace[$fld] = $val['tl'];
		}/**/
		
		$replace["fldnm_".$etRow['nm']] = $etRow['tl'];
	}
	$isChangeType = false;
	$tpRow = $brick->param->var['row'];
	$elTypeId = $el['eltid'];
	if (!empty($elTypeList[$elTypeId])){
		$elTypeName = $elTypeList[$elTypeId]['nm'];
		if (!empty($brick->param->var['row-'.$elTypeName])){
			$tpRow = $brick->param->var['row-'.$elTypeName];
			$isChangeType = true;
		}
	}
	$strList[$isChangeType ? $elTypeId : 0] .= Brick::ReplaceVarByData($tpRow, $replace);
}

$lstResult = "";
foreach ($strList as $key => $value){
	$tpTable = $brick->param->var['table'];
	$elTypeName = $elTypeList[$key]['nm'];
	if (!empty($brick->param->var['table-'.$elTypeName])){
		$tpTable = $brick->param->var['table-'.$elTypeName];
	}
	$lstResult .= Brick::ReplaceVarByData($tpTable, array(
		"page" => $listPage, "rows" => $value
	));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"display" => $p['display'],
	"result" => $lstResult
));


?>