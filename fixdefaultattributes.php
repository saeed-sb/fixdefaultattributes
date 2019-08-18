<?php
/**
* 2007-2019 PrestaShop
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
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fixdefaultattributes extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'fixdefaultattributes';
        $this->tab = 'quick_bulk_update';
        $this->version = '1.0.0';
        $this->author = 'Saeed Sattar Beglou';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Fix default Attributes');
        $this->description = $this->l('Fix default Attributes');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install();
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
        /**
         * If values have been submitted in the form, process.
         */
        $output = '';

        if (((bool)Tools::isSubmit('submitFixdefaultattributesModule')) == true) {
            $result = $this->postProcess();
            $output .= $result;
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitFixdefaultattributesModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Update All Product'),
                        'name' => 'FIXDEFAULTATTRIBUTES_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('if you want to fix all products default combination, enabled this and click on save button.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-file"></i>',
                        'name' => 'FIXDEFAULTATTRIBUTES_PRODUCT_ID',
                        'label' => $this->l('Product ID'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Update'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'FIXDEFAULTATTRIBUTES_LIVE_MODE' => false,
            'FIXDEFAULTATTRIBUTES_PRODUCT_ID' => null,
        );
    }

    /**
     * run process.
     */
    protected function postProcess()
    {
        if (Tools::getValue('FIXDEFAULTATTRIBUTES_LIVE_MODE') == true) {
            return $this->updateAll();
        } elseif (intval(Tools::getValue('FIXDEFAULTATTRIBUTES_PRODUCT_ID')) != null) {
            $id_product = intval(Tools::getValue('FIXDEFAULTATTRIBUTES_PRODUCT_ID'));
			$this->updateDefaultAttribute($id_product);
			StockAvailable::synchronize($id_product);
			
            return $this->displayConfirmation($this->l('Fix product Default Combination.'));
        }
        return $this->displayError($this->l('Please set the product ID or enabled the \"Update all product\"'));
    }

    public function updateAll()
    {
        $products = Product::getProducts($this->context->language->id, 1, 0, 'name', 'ASC', false, true);
        foreach ($products as $key) {
            Product::updateDefaultAttribute($key['id_product']);
        }
        return $this->displayConfirmation($this->l('fix all Products default combinations successfully.'));
    }
	
	public function updateDefaultAttribute($id_product)
	{
		//$id_default = Product::getDefaultAttribute($id_product);
		
		//$result = Db::getInstance()->ExecuteS("SELECT `id_product_attribute` FROM `ps_product_attribute` WHERE `id_product` = " . $id_product . " ORDER BY `quantity` DESC");
		$result = Db::getInstance()->ExecuteS('
			SELECT pa.id_product_attribute FROM ' . _DB_PREFIX_ . 'product_attribute pa
            LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (sa.id_product_attribute = pa.id_product_attribute)
            WHERE pa.id_product=' . $id_product . ' AND sa.quantity > 0 ORDER BY sa.quantity DESC
			');
		


						
		if (count($result) > 0) {
			$id_default = $result[0]['id_product_attribute'];
			
			Db::getInstance()->Execute("UPDATE `" . _DB_PREFIX_ . "product_attribute` SET `default_on` = null WHERE `id_product` = " . $id_product);
			Db::getInstance()->Execute("UPDATE `" . _DB_PREFIX_ . "product_attribute` SET `default_on` = '1' WHERE `id_product_attribute` = " . $id_default . " AND `id_product` = " . $id_product);
			
			Db::getInstance()->Execute("UPDATE `" . _DB_PREFIX_ . "product_attribute_shop` SET `default_on` = null WHERE `id_product` = " . $id_product);
			Db::getInstance()->Execute("UPDATE `" . _DB_PREFIX_ . "product_attribute_shop` SET `default_on` = '1' WHERE `id_product_attribute` = " . $id_default . " AND `id_product` = " . $id_product);
			
			Db::getInstance()->Execute("UPDATE `" . _DB_PREFIX_ . "product` SET `cache_default_attribute` = '" . $id_default . "' WHERE `id_product` = " . $id_product);
			Db::getInstance()->Execute("UPDATE `" . _DB_PREFIX_ . "product_shop` SET `cache_default_attribute` = '" . $id_default . "' WHERE `id_product` = " . $id_product);
		}
	}
}
