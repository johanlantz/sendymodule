<?php
/*
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SendyIntegration extends Module
{
    private $installation;
    private $setup;
    private $availableLanguages;

    public function __construct()
    {
        $this->name = 'sendyintegration';
        $this->tab = 'front_office_features';
        $this->version = '1.4';
        $this->author = 'Givensa';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7.99');

        $this->displayName = $this->l('Sendy Newsletter Integration');
        $this->description = $this->l('Sync newsletter subscribers and customers with your sendy installation');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->sendy_ip_field = "ipaddress";

        if (Configuration::get('SENDYNEWSLETTER_INSTALLATION')) {
            $this->installation = Configuration::get('SENDYNEWSLETTER_INSTALLATION');
        }

        if (!isset($this->installation)) {
            $this->warning = $this->l('You must configure your sendy installation path before saving.');
            $this->setup = false;
        } else {
            $this->setup = true;
        }

        $this->availableLanguages = Language::getLanguages();
        parent::__construct();
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        foreach ($this->availableLanguages as $lang) {
            Configuration::updateValue('SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code'], "");
            Configuration::updateValue('SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code'], "");

            //Create language dirs and copy default subscribe image as placeholders
            if(!is_dir(__DIR__ . "/img/" . $lang['iso_code'])){
                mkdir(__DIR__ . "/img/" . $lang['iso_code'], 0755, true);
                copy(__DIR__ . "/img/en/sendy-newsletter-signup-subscribe.png", __DIR__ . "/img/" . $lang['iso_code'] . "/sendy-newsletter-signup-subscribe.png");
                copy(__DIR__ . "/img/en/sendy-newsletter-signup-subscribe2x.png", __DIR__ . "/img/" . $lang['iso_code'] . "/sendy-newsletter-signup-subscribe2x.png");
            }
        }

        return parent::install()
        && $this->registerHook('displayFooterBefore')
        && $this->registerHook('header')
        && $this->registerHook('actionCustomerAccountAdd')
        && $this->registerHook('actionCustomerAccountUpdate')
        && Configuration::updateValue('SENDYNEWSLETTER_ACTIVE_ON_PAGES', "index, product, category")
        && Configuration::updateValue('SENDYNEWSLETTER_API_KEY', "")
        && Configuration::updateValue('SENDYNEWSLETTER_IP', true)
        && Configuration::updateValue('SENDYNEWSLETTER_DELETE_ON_UNSUB', false)
        && Configuration::updateValue('SENDYNEWSLETTER_NAME', false)
        && Configuration::updateValue('SENDYNEWSLETTER_NAMEREQ', false)
        && Configuration::updateValue('SENDYNEWSLETTER_RESPECT_OPT_IN', true)
        && Configuration::updateValue('SENDYNEWSLETTER_SHOW_INFO', false); 
    }

    public function uninstall()
    {
        foreach ($this->availableLanguages as $lang) {
            Configuration::deleteByName('SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code']);
            Configuration::deleteByName('SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code']);
        }

        return parent::uninstall()
        && $this->unregisterHook('displayFooterBefore')
        && $this->unregisterHook('header')
        && $this->unregisterHook('actionCustomerAccountAdd')
        && $this->unregisterHook('actionCustomerAccountUpdate')
        && Configuration::deleteByName('SENDYNEWSLETTER_ACTIVE_ON_PAGES')
        && Configuration::deleteByName('SENDYNEWSLETTER_API_KEY')
        && Configuration::deleteByName('SENDYNEWSLETTER_INSTALLATION')
        && Configuration::deleteByName('SENDYNEWSLETTER_IP')
        && Configuration::deleteByName('SENDYNEWSLETTER_DELETE_ON_UNSUB')
        && Configuration::deleteByName('SENDYNEWSLETTER_NAME')
        && Configuration::deleteByName('SENDYNEWSLETTER_NAMEREQ')
        && Configuration::deleteByName('SENDYNEWSLETTER_RESPECT_OPT_IN')
        && Configuration::deleteByName('SENDYNEWSLETTER_SHOW_INFO');
    }

    public function hookDisplayFooterBefore($params)
    {   
        $sendy = array(
            'url' => Configuration::get('SENDYNEWSLETTER_INSTALLATION'),
            'list' => Configuration::get('SENDYNEWSLETTER_COUNTRY_' . $this->context->language->iso_code),
            'ip' => (int) Configuration::get('SENDYNEWSLETTER_IP'),
            'ipval' => $_SERVER["REMOTE_ADDR"],
            'name' => (int) Configuration::get('SENDYNEWSLETTER_NAME'),
            'namereq' => (int) Configuration::get('SENDYNEWSLETTER_NAMEREQ'),
            'activeOnPages' => Configuration::get('SENDYNEWSLETTER_ACTIVE_ON_PAGES'),
            'showInfo' => Configuration::get('SENDYNEWSLETTER_SHOW_INFO')
        );
        $this->context->smarty->assign(array(
            'sendynews' => $sendy,
        ));
        if ($this->setup) {
            if (_PS_VERSION_ >= 1.7) {
                return $this->display(__FILE__, 'sendyintegration2.tpl');
            } else {
                return $this->display(__FILE__, 'sendyintegration.tpl');
            } 
        }
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->hookDisplayLeftColumn($params);
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/sendynewsletter.css', 'all');
    }

    public function processSyncCustomersForm()
    {
        // This is a little bit unusual, since each input is a submit button, the name is always the name of the whole form, 
        // not the name of an individual element such as iso_lang that you would expect. This is since there is no explicit
        // submit button for the whole form
        if (Tools::isSubmit('sendy_integration_customers_sync_form'))
        {
            $iso_of_lang_to_sync = Tools::getValue("sendy_integration_customers_sync_form");
            return $this->syncCustomers($iso_of_lang_to_sync);
        }
        return "";
    }

    public function processSyncNativeNewsletterForm()
    {
        $id_shop = (int)Context::getContext()->shop->id;
        
        if (Tools::isSubmit('sendy_integration_native_newsletter_sync_form'))
        {
            $list_name = Tools::getValue("list_to_sync_to");
            if (strlen($list_name) < 1) {
                return $this->displayError($this->l('Invalid list name'));
            }
            $result = $this->syncNewsletter($list_name);
            return $this->displayConfirmation($this->l('Newsletter list synchronized for shop_id=') . $id_shop . ". " . $result );
        }
        return "";
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $installation = Tools::getValue('SENDYNEWSLETTER_INSTALLATION');
            $ip = (int) Tools::getValue('SENDYNEWSLETTER_IP');
            $delete_on_unsubscribe = Tools::getValue('SENDYNEWSLETTER_DELETE_ON_UNSUB');
            $name = (int) Tools::getValue('SENDYNEWSLETTER_NAME');
            $name_req = (int) Tools::getValue('SENDYNEWSLETTER_NAMEREQ');
            $respect_opt_in = (int) Tools::getValue('SENDYNEWSLETTER_RESPECT_OPT_IN');
            $active_on_pages = Tools::getValue('SENDYNEWSLETTER_ACTIVE_ON_PAGES');
            $show_info = Tools::getValue('SENDYNEWSLETTER_SHOW_INFO');
            $sendy_api_key = Tools::getValue('SENDYNEWSLETTER_API_KEY');

            if (!$installation || empty($installation) || !Validate::isAbsoluteUrl($installation)) {
                $output .= $this->displayError($this->l('Invalid installation url'));
            }

            if ($output == null) {
                foreach ($this->availableLanguages as $lang) {
                    Configuration::updateValue('SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code'], Tools::getValue('SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code']));
                }

                foreach ($this->availableLanguages as $lang) {
                    Configuration::updateValue('SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code'], Tools::getValue('SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code']));
                }

                Configuration::updateValue('SENDYNEWSLETTER_INSTALLATION', $installation);
                Configuration::updateValue('SENDYNEWSLETTER_IP', $ip);
                Configuration::updateValue('SENDYNEWSLETTER_DELETE_ON_UNSUB', $delete_on_unsubscribe);
                Configuration::updateValue('SENDYNEWSLETTER_API_KEY', $sendy_api_key);
                Configuration::updateValue('SENDYNEWSLETTER_NAME', $name);
                Configuration::updateValue('SENDYNEWSLETTER_NAMEREQ', $name_req);
                Configuration::updateValue('SENDYNEWSLETTER_RESPECT_OPT_IN', $respect_opt_in);
                Configuration::updateValue('SENDYNEWSLETTER_ACTIVE_ON_PAGES', $active_on_pages);
                Configuration::updateValue('SENDYNEWSLETTER_SHOW_INFO', $show_info);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        $sendyBack = array(         
            'availableLanguages' => $this->availableLanguages
        );
        $this->context->smarty->assign(array(
            'sendyBack' => $sendyBack,
        ));

        $adminSyncForm = $this->display(__DIR__, '/views/templates/admin/admin.tpl');
        // check if the post is from one of our admin forms or if its a fresh load of the page
        $output .= $this->processSyncCustomersForm();
        $output .= $this->processSyncNativeNewsletterForm();
        return $output . $this->displayForm() . $adminSyncForm;
    }

    public function displayForm()
    {
        // Get default Language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Sendy Newsletter Settings'),
                'image' => '../modules/sendynewsletter/logo.gif',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Installation'),
                    'name' => 'SENDYNEWSLETTER_INSTALLATION',
                    'desc' => $this->l('Url address of your sendy installation eg "http://your_sendy_installation"'),
                    'size' => 30,
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Newsletter pages'),
                    'name' => 'SENDYNEWSLETTER_ACTIVE_ON_PAGES',
                    'desc' => $this->l('Pages where to show newsletter module'),
                    'size' => 50,
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Show unsubscribe info'),
                    'name' => 'SENDYNEWSLETTER_SHOW_INFO',
                    'desc' => $this->l('Show tooltip icon about unregistration, could be useful for GDPR (only if your theme has bootstrap)'),
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'info_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'info_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Capture user IP'),
                    'name' => 'SENDYNEWSLETTER_IP',
                    'desc' => $this->l('Store subscriber ip.'),
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'ip_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'ip_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Delete on unsubscribe'),
                    'name' => 'SENDYNEWSLETTER_DELETE_ON_UNSUB',
                    'desc' => $this->l('Delete user information in Sendy on unsubscribe. You must assign the API key below for this to work.'),
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'dou_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'dou_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Sendy API key'),
                    'name' => 'SENDYNEWSLETTER_API_KEY',
                    'desc' => $this->l('Sendy API key. ONLY needed if you have activated Delete on unsubscribe above.'),
                    'size' => 30,
                    'required' => false,
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Respect opt-in'),
                    'name' => 'SENDYNEWSLETTER_RESPECT_OPT_IN',
                    'desc' => $this->l('Respect new clients newsletter setting. If you do not show the checkbox and new clients should be auto subscribed, choose disabled here.'),
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'respect_opt_in_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'respect_opt_in_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Subscribers Name'),
                    'name' => 'SENDYNEWSLETTER_NAME',
                    'desc' => $this->l('If checked the subscribe block will also have a field for subscriber\'s name.'),
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'name_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'name_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Name field required'),
                    'name' => 'SENDYNEWSLETTER_NAMEREQ',
                    'desc' => $this->l('If checked subscribers name will be required.'),
                    'is_bool' => true,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'namereq_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ),
                        array(
                            'id' => 'namereq_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ),
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button',
            ),
        );

        // add country specific lists
        foreach ($this->availableLanguages as $lang) {
            $extra = array(
                'type' => 'text',
                'label' => $this->l('Language specific list id for NEWSLETTER'),
                'name' => 'SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code'],
                'desc' => $lang['iso_code'],
                'size' => 20,
            );
            array_push($fields_form[0]['form']['input'], $extra);
        }

        // add country specific customer lists lists
        foreach ($this->availableLanguages as $lang) {
            $extra = array(
                'type' => 'text',
                'label' => $this->l('Language specific list id for CUSTOMERS'),
                'name' => 'SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code'],
                'desc' => $lang['iso_code'],
                'size' => 20,
            );
            array_push($fields_form[0]['form']['input'], $extra);
        }

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . $helper->token,
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . $helper->token,
                'desc' => $this->l('Back to list'),
            ),
        );

        // Load current value
        $helper->fields_value = array(
            'SENDYNEWSLETTER_INSTALLATION' => Configuration::get('SENDYNEWSLETTER_INSTALLATION'),
            'SENDYNEWSLETTER_IP' => Configuration::get('SENDYNEWSLETTER_IP'),
            'SENDYNEWSLETTER_DELETE_ON_UNSUB' => Configuration::get('SENDYNEWSLETTER_DELETE_ON_UNSUB'),
            'SENDYNEWSLETTER_API_KEY' => Configuration::get('SENDYNEWSLETTER_API_KEY'),
            'SENDYNEWSLETTER_NAME' => Configuration::get('SENDYNEWSLETTER_NAME'),
            'SENDYNEWSLETTER_NAMEREQ' => Configuration::get('SENDYNEWSLETTER_NAMEREQ'),
            'SENDYNEWSLETTER_RESPECT_OPT_IN' => Configuration::get('SENDYNEWSLETTER_RESPECT_OPT_IN'),
            'SENDYNEWSLETTER_ACTIVE_ON_PAGES' => Configuration::get('SENDYNEWSLETTER_ACTIVE_ON_PAGES'),
            'SENDYNEWSLETTER_SHOW_INFO' => Configuration::get('SENDYNEWSLETTER_SHOW_INFO')
        );

        //Load existing values for the country specific newsletter fields
        foreach ($this->availableLanguages as $lang) {
            $helper->fields_value['SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code']] = Configuration::get('SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code']);
        }

        //Load existing values for the country specific customer fields
        foreach ($this->availableLanguages as $lang) {
            $helper->fields_value['SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code']] = Configuration::get('SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code']);
        }
            

        return $helper->generateForm($fields_form);
    }


    public function hookActionCustomerAccountAdd($params)
    {
        $respect_opt_in = (bool) Configuration::get('SENDYNEWSLETTER_RESPECT_OPT_IN');
        $url = Configuration::get('SENDYNEWSLETTER_INSTALLATION') . '/subscribe';
        $store_ip = (int)Configuration::get('SENDYNEWSLETTER_IP');
        $customerLang = Language::getIsoById($params['newCustomer']->id_lang);
        $list = Configuration::get('SENDY_CUSTOMERS_COUNTRY_' . $customerLang);
        
        //Do not subscribe if this country does not have a customer newsletter list
        if ($list == null || strlen($list) == 0) {
            return;
        }
        //This might not work if the original newsletter module is not active (depending on what PS_CUSTOMER_NWSL does)
        //https://github.com/PrestaShop/PrestaShop/blob/1.6.1.x/controllers/front/IdentityController.php#L152
        $newUserHasOptInForNewsletter = $params['newCustomer']->newsletter;

        //Unless we override the opt-in setting, we should not register the new users email to any list
        if (!$newUserHasOptInForNewsletter && $respect_opt_in) {
            return;
        }

        $this->runCurlOperation($url, $list, $params['newCustomer']->email, $params['newCustomer']->firstname, $_SERVER["REMOTE_ADDR"], "", $params['newCustomer']->birthday);
        return true;
    }

    public function hookActionCustomerAccountUpdate($params)
    {
        $url = Configuration::get('SENDYNEWSLETTER_INSTALLATION');
        $customerLang = Language::getIsoById($params['customer']->id_lang);
        $list = Configuration::get('SENDY_CUSTOMERS_COUNTRY_' . $customerLang);
        $api_key = ""; 
        if(!$params['customer']->newsletter) {
            $delete_on_unsubscribe = Configuration::get('SENDYNEWSLETTER_DELETE_ON_UNSUB');
            if ($delete_on_unsubscribe) {
                $url .= '/api/subscribers/delete.php';
                $api_key = Configuration::get('SENDYNEWSLETTER_API_KEY');
            } else {
                $url .= '/unsubscribe';
            }
        } else {
            $url .=  '/subscribe';       
        }

        $this->runCurlOperation($url, $list, $params['customer']->email, $name="", $params['customer']->ip_registration_newsletter, $api_key, $params['customer']->birthday);
        return true;
    }

    public function syncCustomers($iso_lang)
    {
        $respect_opt_in = (bool) Configuration::get('SENDYNEWSLETTER_RESPECT_OPT_IN');
        $url = Configuration::get('SENDYNEWSLETTER_INSTALLATION') . '/subscribe';
        $list = Configuration::get('SENDY_CUSTOMERS_COUNTRY_' . $iso_lang);

        if (!$list) {
            return $this->displayError($this->l('Failed to sync customers. No list defined for language = ' . $iso_lang));
        }

        $id_lang = Language::getIdByIso($iso_lang);
        $sql = 'SELECT firstname, email, newsletter, ip_registration_newsletter FROM '._DB_PREFIX_.'customer WHERE id_lang=' . $id_lang ;
        
        $customer_sync_count = 0;
        $customer_skip_count = 0;
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            foreach ($results as $row) {
                if ((int)$row['newsletter'] == 0 && $respect_opt_in) {
                    echo ("Skipping " . $row['email'] . " since not opt-in to newsletter");
                    $customer_skip_count++;
                    continue;
                }
                $customer_sync_count++;
                $this->runCurlOperation($url, $list, $row['email'], $row['firstname'], $row['ip_registration_newsletter']);
            }
        }
        return $this->displayConfirmation($this->l('Customer list synchronized for language=') . $iso_lang . ". " . "Synced " . $customer_sync_count . " Skipped: " . $customer_skip_count);
    }

    public function syncNewsletter($list)
    {
        $newsletter_table_name = "";
        if (_PS_VERSION_ >= 1.7) {
            $newsletter_table_name = emailsubscription;
        } else {
            $newsletter_table_name = newsletter;
        } 
        $id_shop = (int)Context::getContext()->shop->id;
        $url = Configuration::get('SENDYNEWSLETTER_INSTALLATION') . '/subscribe';
        
        // todo, manage id_shop if multishop wants to use different lists in sendy
        $sql = 'SELECT email, active, newsletter_date_add, ip_registration_newsletter FROM '._DB_PREFIX_.$newsletter_table_name . ' WHERE id_shop=' . $id_shop;
        
        $newsletter_sync_count = 0;
        $newsletter_skip_count = 0;
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            foreach ($results as $row) {
                $active = $row['active'];
                if (!$active) {
                    echo ("Skipping " . $row['email'] . " since not active to newsletter");
                    $newsletter_skip_count++;
                    continue;
                } else {
                    $newsletter_sync_count++;
                }
                $this->runCurlOperation($url, $list, $row['email'], "", $row['ip_registration_newsletter']);
            }
        }
        return "Synced " . $newsletter_sync_count . " Skipped: " . $newsletter_skip_count;
    }

    private function runCurlOperation($url, $list, $email, $name="", $ip_registration_newsletter="na", $sendy_api_key="", $birthday="") {
        $store_ip = (int)Configuration::get('SENDYNEWSLETTER_IP');

        $data = array(
            'list'		=> $list,
            'email' 	=> $email,
            'boolean'	=> 'true'
        );

        if (strlen($name) > 0) {
            $data['name'] = $name;
        }

        if ($store_ip == 1) {
            $data["ipaddress"] = $ip_registration_newsletter;
        }

        if (strlen($sendy_api_key) > 0) {
            $data["api_key"] = $sendy_api_key;
            $data["list_id"] = $list;  //delete uses list_id and not list
        }

        if ($birthday && strlen($birthday) > 0) {
            $data["birthday"] = $birthday;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_exec($ch);
    }
}
