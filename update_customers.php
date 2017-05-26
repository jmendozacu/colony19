<?php
/**
 * Created by PhpStorm.
 * User: ryans
 * Date: 26/05/2017
 * Time: 08:24
 */
require_once 'app/Mage.php';
Mage::app();
try{
    $resource = Mage::getSingleton('core/resource');
    $readConnection = $resource->getConnection('core_read');
    $query ="SELECT so.customer_id FROM mgt_sales_flat_order as so
    INNER JOIN mgt_sales_flat_order_item as si ON si.order_id = so.entity_id AND (si.product_id = 5360 || si.product_id = 5357)
    WHERE so.customer_id IS NOT NULL GROUP BY so.customer_id;";
    $results = $readConnection->fetchAll($query);
    foreach ($results as $customerId){
        echo $customerId . "<br/>";
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if(is_null($customer->getAllowedCategoryIds()) || empty($customer->getAllowedCategoryIds())){
            echo "Need update <br/>";
            $customer->setAllowedCategoryIds("250,251");
            $customer->save();
            echo "Done.<br/>";
        }
    }
}catch (Exception $e){
    echo $e->getMessage();
}
echo "Finished !";