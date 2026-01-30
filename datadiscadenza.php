<?php
/**
 * Data di Scadenza - PrestaShop Module
 * Adds an expiration date field to products displayed on the frontend.
 *
 * @author Michelepietrafesa12
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DataDiScadenza extends Module
{
    /** Available hook positions for frontend display */
    const POSITION_ADDITIONAL_INFO = 'displayProductAdditionalInfo';
    const POSITION_AFTER_PRICE = 'displayProductPriceBlock';
    const POSITION_FOOTER_PRODUCT = 'displayFooterProduct';
    const POSITION_REASSURANCE = 'displayReassurance';
    const POSITION_SHORTCODE_ONLY = 'shortcode_only';

    public function __construct()
    {
        $this->name = 'datadiscadenza';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Michelepietrafesa12';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Data di Scadenza', [], 'Modules.Datadiscadenza.Admin');
        $this->description = $this->trans(
            'Aggiunge un campo data di scadenza ai prodotti e lo mostra nel frontend.',
            [],
            'Modules.Datadiscadenza.Admin'
        );
        $this->confirmUninstall = $this->trans(
            'Sei sicuro di voler disinstallare il modulo? Tutti i dati delle date di scadenza verranno persi.',
            [],
            'Modules.Datadiscadenza.Admin'
        );
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->installConfig()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('displayProductPriceBlock')
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayReassurance')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayDataScadenza');
    }

    public function uninstall()
    {
        return $this->uninstallDb()
            && $this->uninstallConfig()
            && parent::uninstall();
    }

    private function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_expiration_date` (
            `id_product` INT(10) UNSIGNED NOT NULL,
            `expiration_date` DATE DEFAULT NULL,
            PRIMARY KEY (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    private function uninstallDb()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_expiration_date`'
        );
    }

    private function installConfig()
    {
        Configuration::updateValue('DATASCADENZA_SHOW_EXPIRED', 1);
        Configuration::updateValue('DATASCADENZA_POSITION', self::POSITION_ADDITIONAL_INFO);
        Configuration::updateValue('DATASCADENZA_SHOW_LABEL', 1);
        Configuration::updateValue('DATASCADENZA_EXPIRED_TEXT', 'Prodotto scaduto');
        Configuration::updateValue('DATASCADENZA_LABEL_TEXT', 'Data di scadenza:');

        return true;
    }

    private function uninstallConfig()
    {
        Configuration::deleteByName('DATASCADENZA_SHOW_EXPIRED');
        Configuration::deleteByName('DATASCADENZA_POSITION');
        Configuration::deleteByName('DATASCADENZA_SHOW_LABEL');
        Configuration::deleteByName('DATASCADENZA_EXPIRED_TEXT');
        Configuration::deleteByName('DATASCADENZA_LABEL_TEXT');

        return true;
    }

    // -------------------------------------------------------------------------
    // Configuration page
    // -------------------------------------------------------------------------

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDataDiScadenza')) {
            $showExpired = (int) Tools::getValue('DATASCADENZA_SHOW_EXPIRED');
            $position = Tools::getValue('DATASCADENZA_POSITION');
            $showLabel = (int) Tools::getValue('DATASCADENZA_SHOW_LABEL');
            $expiredText = Tools::getValue('DATASCADENZA_EXPIRED_TEXT');
            $labelText = Tools::getValue('DATASCADENZA_LABEL_TEXT');

            $validPositions = [
                self::POSITION_ADDITIONAL_INFO,
                self::POSITION_AFTER_PRICE,
                self::POSITION_FOOTER_PRODUCT,
                self::POSITION_REASSURANCE,
                self::POSITION_SHORTCODE_ONLY,
            ];

            if (!in_array($position, $validPositions, true)) {
                $position = self::POSITION_ADDITIONAL_INFO;
            }

            Configuration::updateValue('DATASCADENZA_SHOW_EXPIRED', $showExpired);
            Configuration::updateValue('DATASCADENZA_POSITION', pSQL($position));
            Configuration::updateValue('DATASCADENZA_SHOW_LABEL', $showLabel);
            Configuration::updateValue('DATASCADENZA_EXPIRED_TEXT', pSQL($expiredText));
            Configuration::updateValue('DATASCADENZA_LABEL_TEXT', pSQL($labelText));

            $output .= $this->displayConfirmation(
                $this->trans('Impostazioni aggiornate.', [], 'Modules.Datadiscadenza.Admin')
            );
        }

        return $output . $this->renderConfigForm();
    }

    private function renderConfigForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Impostazioni Data di Scadenza', [], 'Modules.Datadiscadenza.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Mostra avviso prodotto scaduto', [], 'Modules.Datadiscadenza.Admin'),
                        'name' => 'DATASCADENZA_SHOW_EXPIRED',
                        'desc' => $this->trans(
                            'Se attivato, mostra un avviso visivo quando il prodotto è scaduto.',
                            [],
                            'Modules.Datadiscadenza.Admin'
                        ),
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Sì', [], 'Modules.Datadiscadenza.Admin')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Datadiscadenza.Admin')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Mostra etichetta', [], 'Modules.Datadiscadenza.Admin'),
                        'name' => 'DATASCADENZA_SHOW_LABEL',
                        'desc' => $this->trans(
                            'Se attivato, mostra l\'etichetta "Data di scadenza:" prima della data.',
                            [],
                            'Modules.Datadiscadenza.Admin'
                        ),
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'label_on', 'value' => 1, 'label' => $this->trans('Sì', [], 'Modules.Datadiscadenza.Admin')],
                            ['id' => 'label_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Datadiscadenza.Admin')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Testo etichetta', [], 'Modules.Datadiscadenza.Admin'),
                        'name' => 'DATASCADENZA_LABEL_TEXT',
                        'desc' => $this->trans(
                            'Il testo dell\'etichetta mostrata prima della data di scadenza.',
                            [],
                            'Modules.Datadiscadenza.Admin'
                        ),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Testo prodotto scaduto', [], 'Modules.Datadiscadenza.Admin'),
                        'name' => 'DATASCADENZA_EXPIRED_TEXT',
                        'desc' => $this->trans(
                            'Il testo mostrato nel badge quando il prodotto è scaduto.',
                            [],
                            'Modules.Datadiscadenza.Admin'
                        ),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Posizione nella scheda prodotto', [], 'Modules.Datadiscadenza.Admin'),
                        'name' => 'DATASCADENZA_POSITION',
                        'desc' => $this->trans(
                            'Scegli dove visualizzare la data di scadenza nella pagina prodotto. '
                            . 'Seleziona "Solo shortcode" per usare lo shortcode con Creative Elements.',
                            [],
                            'Modules.Datadiscadenza.Admin'
                        ),
                        'options' => [
                            'query' => [
                                ['id' => self::POSITION_ADDITIONAL_INFO, 'name' => $this->trans('Info aggiuntive prodotto (default)', [], 'Modules.Datadiscadenza.Admin')],
                                ['id' => self::POSITION_AFTER_PRICE, 'name' => $this->trans('Sotto il prezzo', [], 'Modules.Datadiscadenza.Admin')],
                                ['id' => self::POSITION_FOOTER_PRODUCT, 'name' => $this->trans('Footer scheda prodotto', [], 'Modules.Datadiscadenza.Admin')],
                                ['id' => self::POSITION_REASSURANCE, 'name' => $this->trans('Blocco rassicurazioni', [], 'Modules.Datadiscadenza.Admin')],
                                ['id' => self::POSITION_SHORTCODE_ONLY, 'name' => $this->trans('Solo shortcode (per Creative Elements)', [], 'Modules.Datadiscadenza.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Salva', [], 'Modules.Datadiscadenza.Admin'),
                ],
            ],
        ];

        $shortcodeInfo = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Integrazione Shortcode / Creative Elements', [], 'Modules.Datadiscadenza.Admin'),
                    'icon' => 'icon-code',
                ],
                'description' => $this->trans(
                    'Per integrare la data di scadenza con Creative Elements (Elementor) o nel tuo tema, '
                    . 'usa il widget "Data di Scadenza" disponibile nella sezione widget di Creative Elements, '
                    . 'oppure inserisci manualmente questo shortcode nel template del prodotto:',
                    [],
                    'Modules.Datadiscadenza.Admin'
                ),
                'input' => [
                    [
                        'type' => 'html',
                        'name' => 'shortcode_info',
                        'html_content' => '
                            <div class="alert alert-info">
                                <h4>' . $this->trans('Shortcode disponibili', [], 'Modules.Datadiscadenza.Admin') . '</h4>
                                <p><strong>Nel template Smarty (.tpl):</strong></p>
                                <pre style="background:#f5f5f5;padding:10px;border-radius:4px;">{hook h=\'displayDataScadenza\'}</pre>
                                <p class="mt-2"><strong>In Creative Elements:</strong></p>
                                <p>' . $this->trans(
                                    'Usa il widget "Shortcode" di Creative Elements e inserisci:',
                                    [],
                                    'Modules.Datadiscadenza.Admin'
                                ) . '</p>
                                <pre style="background:#f5f5f5;padding:10px;border-radius:4px;">[datadiscadenza]</pre>
                                <p class="mt-2"><strong>In un modulo PHP:</strong></p>
                                <pre style="background:#f5f5f5;padding:10px;border-radius:4px;">Hook::exec(\'displayDataScadenza\', [\'id_product\' => $idProduct]);</pre>
                            </div>
                        ',
                    ],
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitDataDiScadenza';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value = [
            'DATASCADENZA_SHOW_EXPIRED' => Configuration::get('DATASCADENZA_SHOW_EXPIRED'),
            'DATASCADENZA_POSITION' => Configuration::get('DATASCADENZA_POSITION'),
            'DATASCADENZA_SHOW_LABEL' => Configuration::get('DATASCADENZA_SHOW_LABEL'),
            'DATASCADENZA_EXPIRED_TEXT' => Configuration::get('DATASCADENZA_EXPIRED_TEXT'),
            'DATASCADENZA_LABEL_TEXT' => Configuration::get('DATASCADENZA_LABEL_TEXT'),
        ];

        return $helper->generateForm([$fieldsForm, $shortcodeInfo]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Extract product ID from hook params with multiple fallbacks for PS 8 compatibility.
     */
    private function extractProductId(array $params): int
    {
        if (!empty($params['id_product'])) {
            return (int) $params['id_product'];
        }

        if (isset($params['product']) && is_object($params['product']) && !empty($params['product']->id)) {
            return (int) $params['product']->id;
        }

        if (isset($params['product']) && is_array($params['product']) && !empty($params['product']['id_product'])) {
            return (int) $params['product']['id_product'];
        }

        return 0;
    }

    /**
     * Get expiration date for a product.
     */
    public function getExpirationDate(int $idProduct): ?string
    {
        $result = Db::getInstance()->getValue(
            'SELECT `expiration_date` FROM `' . _DB_PREFIX_ . 'product_expiration_date`
             WHERE `id_product` = ' . (int) $idProduct
        );

        return $result ?: null;
    }

    /**
     * Validate date format (YYYY-MM-DD).
     */
    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parts = explode('-', $date);

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    /**
     * Save expiration date for a product.
     */
    public function saveExpirationDate(int $idProduct, ?string $date): bool
    {
        if (empty($date)) {
            return Db::getInstance()->delete('product_expiration_date', '`id_product` = ' . (int) $idProduct);
        }

        if (!$this->isValidDate($date)) {
            return false;
        }

        $exists = $this->getExpirationDate($idProduct);

        if ($exists !== null) {
            return Db::getInstance()->update('product_expiration_date', [
                'expiration_date' => pSQL($date),
            ], '`id_product` = ' . (int) $idProduct);
        }

        return Db::getInstance()->insert('product_expiration_date', [
            'id_product' => (int) $idProduct,
            'expiration_date' => pSQL($date),
        ]);
    }

    /**
     * Render the expiration date HTML for a given product.
     * Used by all frontend hooks and the shortcode.
     */
    public function renderExpirationDate(int $idProduct): string
    {
        if (!$idProduct) {
            return '';
        }

        $expirationDate = $this->getExpirationDate($idProduct);

        if (!$expirationDate) {
            return '';
        }

        $showExpired = (bool) Configuration::get('DATASCADENZA_SHOW_EXPIRED');
        $showLabel = (bool) Configuration::get('DATASCADENZA_SHOW_LABEL');
        $expiredText = Configuration::get('DATASCADENZA_EXPIRED_TEXT') ?: 'Prodotto scaduto';
        $labelText = Configuration::get('DATASCADENZA_LABEL_TEXT') ?: 'Data di scadenza:';

        $dateFormatted = Tools::displayDate($expirationDate);
        $isExpired = strtotime($expirationDate) < strtotime('today');

        $this->context->smarty->assign([
            'expiration_date' => $dateFormatted,
            'expiration_date_raw' => $expirationDate,
            'is_expired' => $isExpired,
            'show_expired' => $showExpired,
            'show_label' => $showLabel,
            'expired_text' => $expiredText,
            'label_text' => $labelText,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_expiration.tpl');
    }

    // -------------------------------------------------------------------------
    // Back-office: product page tab
    // -------------------------------------------------------------------------

    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $idProduct = $this->extractProductId($params);
        $expirationDate = $this->getExpirationDate($idProduct);

        $this->context->smarty->assign([
            'expiration_date' => $expirationDate ?? '',
            'id_product' => $idProduct,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    public function hookActionProductUpdate(array $params): void
    {
        $this->processProductSave($params);
    }

    public function hookActionProductAdd(array $params): void
    {
        $this->processProductSave($params);
    }

    private function processProductSave(array $params): void
    {
        $idProduct = $this->extractProductId($params);
        if (!$idProduct) {
            return;
        }

        $date = Tools::getValue('expiration_date');
        $this->saveExpirationDate($idProduct, $date ?: null);
    }

    // -------------------------------------------------------------------------
    // Front-office: display based on configured position
    // -------------------------------------------------------------------------

    /**
     * Check if the current hook matches the configured position.
     */
    private function shouldRenderInHook(string $hookName): bool
    {
        $position = Configuration::get('DATASCADENZA_POSITION');

        return $position === $hookName;
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        if (!$this->shouldRenderInHook(self::POSITION_ADDITIONAL_INFO)) {
            return '';
        }

        return $this->renderExpirationDate($this->extractProductId($params));
    }

    public function hookDisplayProductPriceBlock(array $params): string
    {
        if (!$this->shouldRenderInHook(self::POSITION_AFTER_PRICE)) {
            return '';
        }

        // This hook fires multiple times with different "type" values; render only for 'after_price'
        if (isset($params['type']) && $params['type'] !== 'after_price') {
            return '';
        }

        return $this->renderExpirationDate($this->extractProductId($params));
    }

    public function hookDisplayFooterProduct(array $params): string
    {
        if (!$this->shouldRenderInHook(self::POSITION_FOOTER_PRODUCT)) {
            return '';
        }

        return $this->renderExpirationDate($this->extractProductId($params));
    }

    public function hookDisplayReassurance(array $params): string
    {
        if (!$this->shouldRenderInHook(self::POSITION_REASSURANCE)) {
            return '';
        }

        return $this->renderExpirationDate($this->extractProductId($params));
    }

    // -------------------------------------------------------------------------
    // Custom hook for shortcode / Creative Elements
    // -------------------------------------------------------------------------

    /**
     * Custom hook that can be called from templates or Creative Elements shortcode widget.
     * Usage in .tpl:  {hook h='displayDataScadenza'}
     * The hook auto-detects the current product from context.
     */
    public function hookDisplayDataScadenza(array $params): string
    {
        $idProduct = $this->extractProductId($params);

        // Fallback: get product from current controller context
        if (!$idProduct && isset($this->context->controller) && $this->context->controller instanceof ProductController) {
            $product = $this->context->controller->getTemplateVarProduct();
            if (is_array($product) && !empty($product['id_product'])) {
                $idProduct = (int) $product['id_product'];
            }
        }

        return $this->renderExpirationDate($idProduct);
    }

    // -------------------------------------------------------------------------
    // Front-office: CSS and shortcode registration
    // -------------------------------------------------------------------------

    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->context->controller instanceof ProductControllerCore
            || $this->context->controller instanceof ProductController) {
            $this->context->controller->registerStylesheet(
                'module-datadiscadenza-style',
                'modules/' . $this->name . '/views/css/front.css',
                ['media' => 'all', 'priority' => 200]
            );
        }
    }

    /**
     * Register the [datadiscadenza] shortcode for Creative Elements.
     * Also injects CSS when the shortcode is used outside the product page.
     */
    public function hookDisplayHeader(): void
    {
        // Register shortcode for Creative Elements compatibility
        if (function_exists('add_shortcode')) {
            add_shortcode('datadiscadenza', [$this, 'handleShortcode']);
        }
    }

    /**
     * Shortcode handler for [datadiscadenza] and [datadiscadenza id_product="123"].
     */
    public function handleShortcode($attrs = []): string
    {
        $idProduct = 0;

        if (!empty($attrs['id_product'])) {
            $idProduct = (int) $attrs['id_product'];
        }

        // Auto-detect product from context
        if (!$idProduct && isset($this->context->controller) && $this->context->controller instanceof ProductController) {
            $product = $this->context->controller->getTemplateVarProduct();
            if (is_array($product) && !empty($product['id_product'])) {
                $idProduct = (int) $product['id_product'];
            }
        }

        if (!$idProduct) {
            return '';
        }

        // Ensure CSS is loaded
        $cssPath = _PS_MODULE_DIR_ . $this->name . '/views/css/front.css';
        if (file_exists($cssPath)) {
            $cssUrl = __PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/front.css';
            $css = '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '" />';

            return $css . $this->renderExpirationDate($idProduct);
        }

        return $this->renderExpirationDate($idProduct);
    }
}
