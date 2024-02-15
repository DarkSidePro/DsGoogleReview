<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once ('vendor/autoload.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dsgooglereviews extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'dsgooglereviews';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Dark-Side.pro';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DS: Google Reviews');
        $this->description = $this->l('Display Google Reviews on home page in your store');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        Configuration::set('ds_google_place_id', '');
        Configuration::set('ds_googe_api_key', '');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $categoryData = $this->getCategoryData();
        $token = Tools::getAdminTokenLite('AdministratorDshomecategories');

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('categoryData', $categoryData);
        $this->context->smarty->assign('token', $token);
        $this->context->smarty->assign('link', $this->context->link);
        $this->context->smarty->assign('namemodules', $this->name);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayHome()
    {
        $categories = $this->getHomeActiveCategories();
        $this->context->smarty->assign('categories', $categories);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/hook/displayHome.tpl');

        return $output;
    }

    private function getCategories()
    {
        $sql = new DbQuery();
        $sql->select('id_category')
            ->from('category');
        
        $result = Db::getInstance()->executeS($sql);         

        return $result;
    }

    private function createCategoryEntry($id)
    {
        $sql = Db::getInstance()->insert('dshomecategory', array(
            'category_id' => (int) $id,
            'status' => 0
        ));
    }

    private function initCategoryData()
    {
        $categoryIds = $this->getCategories();



        foreach ($categoryIds as $id) {        
            $id_category = $id['id_category'];

            $this->createCategoryEntry($id_category);
        }
    }

    private function getCategoryData()
    {
        $sql = new DbQuery();
        $sql->select('dshc.*, cl.name')
            ->from('dshomecategory', 'dshc')
            ->leftJoin('category_lang', 'cl', 'cl.id_category = dshc.category_id')
            ->groupBy('dshc.id')
            ->where('cl.id_lang =' . $this->context->language->id);
        
        $result = Db::getInstance()->executeS($sql);         

        return $result;
    }

    private function getHomeActiveCategories()
    {
        $sql = new DbQuery();
        $sql->select('dshc.category_id, cl.name, cl.link_rewrite')
            ->from('dshomecategory', 'dshc')
            ->leftJoin('category_lang', 'cl', 'cl.id_category = dshc.category_id')
            ->where('cl.id_lang =' . $this->context->language->id . ' AND dshc.status = 1');

        $result = Db::getInstance()->executeS($sql);         

        return $result;   
    }

    public function hookActionCategoryAdd($params)
    {
        $categoryId = $params['id_category'];

        $this->createCategoryEntry($categoryId);
    }

    public function hookActionCategoryDelete($params)
    {
        $categoryId = $params['id_category'];

        $this->removeCategoryEntry($categoryId);
    }

    private function removeCategoryEntry($categoryId)
    {
        $sql = 'DELETE FROM '._DB_PREFIX_.'dshomecategory WHERE category_id =' . $categoryId;
        Db::getInstance()->execute($sql);
    }
}
