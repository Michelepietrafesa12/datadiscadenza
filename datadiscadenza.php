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
    public function __construct()
    {
        $this->name = 'datadiscadenza';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Michelepietrafesa12';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Data di Scadenza');
        $this->description = $this->l('Aggiunge un campo data di scadenza ai prodotti e lo mostra nel frontend.');
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall()
    {
        return $this->uninstallDb() && parent::uninstall();
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
     * Save expiration date for a product.
     */
    public function saveExpirationDate(int $idProduct, ?string $date): bool
    {
        if (empty($date)) {
            return Db::getInstance()->delete('product_expiration_date', '`id_product` = ' . (int) $idProduct);
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

    // -------------------------------------------------------------------------
    // Back-office: product page tab (PrestaShop 8 product page hooks)
    // -------------------------------------------------------------------------

    /**
     * Display extra tab content in the admin product page.
     */
    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $idProduct = (int) ($params['id_product'] ?? 0);
        $expirationDate = $this->getExpirationDate($idProduct);

        $this->context->smarty->assign([
            'expiration_date' => $expirationDate ?? '',
            'id_product' => $idProduct,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    /**
     * Save expiration date when a product is updated.
     */
    public function hookActionProductUpdate(array $params): void
    {
        $this->processProductSave($params);
    }

    /**
     * Save expiration date when a product is added.
     */
    public function hookActionProductAdd(array $params): void
    {
        $this->processProductSave($params);
    }

    private function processProductSave(array $params): void
    {
        $idProduct = (int) ($params['id_product'] ?? 0);
        if (!$idProduct) {
            return;
        }

        $date = Tools::getValue('expiration_date');
        $this->saveExpirationDate($idProduct, $date ?: null);
    }

    // -------------------------------------------------------------------------
    // Front-office: display expiration date on product page
    // -------------------------------------------------------------------------

    /**
     * Display expiration date on the product page.
     */
    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        $idProduct = (int) ($params['product']['id_product'] ?? ($params['product']->id ?? 0));
        $expirationDate = $this->getExpirationDate($idProduct);

        if (!$expirationDate) {
            return '';
        }

        $dateFormatted = Tools::displayDate($expirationDate);
        $isExpired = strtotime($expirationDate) < strtotime('today');

        $this->context->smarty->assign([
            'expiration_date' => $dateFormatted,
            'expiration_date_raw' => $expirationDate,
            'is_expired' => $isExpired,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_expiration.tpl');
    }

    /**
     * Add CSS to the front controller.
     */
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
}
