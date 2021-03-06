<?php
/**
 * @package Abricos
 * @subpackage EShop
 * @copyright 2012-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;
$p = &$brick->param->param;

$imgWidth = bkint($p['imgw']);
$imgHeight = bkint($p['imgh']);

Abricos::GetModule('filemanager')->EnableThumbSize(array(
    array(
        "w" => $imgWidth,
        "h" => $imgHeight
    )
));

$man = EShopModule::$instance->GetManager()->cManager;

$catList = $man->CatalogList();

if (!empty($p['catids'])){
    $catids = explode(",", $p['catids']);
    $cCatList = $man->CatalogListByIds($catids);
} else {
    $cCat = $man->CatalogByAdress();

    if (empty($cCat)){
        $brick->content = "";
        return;
    }

    $cCat = $catList->Find($cCat->id);
    $cCatList = $cCat->childs;
}

$count = $cCatList->Count();
$lst = "";
for ($i = 0; $i < $count; $i++){
    $cat = $cCatList->GetByIndex($i);
    if ($cat->listDisable){
        continue;
    }

    if (empty($cat->foto)){
        $image = $v["imgempty"];
    } else {
        $image = Brick::ReplaceVarByData($v["img"], array(
            "src" => $cat->FotoSrc($imgWidth, $imgHeight)
        ));
    }
    $image = Brick::ReplaceVarByData($image, array(
        "w" => $imgWidth,
        "h" => $imgHeight
    ));

    $lst .= Brick::ReplaceVarByData($v['row'], array(
        "classcolumn" => $p['classcolumn'],
        "width" => $imgWidth,
        "image" => $image,
        "title" => addslashes(htmlspecialchars($cat->title)),
        "link" => $cat->URI()
    ));
}
$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "result" => Brick::ReplaceVarByData($v['table'], array(
        "rows" => $lst
    )),
    "brickid" => $brick->id
));
