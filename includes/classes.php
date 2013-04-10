<?php 
/**
 * @package Abricos
 * @subpackage EShop
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class EShopConfig {
	
	/**
	 * @var EShopConfig
	 */
	public static $instance;
	
	/**
	 * Количество товаров на странице
	 * @var integer
	 */
	public $productPageCount = 12;
	
	/**
	 * SEO оптимизация страниц
	 * @var boolean
	 */
	public $seo = false;
	
	public function __construct($cfg){
		EShopConfig::$instance = $this;
		
		if (empty($cfg)){ $cfg = array(); }
		
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
			}else{
				$this->_calcURI = "/eshop/";
			} 
		}
		return $this->_calcURI;
	}
	
}

class EShopCatalogManager extends CatalogModuleManager {
	
	/**
	 * @var EShopManager
	 */
	public $manager;
	
	public function __construct(){
		$this->manager = EShopManager::$instance;

		parent::__construct("eshp");
		
		$this->CatalogClass = EShopCatalog;
	}
	
	public function IsAdminRole(){
		return $this->manager->IsAdminRole();
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
		if (Abricos::$adress->level == 1){
			$this->_cacheCatByAdress = $this->Catalog(0);
			return $this->_cacheCatByAdress;
		}
		
		$modSM = Abricos::GetModule("sitemap");
		$cat = null; $mItem = null;
		if (!empty($modSM)){
			$mList = SitemapModule::$instance->GetManager()->MenuList();
			$mItem = $mList->FindByPath(Abricos::$adress->dir, true);
			if (!empty($mItem)){
				$cat = $mItem->cat;
			}
		}
		if (!empty($cat)){
			$cat = $this->Catalog($cat->id);
		}
		
		$this->_cacheCatByAdress = $cat;
		
		return $this->_cacheCatByAdress;
	}
}


?>