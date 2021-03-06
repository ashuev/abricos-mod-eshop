<?php
/**
 * @package Abricos
 * @subpackage EShop
 * @copyright 2012-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class EShopConfig
 */
class EShopConfig {

    /**
     * @var EShopConfig
     */
    public static $instance;

    /**
     * Количество товаров на странице
     *
     * @var integer
     */
    public $productPageCount = 12;

    /**
     * SEO оптимизация страниц
     *
     * @var boolean
     */
    public $seo = false;

    public function __construct($cfg){
        EShopConfig::$instance = $this;

        if (empty($cfg)){
            $cfg = array();
        }

        if (isset($cfg['productpagecount'])){
            $this->productPageCount = intval($cfg['productpagecount']);
        }

        if (isset($cfg['seo'])){
            $this->seo = $cfg['seo'];
        }
    }
}

class EShopCatalog extends Catalog {

    private $_calcURI = null;

    public function URI(){
        if (is_null($this->_calcURI)){
            if (!empty($this->parent)){
                $this->_calcURI = $this->parent->URI().$this->name."/";
            } else {
                $this->_calcURI = "/eshop/";
            }
        }
        return $this->_calcURI;
    }

}

class EShopElement extends CatalogElement {

    private $_calcURI = null;

    public function URI(){
        if (is_null($this->_calcURI)){
            $this->_calcURI = "";

            // TODO: Необходимо оптимизировать
            $catList = EShopCatalogManager::$instance->CatalogList();
            $cat = $catList->Find($this->catid);

            if (!empty($cat)){
                $this->_calcURI = $cat->URI();
            }

            $this->_calcURI .= "product_".$this->id."/";
        }
        return $this->_calcURI;
    }
}

class EShopElementList extends CatalogElementList {
    public function ToAJAX(){
        return parent::ToAJAX(EShopCatalogManager::$instance);
    }
}

class EShopCatalogManager extends CatalogModuleManager {

    /**
     * @var EShopCatalogManager
     */
    public static $instance = null;

    /**
     * @var EShopManager
     */
    public $manager;

    public function __construct(){
        $this->manager = EShopManager::$instance;

        EShopCatalogManager::$instance = $this;

        parent::__construct("eshp");

        $this->CatalogClass = 'EShopCatalog';
        $this->CatalogElementClass = 'EShopElement';
        $this->CatalogElementListClass = 'EShopElementList';
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function IsModeratorRole(){
        return $this->manager->IsModeratorRole();
    }

    public function IsOperatorRole(){
        return $this->manager->IsOperatorRole();
    }

    public function IsWriteRole(){
        return $this->manager->IsWriteRole();
    }

    public function IsViewRole(){
        return $this->manager->IsViewRole();
    }

    private $_cacheCatByAdress = null;

    /**
     * Вернуть каталог согласно текущему адресу запрашиваемой страницы
     *
     * @return EShopCatalog
     */
    public function CatalogByAdress(){
        if (!is_null($this->_cacheCatByAdress)){
            return $this->_cacheCatByAdress;
        }
        $dir = Abricos::$adress->dir;
        if (Abricos::$adress->level <= 1 || substr($dir[1], 0, 4) == 'page'){
            $this->_cacheCatByAdress = $this->Catalog(0);
            return $this->_cacheCatByAdress;
        }

        $modSM = Abricos::GetModule("sitemap");
        $cat = null;
        $mItem = null;
        if (!empty($modSM)){
            $mList = SitemapModule::$instance->GetManager()->MenuList();

            $miEshop = $mList->FindByPath('eshop');
            if (empty($miEshop)){
                require_once 'smclasses.php';

                $miEshop = new EShopRootMenuItem($mList);
                $mList->Add($miEshop);
                EShopModule::$instance->GetManager()->Sitemap_MenuBuild($miEshop);
            }

            $arr = array();
            foreach ($dir as $d){
                if (substr($d, 0, 4) == 'page'){
                    continue;
                }
                array_push($arr, $d);
            }
            $mItem = $mList->FindByPath($arr, true);
            if (!empty($mItem)){
                $cat = isset($mItem->cat) ? $mItem->cat : 0;
            }
        }
        if (!empty($cat)){
            $cat = $this->Catalog($cat->id);
        }

        return ($this->_cacheCatByAdress = $cat);
    }

    public function CatalogByPath($path = ''){
        $a = explode('/', $path);

        if (!isset($a[0]) || $a[0] !== 'eshop'){
            return null;
        }

        $catList = $this->CatalogList();
        $cat = $catList->Get(0);
        array_shift($a);

        while (count($a) > 0){
            $name = array_shift($a);
            $cat = $cat->childs->GetByName($name);
            if (empty($cat)){
                return null;
            }
        }
        return $cat;
    }

    /**
     * @param integer $productid
     * @return EShopElement
     */
    public function Product($productid){
        $el = $this->Element($productid);

        $ext = array_merge(array(
            'price' => '',
            'akc' => '',
            'new' => '',
            'hit' => '',
            'sklad' => ''
        ), $el->detail->optionsBase);

        $el->ext['price'] = $ext['price'];
        $el->ext['akc'] = $ext['akc'];
        $el->ext['new'] = $ext['new'];
        $el->ext['hit'] = $ext['hit'];
        $el->ext['sklad'] = $ext['sklad'];

        return $el;
    }

    /**
     * @param mixed $cfg
     * @return EShopElementList
     */
    public function ProductList($cfg){
        if (empty($cfg)){
            $cfg = new CatalogElementListConfig();
        }

        $optionsBase = $this->ElementTypeList()->Get(0)->options;

        $ordOpt = $cfg->orders->AddByOption($optionsBase->GetByName("price"));
        if (!empty($ordOpt)){
            $ordOpt->zeroDesc = true;
        }

        // $cfg->extFields->Add($optionsBase->GetByName("price"));
        $cfg->extFields->Add($optionsBase->GetByName("akc"));
        $cfg->extFields->Add($optionsBase->GetByName("new"));
        $cfg->extFields->Add($optionsBase->GetByName("hit"));
        // $cfg->extFields->Add($optionsBase->GetByName("sklad"));

        return $this->ElementList($cfg);
    }

    public function ProductListToAJAX($cfg){
        $list = $this->ProductList($cfg);

        $ret = new stdClass();
        $ret->elements = $list->ToAJAX($this);
        return $ret;
    }
}
