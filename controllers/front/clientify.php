<?php

class AdminClientifyClientifyModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();
        $this->assign();
    }

    public function assign()
    {
        $this->insertLog("firstGridData");
        $productsPerPage = Configuration::get("PS_PRODUCTS_PER_PAGE");
        $categoryData = explode("-", Tools::getValue('c'));
        $id_category = ((count($categoryData) > 0) ? $categoryData[0] : "NULL");
        $in_house_sku_enable = (Configuration::get('PRE_SALE_ENABLE_SKU_IN_HOUSE') == null) ? 0 : Configuration::get('PRE_SALE_ENABLE_SKU_IN_HOUSE');
        $in_house_price_enable = (Configuration::get('PRE_SALE_ENABLE_PRICE_IN_HOUSE') == null) ? 0 : Configuration::get('PRE_SALE_ENABLE_PRICE_IN_HOUSE');
        $dealer_sku_enable = (Configuration::get('PRE_SALE_ENABLE_SKU_DEALER') == null) ? 0 : Configuration::get('PRE_SALE_ENABLE_SKU_DEALER');
        $dealer_price_enable = (Configuration::get('PRE_SALE_ENABLE_PRICE_DEALER') == null) ? 0 : Configuration::get('PRE_SALE_ENABLE_PRICE_DEALER');

        $sql = "SELECT psi.id, psi.id_customer, psi.product_name, FORMAT(psi.price, 0) AS price, psi.sku,
                   (SELECT filesystem_path 
                      FROM {$this->db_prefix}mhf_pre_sale_items_photos psip 
                     WHERE psip.pre_sale_item_id = psi.id 
                       AND `position` = 0) AS cover_image,
                   (SELECT psc.name 
                      FROM {$this->db_prefix}mhf_pre_sale_category psc
                     WHERE psc.id = psi.id_category) AS category_name,
                   CASE
                       WHEN pc.email = '{$this->in_house_email}' THEN 1
                       ELSE 0
                   END AS in_house
              FROM {$this->db_prefix}mhf_pre_sale_items psi INNER JOIN {$this->db_prefix}customer pc
                ON psi.id_customer = pc.id_customer
             WHERE psi.status = '{$this->search_status}' 
               AND psi.active = 1
               AND (($id_category IS NULL) OR ($id_category IS NOT NULL AND psi.id_category = $id_category))
             ORDER BY psi.id DESC";

        $limit = " LIMIT 0, $productsPerPage";

        $nPsProducts = $this->executeSelect($sql);

        $psProducts = $this->executeSelect($sql . $limit);

        $totalPages = ceil((count($nPsProducts) / $productsPerPage));

        foreach ($psProducts as &$product) {
            $name = str_replace("&", " ", $product["product_name"]);
            $name = str_replace(" ", "-", $name);
            $name = strtolower($name);

            $category = str_replace("&", " ", $product["category_name"]);
            $category = str_replace(" ", "-", $category);
            $category = strtolower($category);

            $product['link_rewrite'] = "/module/presale/product?p=$category/{$product['id']}-$name";
        }

        $sqlTitleInfo = "SELECT description, breadcrumb 
                       FROM {$this->db_prefix}gmcatseconddesc
                      WHERE id_category = 231";

        $titleInfo = $this->executeSelect($sqlTitleInfo);

        $this->context->smarty->assign(array(
            'psProducts' => $psProducts,
            'nPsProducts' => count($nPsProducts),
            'startProduct' => 1,
            'endProduct' => count($psProducts),
            'totalPages' => $totalPages,
            'currentPage' => 1,
            'preSaleTitle' => (count($titleInfo) > 0) ? $titleInfo[0]['description'] : "Pre Sale Products",
            'breadcrumb' => [
                'data' => (count($titleInfo) > 0) ? $titleInfo[0]['breadcrumb'] : ""
            ],
            'filterCriterion' => [
                [
                    'id' => 'categories',
                    'name' => "Categories",
                    'options' => $this->getListingByCategory(),
                    'selected' => ($id_category != "NULL") ? $id_category : 0
                ],
                [
                    'id' => 'brands',
                    'name' => "Brands",
                    'options' => $this->getListingByBrand()
                ],
                [
                    'id' => 'designers',
                    'name' => "Designers",
                    'options' => $this->getListingByDesigner()
                ]
            ],
            "in_house_sku_enable" => $in_house_sku_enable,
            "in_house_price_enable" => $in_house_price_enable,
            "dealer_sku_enable" => $dealer_sku_enable,
            "dealer_price_enable" => $dealer_price_enable
        ));

        $this->setTemplate('module:presale/views/templates/front/grid.tpl');
    }

}

