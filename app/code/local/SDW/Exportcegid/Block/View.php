<?php

class SDW_Exportcegid_Block_View extends Mage_Core_Block_Template
{
    public function getGetValue($field)
    {
        if(isset($_GET[$field]))
            return urldecode($_GET[$field]);
            
        return NULL;
    }

    public function getStoreTree()
    {
        $select=array();
        $select[]=array(
            array(),
            "Toutes les boutiques"
        );
        foreach(Mage::app()->getWebsites() as $website)
        {
            $select=$this->getStoreTree_sub_website($website,$select);
            
        }
        return $select;
    }
        
    private function getStoreTree_sub_website($website,$select)
    {
        $websiteArray=array(
            array(),
            $website->getName()
        );
        $select[]=&$websiteArray;
        
        foreach($website->getGroups() as $group)
        {
            $select=$this->getStoreTree_sub_group($websiteArray,$group,$select);
        }
        return $select;
    }
    
    private function getStoreTree_sub_group(&$websiteArray,$group,$select)
    {
        $groupArray=array(
            array(),
            "&nbsp;&nbsp;&nbsp;".$group->getName()
        );
        $select[]=&$groupArray;
        
        foreach($group->getStores() as $store)
        {
            $select=$this->getStoreTree_sub_store($websiteArray,$groupArray,$store,$select);
        }
        
        return $select;
    }
    
    private function getStoreTree_sub_store(&$websiteArray,&$groupArray,$store,$select)
    {
        $websiteArray[0][]=$store->getStoreId();
        $groupArray[0][]=$store->getStoreId();
        
        $select[]=array(
            array($store->getStoreId()),
            "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$store->getName()
        );
        
        return $select;
    }

    
    public function exportData()
    {
        $website=array_filter(explode(",",$this->getGetValue("store")));
        $from=$this->getGetValue("from");
        $to=$this->getGetValue("to");
        
        if(!$from)$from=date("d/m/Y",mktime(0,0,0,date("n")-1,1,date("Y")));
        if(!$to)$to=date("d/m/Y",mktime(0,0,-1,date("n"),1,date("Y")));
        
        $from=preg_replace("#([0-9]{2})/([0-9]{2})/([0-9]{4})#","$3-$2-$1",$from);
        $to=preg_replace("#([0-9]{2})/([0-9]{2})/([0-9]{4})#","$3-$2-$1",$to);
        
        // Inverse from et to si besoin pour avoir un ordre croissant
        list($from,$to)=array(
            min($from,$to),
            max($from,$to),
        );
        
        $rows=array();
        
        
        $collection=Mage::getResourceModel('sales/order_invoice_collection');
        $collection->addAttributeToFilter('created_at', array(
            'from'  => $from,
            'to'    => $to
        ));
        if(count($website))$collection->addAttributeToFilter('store_id',$website);
        
        foreach($collection as $invoice)
        {
            $rows=$this->exportData_sub_processInvoice($invoice,$rows,"-0");
        }
        
        
        // Les avoirs sont traités de la même façon que les factures.
        // Mais la fonction d'export de ligne, inverse crédit/débit pour les avoirs.
        $collection=Mage::getResourceModel('sales/order_creditmemo_collection');
        $collection->addAttributeToFilter('created_at', array(
            'from'  => $from,
            'to'    => $to
        ));
        if(count($website))$collection->addAttributeToFilter('store_id',$website);
        
        foreach($collection as $invoice)
        {
            $rows=$this->exportData_sub_processInvoice($invoice,$rows,"-1");
        }
        
        ksort($rows);
        
        return $rows;
    }
    
    public function exportData_sub_processInvoice($invoice,array $rows,$prefixKey)
    {
        $order=Mage::getModel("sales/order")->load($invoice->getOrderId());
        $customer=Mage::getModel("customer/customer")->load($order->getCustomerId());
        $invoicekey=date("Ymdhis",strtotime($invoice->getCreatedAt()))."-".$invoice->getIncrementId()."-".$prefixKey;
    
        $totalsProduits=array();
        $totalsTransports=array();
        $totalsTva=array();
        $labelsProduits=array();
        $labelsTransports=array();
        $labelsTva=array();
                
        foreach($invoice->getAllItems() as $item)
        {
            if($item->getRowTotal()===NULL)continue;
            
            $classTaxId=$this->exportData_sub_getProductTaxClass($item->getProductId());
            
            $compteTva=Mage::getStoreConfig("configuration_tax/tva/class_$classTaxId");
            $totalsTva[$compteTva]+=$item->getTaxAmount();
            $labelsTva[$compteTva]=Mage::getStoreConfig("configuration_tax/tvalabel/class_$classTaxId");
            
            $compteProduits=Mage::getStoreConfig("configuration_tax/produits/class_$classTaxId");
            $totalsProduits[$compteProduits]+=$item->getRowTotal();
            $labelsProduits[$compteProduits]=Mage::getStoreConfig("configuration_tax/produitslabel/class_$classTaxId");
        }
        
        if($invoice->getShippingAmount()>0)
        {
            if($invoice->getShippingTaxAmount())
            {
                $compteTransport=Mage::getStoreConfig("configuration_tax/transport/class_avec");
                $totalsTransports[$compteTransport]+=$invoice->getShippingAmount();
                $labelsTransports[$compteTransport]=Mage::getStoreConfig("configuration_tax/transportlabel/class_avec");
                
                $compteTva=Mage::getStoreConfig("configuration_tax/tva/class_avec");
                $totalsTva[$compteTva]+=$invoice->getShippingTaxAmount();
                $labelsTva[$compteTva]=Mage::getStoreConfig("configuration_tax/tvalabel/class_avec");
            }
            else
            {
                $compteTransport=Mage::getStoreConfig("configuration_tax/transport/class_sans");
                $totalsTransports[$compteTransport]+=$invoice->getShippingAmount();
                $labelsTransports[$compteTransport]=Mage::getStoreConfig("configuration_tax/transportlabel/class_sans");
            }
        }
        
        $delta=round($invoice->getGrandTotal(),2);
        foreach($totalsProduits as $compteNum=>$montantCredit)$delta-=round($montantCredit,2);
        foreach($totalsTransports as $compteNum=>$montantCredit)$delta-=round($montantCredit,2);
        foreach($totalsTva as $compteNum=>$montantCredit)$delta-=round($montantCredit,2);
        foreach($totalsTva as $compteNum=>$montantCredit)
        {
            $totalsTva[$compteNum]+=$delta;
            $delta=0;
        }
        
        
        $rows[$invoicekey."-0"]=$this->exportData_sub_createRow($invoice,$order,
                        $customer->getNumeroCompteClient()  ?   $customer->getNumeroCompteClient()  :   "4111DIVS",
                        $customer->getSociale()             ?   $customer->getSociale()             :   ($customer->getFirstname()." ".$customer->getLastname()),
                        $invoice->getGrandTotal(),0);

        foreach($totalsProduits as $compteNum=>$montantCredit)
            if($montantCredit)
                $rows[$invoicekey."-1-".$compteNum]=$this->exportData_sub_createRow($invoice,$order,$compteNum,$labelsProduits[$compteNum],0,$montantCredit);
            
        foreach($totalsTransports as $compteNum=>$montantCredit)
            if($montantCredit)
                $rows[$invoicekey."-2-".$compteNum]=$this->exportData_sub_createRow($invoice,$order,$compteNum,$labelsTransports[$compteNum],0,$montantCredit);
            
        foreach($totalsTva as $compteNum=>$montantCredit)
            if($montantCredit)
                $rows[$invoicekey."-3-".$compteNum]=$this->exportData_sub_createRow($invoice,$order,$compteNum,$labelsTva[$compteNum],0,$montantCredit);
            
        return $rows;
    }
    
    private function exportData_sub_createRow($invoice,$order,$CompteNum,$CompteLib,$Debit,$Credit)
    {
        // Si on est sur un avoir, on inverse crédit/débit.
        if(is_a($invoice,"Mage_Sales_Model_Order_Creditmemo"))
        {
            list($Debit,$Credit)=array($Credit,$Debit);
        }
        
        if(is_a($invoice,"Mage_Sales_Model_Order_Creditmemo"))
        {
            $labelEcriture="Remboursement commande #".$order->getIncrementId();
        }
        else
        {
            $labelEcriture="Commande #".$order->getIncrementId();
        }
        
        return array(
            "JournalCode"=>"VT",
//             "JournalLib"=>"JOURNAL DES VENTES",
//             "EcritureNum"=>$invoice->getIncrementId(),
            "EcritureDate"=>date("Ymd",strtotime($invoice->getCreatedAt())),
            "CompteNum"=>$CompteNum,
            "CompteLib"=>$CompteLib,
//             "CompAuxNum"=>"",
//             "CompAuxLib"=>"",
            "PieceRef"=>$invoice->getIncrementId(),
//             "PieceDate"=>date("Ymd",strtotime($invoice->getCreatedAt())),
            "EcritureLib"=>$labelEcriture,
            "Debit"=>round($Debit,2),
            "Credit"=>round($Credit,2),
//             "EcritureLet"=>"",
//             "DateLet"=>"",
//             "ValidDate"=>"",
//             "MontantDevise"=>"",
//             "Idevise"=>"",
            "lettrage"=>"",
            "code tva"=>""

        );
    }
    
    private function exportData_sub_getProductTaxClass($productId)
    {
        static $cache=array();
        if(!isset($cache[$productId]))
        {
            $product=Mage::getModel("catalog/product")->load($productId);
            $cache[$productId]=$product->getTaxClassId();
        }
        return $cache[$productId];
    }
    
}
