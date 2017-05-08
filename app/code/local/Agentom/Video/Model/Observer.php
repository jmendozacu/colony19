<?php
class Agentom_Video_Model_Observer
{

			public function updateCustomerForAuthorizedCategories(Varien_Event_Observer $observer)
			{
                $invoice = $observer->getEvent()->getInvoice();
                $order = $invoice->getOrder();

                $authorizedId = array();
                foreach ($order->getAllItems() as $item) {
                    $product = $item->getProduct();
                    $product = Mage::getModel('catalog/product')->load($product->getId());
                    if(!is_null($product->getGrantAccessToCategory()) && !empty($product->getGrantAccessToCategory())){
                        $authorizedId[] = $product->getGrantAccessToCategory();
                    }
                }

                if(count($authorizedId)){
                    $customerId = $order->getCustomerId();
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    $previousIds = $customer->getAllowedCategoryIds();
                    if(!is_null($previousIds) && !empty($previousIds)){
                        $customer->setAllowedCategoryIds(implode(",",array_merge($previousIds,$authorizedId)));
                    }else{
                        $customer->setAllowedCategoryIds(implode(",",$authorizedId));
                    }

                    $customer->save();
                }

			}
		
}
