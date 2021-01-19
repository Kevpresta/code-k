<?php
/**
* 2007-2020 PrestaShop
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
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Px_Kezia extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'Px_Kezia';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Tony Chauveau';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Kezia');
        $this->description = $this->l('Intégration avec le logiciel Kezia');

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('PX_KEZIA_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayOrderConfirmation');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PX_KEZIA_LIVE_MODE');

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
        if (((bool)Tools::isSubmit('submitPx_KeziaModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

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
        $helper->submit_action = 'submitPx_KeziaModule';
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
                        'label' => $this->l('Live mode'),
                        'name' => 'PX_KEZIA_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'PX_KEZIA_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'PX_KEZIA_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
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
            'PX_KEZIA_LIVE_MODE' => Configuration::get('PX_KEZIA_LIVE_MODE', true),
            'PX_KEZIA_ACCOUNT_EMAIL' => Configuration::get('PX_KEZIA_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'PX_KEZIA_ACCOUNT_PASSWORD' => Configuration::get('PX_KEZIA_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
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

    public function hookDisplayOrderConfirmation($params)
    {
        if (isset($params['objOrder']) && Validate::isLoadedObject($params['objOrder'])) {
            $order = $params['objOrder'];
        } elseif (isset($params['order']) && Validate::isLoadedObject($params['order'])) {
            $order = $params['order'];
        } else {
            return;
        }

        $address = new Address($order->id_address_delivery);
        $customer = $order->getCustomer();
        $date_add = new DateTime($order->date_add);
        $datecde = $date_add->format('Ymd');
        $customer_name = $customer->firstname." ".$customer->lastname;

        $sql = "INSERT INTO WEB_COMMANDE
        (`CODE_WEB`, `LIV`, `ADR1`, `ADR2`, `CP`, `VILLE`, `PAYS`, `DATECDE`, `PORT`)
        VALUES (
            $order->id, 
            $customer_name,
            $address->alias,
            $address->address1,
            $address->address2,
            $address->postcode,
            $address->city,
            $address->country,
            $datecde,
            $order->total_shipping
        )";
        Db::getInstance()->Execute($sql);
        $id = Db::getInstance()->Insert_ID();
        
        foreach ($order->getProducts() as $row) {
            $product = new Product((int) $row['product_id']);

            $idart = $row['product_id'];
            $q_cde = $row['product_quantity'];
            $prix_ttc = round($row['unit_price_tax_incl'], 2);
            $tauxtva = $product->tax_rate;

            $sql = "INSERT INTO WEB_LI_CDE
            (`NO_WEB`, `IDART`, `Q_CDE`, `PRIX_TTC`, `TAUXTVA`) 
            VALUES (
                $id,
                $idart,
                $q_cde,
                $prix_ttc,
                $tauxtva
            )";
            Db::getInstance()->Execute($sql);
        }

        $date_add = new DateTime($customer->date_add);
        $datecreation = $date_add->format('Ymd');

        $sql = "INSERT INTO WEB_CLIENT
        (`NOMCLI`, `ADR1`, `ADR2`, `CP`, `VILLE`, `PAYS`, `TELPERSO`, `GSM`, `EMAIL`, `DATECREATION`)
        VALUES (
            $customer_name,
            $address->address1,
            $address->address2,
            $address->postcode,
            $address->city,
            $address->country,
            $address->phone,
            $address->phone_mobile,
            $customer->email,
            $datecreation
        )";
        Db::getInstance()->Execute($sql);
    }

    public function hookActionCronJob() {
            
        $sql = "SELECT * FROM WEB_ARTICLE WHERE TRAITE = 0";
        $results=Db::getInstance()->ExecuteS($sql);
        foreach($results as $art) {
            $product = new Product();
            $product->ean13 = $art["MULTI_CODE"];
            $product->name = $art["DESIGNATION"];
            $product->redirect_type = '404';
            $product->price = $art["PRIX_TTC"];
            $product->quantity = $art["stock"];
            $product->minimal_quantity = 1;
            $product->show_price = 1;
            $product->on_sale = 0;
            $product->online_only = 1;
            $product->is_virtual = 0;
            $product->tax_rate = $art["TAUX_TVA"];
            $product->ecotax = $art["ECOTAX"];
            $product->add();
            Db::getInstance()->Execute("UPDATE WEB_ARTICLE SET TRAITE = 1 WHERE IDART = ".$art["IDART"]);
        }
        
    }

    public function getCronFrequency() {
        return array(
            'hour' => 5,
            'day' => -1, 
            'month' => -1,
            'day_of_week' => -1
        );
    }
}

