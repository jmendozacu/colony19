<?php

/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2016 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 */
class Activo_AdvancedSearch_Model_Query extends Mage_Core_Model_Abstract
{

    protected $_tableQuery;
    protected $_tableQueryResult;

    public function _construct()
    {
        $this->_tableQuery = $this->_resource()->getTableName('activo_advancedsearch_query');
        $this->_tableQueryResult = $this->_resource()->getTableName('activo_advancedsearch_query_result');
    }

    public function graphDataOperation()
    {
        $query_text = Mage::helper('catalogsearch')->getQuery()->getQueryText();
        if ($query_text)
        {
            $this->queryTextOperation($query_text);
        }
    }

    public function queryTextOperation($query_text)
    {
        $getCurrentDateRecords = 'SELECT * FROM ' . $this->_tableQuery . ' WHERE store_id = ' . Mage::app()->getStore()->getId() . ' AND created_at = "' . date("Y-m-d", Mage::getModel('core/date')->timestamp(time())) . '"';
        $matches = $this->_read()->fetchAll($getCurrentDateRecords);

        if (count($matches) > 0)
        {
            $query_id = $matches[0]['id'];
            $query_text_unserialize = unserialize($matches[0]['query_text']);
            $query_text_exists = array_key_exists($query_text, $query_text_unserialize);
            if ($query_text_exists)
            {
                $query_text_unserialize[$query_text] = $query_text_unserialize[$query_text] + 1;
            }
            else
            {
                $query_text_unserialize[$query_text] = 1;
            }

            //count unique query text
            $unique_query_text = array_count_values($query_text_unserialize);
            $unique_query_text_counts = isset($unique_query_text['1']) ? $unique_query_text['1'] : 0;

            //count total query text
            $total_query_text_count = array_sum($query_text_unserialize);

            $query_text_json = serialize($query_text_unserialize);

            //update table activo_advancedsearch_query            
            $query_fields_arr = array(
                'query_text' => $query_text_json,
            );
            $this->_write()->update($this->_tableQuery, $query_fields_arr, 'store_id = ' . Mage::app()->getStore()->getId() . ' AND id = ' . $query_id);

            //update table activo_advancedsearch_query_result
            $query_result_fields_arr = array(
                'popularity' => $total_query_text_count,
                'unique_count' => $unique_query_text_counts,
            );
            $this->_write()->update($this->_tableQueryResult, $query_result_fields_arr, 'query_id = ' . $query_id);
        }
        else
        {
            $query_text_count = array($query_text => 1);
            $query_text_json = serialize($query_text_count);

            //insert record into table activo_advancedsearch_query
            $query_fields_arr = array(
                'query_text' => $query_text_json,
                'store_id' => Mage::app()->getStore()->getId(),
                'created_at' => date("Y-m-d", Mage::getModel('core/date')->timestamp(time())),
            );
            $this->_write()->insert($this->_tableQuery, $query_fields_arr);

            //insert record into table activo_advancedsearch_query_result
            $query_result_fields_arr = array(
                'query_id' => $this->_write()->lastInsertId(),
                'popularity' => 1,
                'unique_count' => 1,
                'updated_at' => date("Y-m-d", Mage::getModel('core/date')->timestamp(time())),
            );

            $this->_write()->insert($this->_tableQueryResult, $query_result_fields_arr);
        }
    }

    public function getGraphData()
    {
        $store_id = '';
        if (Mage::app()->getRequest()->getParam('store'))
        {
            $store_id = Mage::app()->getRequest()->getParam('store');
        }
        if ($store_id)
        {
            $getSelect = 'SELECT q.id, q.query_text, q.store_id, q.created_at, qr.query_id, qr.popularity, qr.unique_count FROM ' . $this->_tableQuery . ' AS q INNER JOIN ' . $this->_tableQueryResult . ' AS qr ON q.id = qr.query_id WHERE q.store_id = ' . $store_id . ' AND q.created_at > DATE_SUB("' . date("Y-m-d", Mage::getModel('core/date')->timestamp(time())) . '", INTERVAL 7 DAY) ORDER BY q.created_at';
        }
        else
        {
            $getSelect = 'SELECT q.id, q.query_text, q.store_id, q.created_at, qr.query_id, SUM(qr.popularity) as popularity, SUM(qr.unique_count) as unique_count FROM ' . $this->_tableQuery . ' AS q INNER JOIN ' . $this->_tableQueryResult . ' AS qr ON q.id = qr.query_id WHERE q.created_at > DATE_SUB("' . date("Y-m-d", Mage::getModel('core/date')->timestamp(time())) . '", INTERVAL 7 DAY) GROUP BY q.created_at ORDER BY q.created_at';
        }
        $getData = $this->_read()->fetchAll($getSelect);
        $graphData = $this->createGraphDataArray($getData);
        return $graphData;
    }

    public function createGraphDataArray($getData)
    {
        if (count($getData) > 0)
        {
            $graphArr = array(array('Date', 'Total Searches', 'Unique Searches'));
            foreach ($getData as $obj)
            {
                $graphArr[] = array(date('m/d', strtotime($obj['created_at'])), (int) $obj['popularity'], (int) $obj['unique_count']);
            }
        }
        else
        {
            $graphArr = array();
        }
        return $graphArr;
    }

    public function _resource()
    {
        return Mage::getSingleton('core/resource');
    }

    public function _write()
    {
        return $this->_resource()->getConnection('core_write');
    }

    public function _read()
    {
        return $this->_resource()->getConnection('core_read');
    }

}
