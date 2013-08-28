<?php
/**
 * @package Abricos
 * @subpackage EShop
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = &$brick->param->param;
$v = &$brick->param->var;

$cManager = EShopModule::$instance->GetManager()->cManager;

$extFilterCol = "";

if (!empty($p['extfilter'])){
	$aEF = explode(":", $p['extfilter']);
	
	$elTypeList = $cManager->ElementTypeList();
	$elTypeBase = $elTypeList->Get(0);
	$option = $elTypeBase->options->GetByName($aEF[0]);
	if (!empty($option) && $option->type == Catalog::TP_TABLE){

		$lst = "";
		foreach ($option->values as $value){
			$lst .= Brick::ReplaceVarByData($v['option'], array(
				"id" => $value['id'],
				"tl" => htmlspecialchars($value['tl'])
			));
		}
		$extFilterCol = Brick::ReplaceVarByData($v["textfilter"], array(
			"fld" => $option->name,
			"select" => Brick::ReplaceVarByData($v['select'], array(
				"tl" => empty($aEF[1]) ? "" : $aEF[1],
				"rows" => $lst
			))
		));
	}
	
}

$query = Abricos::CleanGPC('g', 'q', TYPE_STR);
$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"query" => htmlspecialchars($query),
	"extfiltercol" => $extFilterCol
));

?>