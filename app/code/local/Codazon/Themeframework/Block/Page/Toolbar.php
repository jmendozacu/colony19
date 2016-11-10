<?php
class Codazon_Themeframework_Block_Page_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{
	public function getLimit()
    {
		$limit = $this->getRequest()->getParam($this->getLimitVarName());
        return $limit?$limit:Codazon_Themeframework_Block_Page_Category::DEFAULT_LIMIT;
    }	
	public function isExpanded(){
		return false;	
	}
	
	public function getPagerHtml()
    {
        $pagerBlock = $this->getChild('category_list_toolbar_pager');

        if ($pagerBlock instanceof Varien_Object) {

            /* @var $pagerBlock Mage_Page_Block_Html_Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());

            $pagerBlock->setUseContainer(false)
                ->setShowPerPage(false)
                ->setShowAmounts(false)
                ->setLimitVarName($this->getLimitVarName())
                ->setPageVarName($this->getPageVarName())
                ->setLimit($this->getLimit())
                ->setFrameLength(Mage::getStoreConfig('design/pagination/pagination_frame'))
                ->setJump(Mage::getStoreConfig('design/pagination/pagination_frame_skip'))
                ->setCollection($this->getCollection());

            return $pagerBlock->toHtml();
        }

        return '';
    }
}
?>
