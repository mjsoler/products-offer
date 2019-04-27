<?php
/**
 * Módulo que crea una sección en la home con 4 productos en oferta aleatorios.
 * Este módulo tiene en cuenta tanto los productos con descuento como los productos etiquetados con on-sale.
 * @author mjsoler <solergmj@gmail.com>
 * @copyright (c) MJSoler
 * 
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class ProductsOffer extends Module {

    public function __construct() {
        $this->name = 'productsoffer';
        $this->version = '1.0.0';
        $this->author = 'MJSoler';
        $this->author_uri = 'http://mjsoler.x10host.com/';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Productos en oferta');
        $this->description = $this->l('Añade un bloque con las ofertas de productos en la home');
        $this->bootstrap = true;
    }

    public function install() {

        if (!parent::install() || !$this->registerHook('displayHome')) {
            return false;
        }
        return true;
    }

    public function uninstall() {

        if (!parent::uninstall() || !$this->registerHook('displayHome')) {
            return false;
        }
        return true;
    }

    public function hookdisplayHome() {
        $numProducts = 4;

        return $this->getBlockProductsOffer($numProducts);
    }

    /**
     * Obtiene y muestra los productos en oferta
     * @param int $numProducts numero de productos
     * @return type Retorna el template con los productos a mostar
     */
    public function getBlockProductsOffer($numProducts) {
        $productsOffer = ProductsOffer::getProductsOffer($this->context->language->id, 0, $numProducts);

        $this->smarty->assign(array(
            'productsOffer' => $this->presentationProductsOffer($productsOffer),
                )
        );

        $template = $this->display(__FILE__, 'views/templates/front/productsoffer.tpl');
        return $template;
    }

    /**
     * Obtiene los productos que tienen descuento o tienen la etiqueta on-sale
     * @param int $idLang id del lenguaje de la tienda
     * @param int $start Número de inicio
     * @param int $limit Número de productos a devolver
     * @return array con todos los productos que tienen oferta
     */
    public function getProductsOffer($idLang, $start, $limit) {

        $sql = 'SELECT p.*, product_shop.*, pl.* , m.`name` AS manufacturer_name, s.`name` AS supplier_name
				FROM `' . _DB_PREFIX_ . 'product` p
				' . Shop::addSqlAssociation('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` ' . Shop::addSqlRestrictionOnLang('pl') . ' AND pl.`id_lang` = ' . (int) $idLang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `' . _DB_PREFIX_ . 'supplier` s ON (s.`id_supplier` = p.`id_supplier`)
				WHERE product_shop.`visibility` IN ("both", "catalog")' .
                                ' AND product_shop.`active` = 1
                                  AND p.`on_sale` = 1
                                  OR p.`id_product` IN (select sp.`id_product` FROM `' . _DB_PREFIX_ . 'specific_price` sp WHERE sp.`from_quantity`=1)
				ORDER BY rand()' .
                ($limit > 0 ? ' LIMIT ' . (int) $start . ',' . (int) $limit : '');
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return Product::getProductsProperties($idLang, $result);
    }
    
    /**
     * Montamos los datos necesarios para la presentacion de los productos
     * @param array $productsOffer array con los productos
     * @return array productos con los datos necesarios para su presentacion
     */
    public function presentationProductsOffer($productsOffer) {
        $products_for_template = [];
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(new ImageRetriever($this->context->link), $this->context->link, new PriceFormatter(), new ProductColorsRetriever(), $this->context->getTranslator());

        if ($productsOffer) {
            foreach ($productsOffer as $product) {
                $products_for_template[] = $presenter->present($presentationSettings, $product, $this->context->language);
            }
        }

        return $products_for_template;
    }

}
