<?php
class SDW_Product_Helper_Data
{

    private $bIsInStock = true;

    /**
     * On vérifie dans l'enselmble des produits groupés si un produit n'est pas en stock
     * Si c'est le cas, aucun produit ne sera vendable
     */
    // 
    public function getDefineIfAllIsInStockOrNot($products = null) {
        if(!empty($products))
            foreach($products as $product)
                if(!$product->isSaleable())
                    $this->bIsInStock = false;

        return $this->bIsInStock;
    }

    /**
     * 
     */
    public function getAssociatedProductsInCategoryList($productId) {
        $_product = Mage::getModel('catalog/product')->load($productId);

        if($_product->getTypeId() == "grouped") {
            $_associatedProducts = $_product->getTypeInstance(true)->getAssociatedProducts($_product);
            if($_associatedProducts) {
                return $_associatedProducts;
            }
        }
    }

    /**
     * 
     */
    public function getTotalPriceAssociatedProducts($products, $bOnlyPrice = false) {
        $symbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        $total = 0;
        $realTotal = 0;
        foreach($products as $_item):
            $total += Mage::helper('tax')->getPrice($_item, $_item->getFinalPrice());
            $realTotal += Mage::helper('tax')->getPrice($_item, $_item->getPrice());
        endforeach;

        if($bOnlyPrice)
            if($total != $realTotal)
                return $total;
            else
                return $realTotal;

        $html = '<div class="old-price-promo"><span class="price-origin">'.number_format($realTotal, 2, ',', ' ').'&nbsp;'.$symbol.'</span>';

        if($total != $realTotal)
            $html .= '<span class="percent">-'.$this->getPercent($total, $realTotal).'<span>%</span></span>';

        $html .= '<div class="price-box"><p class="minimal-price"><span class="price">'.number_format($total, 2, ',', ' ').'&nbsp;'.$symbol.'</span></p></div>';

        return $html.'</div>';
    }

    /**
     * 
     */
    public function createInputs($products, $qty) {
        $i = 0;$html = '';
        foreach($products as $_item) {
            // if($i > 0)
                $html .= '<input type="hidden" name="super_group['.$_item->getId().']" maxlength="12" value="'.($_item->getQty() * 1).'" title="'.$qty.'" class="update-with-primary" />';
            $i++;
        }

        return $html;
    }

    /**
     * 
     */
    private function getPercent($total, $realTotal) {
        $percent = ($total * 100) / $realTotal;

        return floor((100 - $percent));
    }

}