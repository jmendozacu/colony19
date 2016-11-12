<?php

class SDW_Reportmarge_Block_Adminhtml_Listing_Render_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $data = array();
        if (get_class($row) === 'Mage_Catalog_Model_Product') {
            $data = $this->loadData($row);
        } else {
            $data = $this->loadTotals($row);
        }

        $return = 0;
        if (isset($data[$this->getColumn()->getIndex()])) {
            $return = $data[$this->getColumn()->getIndex()];
        }

        return $return;
    }

    public function loadData($row)
    {
        if (!Zend_Registry::isRegistered('marge_totale')) {
            return array();
        }

        $providerModel = Mage::getModel('provider/provider')->load((int) $row->provider);

        $marge_totale = Zend_Registry::get('marge_totale');
        $data         = $providerModel->getData();
        $prix_revient = ($row->prix_achat + ($row->prix_achat * $data['transport'] / 100)) * $row->ordered_qty;

        return array(
            'ordered_total'          => number_format($row->ordered_total, 2, ',', ' '), // CA HT
            'marge_brute'            => number_format($row->ordered_total - $prix_revient, 2, ',', ' '), // MB
            'taux_marge_brute'       => number_format(((($row->ordered_total - $prix_revient) / $row->ordered_total) * 100), 2, ',', ' '), // MB/CA
            'taux_marge_brute_total' => number_format($marge_brute / $marge_totale * 100, 2, ',', ' '), // %MB/Total
        );
    }

    public function loadTotals($row)
    {
        if (
            !Zend_Registry::isRegistered('ca_total')
            || !Zend_Registry::isRegistered('marge_totale')
        ) {
            return array();
        }

        return array(
            'ordered_total'          => number_format(Zend_Registry::get('ca_total'), 2, ',', ' '), // CA HT
            'marge_brute'            => number_format(Zend_Registry::get('marge_totale'), 2, ',', ' '), // MB
            'taux_marge_brute'       => number_format(((Zend_Registry::get('marge_totale') / Zend_Registry::get('ca_total')) * 100), 2, ',', ' '), // MB/CA
            'taux_marge_brute_total' => 100, // %MB/Total
        );
    }
}
