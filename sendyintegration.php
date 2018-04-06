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
        $this->version = '1.2';
        $this->author = 'Givensa';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7.99');

        $this->displayName = $this->l('Sendy Newsletter Integration');
        $this->description = $this->l('Sync newsletter subscribers and customers with your sendy installation');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

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
        }
        
        foreach ($this->availableLanguages as $lang) {
            Configuration::updateValue('SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code'], "");
        }

        return parent::install()
        && $this->registerHook('displayFooterBefore')
        && $this->registerHook('header')
        && $this->registerHook(array('actionCustomerAccountAdd'))
        && Configuration::updateValue('SENDYNEWSLETTER_ACTIVE_ON_PAGES', "index, product, category")
        && Configuration::updateValue('SENDYNEWSLETTER_IP', false)
        && Configuration::updateValue('SENDYNEWSLETTER_IPVALUE', '')
        && Configuration::updateValue('SENDYNEWSLETTER_NAME', false)
        && Configuration::updateValue('SENDYNEWSLETTER_NAMEREQ', false)
        && Configuration::updateValue('SENDYNEWSLETTER_RESPECT_USER_OPT_IN', true);
    }

    public function uninstall()
    {
        foreach ($this->availableLanguages as $lang) {
            Configuration::deleteByName('SENDYNEWSLETTER_COUNTRY_' . $lang['iso_code']);
        }

        foreach ($this->availableLanguages as $lang) {
            Configuration::deleteByName('SENDY_CUSTOMERS_COUNTRY_' . $lang['iso_code']);
        }

        return parent::uninstall()
        && Configuration::deleteByName('SENDYNEWSLETTER_ACTIVE_ON_PAGES')
        && Configuration::deleteByName('SENDYNEWSLETTER_INSTALLATION')
        && Configuration::deleteByName('SENDYNEWSLETTER_IP')
        && Configuration::deleteByName('SENDYNEWSLETTER_IPVALUE')
        && Configuration::deleteByName('SENDYNEWSLETTER_NAME')
        && Configuration::deleteByName('SENDYNEWSLETTER_NAMEREQ')
        && Configuration::deleteByName('SENDYNEWSLETTER_RESPECT_USER_OPT_IN');
    }

    public function hookDisplayFooterBefore($params)
    {
        $sendy = array(
            'url' => Configuration::get('SENDYNEWSLETTER_INSTALLATION'),
            'list' => Configuration::get('SENDYNEWSLETTER_COUNTRY_' . $this->context->language->iso_code),
            'ip' => (int) Configuration::get('SENDYNEWSLETTER_IP'),
            'ipval' => $_SERVER["REMOTE_ADDR"],
            'ipfield' => Configuration::get('SENDYNEWSLETTER_IPVALUE'),
            'name' => (int) Configuration::get('SENDYNEWSLETTER_NAME'),
            'namereq' => (int) Configuration::get('SENDYNEWSLETTER_NAMEREQ'),
            'activeOnPages' => Configuration::get('SENDYNEWSLETTER_ACTIVE_ON_PAGES')
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

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $installation = Tools::getValue('SENDYNEWSLETTER_INSTALLATION');
            $ip = (int) Tools::getValue('SENDYNEWSLETTER_IP');
            $ip_var = Tools::getValue('SENDYNEWSLETTER_IPVALUE');
            $name = (int) Tools::getValue('SENDYNEWSLETTER_NAME');
            $name_req = (int) Tools::getValue('SENDYNEWSLETTER_NAMEREQ');
            $respect_opt_in = (int) Tools::getValue('SENDYNEWSLETTER_RESPECT_USER_OPT_IN');
            $active_on_pages = Tools::getValue('SENDYNEWSLETTER_ACTIVE_ON_PAGES');
            
            if (!$installation || empty($installation) || !Validate::isAbsoluteUrl($installation)) {
                $output .= $this->displayError($this->l('Invalid installation url'));
            }

            if ($ip == 1) {
                if (!$ip_var || empty($ip_var) || !Validate::isGenericName($ip_var)) {
                    $output .= $this->displayError($this->l('Invalid ip custom field value'));
                }
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
                Configuration::updateValue('SENDYNEWSLETTER_IPVALUE', $ip_var);
                Configuration::updateValue('SENDYNEWSLETTER_NAME', $name);
                Configuration::updateValue('SENDYNEWSLETTER_NAMEREQ', $name_req);
                Configuration::updateValue('SENDYNEWSLETTER_RESPECT_USER_OPT_IN', $respect_opt_in);Configuration::updateValue('SENDYNEWSLETTER_RESPECT_USER_OPT_IN', $respect_opt_in);
                Configuration::updateValue('SENDYNEWSLETTER_ACTIVE_ON_PAGES', $active_on_pages);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output . $this->displayForm();
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
                    'label' => $this->l('Capture user IP'),
                    'name' => 'SENDYNEWSLETTER_IP',
                    'desc' => $this->l('You might want to store subscribers IP address as it might be required of you by the local law.'),
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
                    'label' => $this->l('Respect opt-in'),
                    'name' => 'SENDYNEWSLETTER_RESPECT_USER_OPT_IN',
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
                    'type' => 'text',
                    'label' => $this->l('IP value'),
                    'name' => 'SENDYNEWSLETTER_IPVALUE',
                    'desc' => $this->l('If you want to store subscibers IP address you will need to create a new custom field in your list. Input the name of that field here exactly, "IP" is not the same as "ip".'),
                    'size' => 20,
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
            'SENDYNEWSLETTER_IPVALUE' => Configuration::get('SENDYNEWSLETTER_IPVALUE'),
            'SENDYNEWSLETTER_NAME' => Configuration::get('SENDYNEWSLETTER_NAME'),
            'SENDYNEWSLETTER_NAMEREQ' => Configuration::get('SENDYNEWSLETTER_NAMEREQ'),
            'SENDYNEWSLETTER_RESPECT_USER_OPT_IN' => Configuration::get('SENDYNEWSLETTER_RESPECT_USER_OPT_IN'),
            'SENDYNEWSLETTER_ACTIVE_ON_PAGES' => Configuration::get('SENDYNEWSLETTER_ACTIVE_ON_PAGES')
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
        $respect_opt_in = (bool) Configuration::get('SENDYNEWSLETTER_RESPECT_USER_OPT_IN');
        $url = Configuration::get('SENDYNEWSLETTER_INSTALLATION') . '/subscribe';
        $ip_set = (int)Configuration::get('SENDYNEWSLETTER_IP');
        $ip_var = Configuration::get('SENDYNEWSLETTER_IPVALUE');
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

        $data = array(
            'list'		=> $list,
            'email' 	=> $params['newCustomer']->email,
            'boolean'	=> 'true'
        );

        $data['name'] = $params['newCustomer']->firstname;

        if ($ip_set == 1 && $ip_var && !empty($ip_var)) {
            $data[$ip_var] = $params['newCustomer']->ip_registration_newsletter;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_exec($ch);
        return true;
    }
}
