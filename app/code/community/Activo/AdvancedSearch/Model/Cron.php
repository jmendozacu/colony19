<?php

class Activo_AdvancedSearch_Model_Cron extends Mage_Core_Model_Abstract
{
    /**
     * Cron job method for auto complete to reindex
     *
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function reindexAutoComplete(Mage_Cron_Model_Schedule $schedule)
    {
        $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode('advancedsearch_complete');
        if ($indexProcess) {
            $indexProcess->reindexAll();
        }
    }
    
    /**
     * Cron job method for auto suggest to reindex
     *
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function reindexAutoSuggest(Mage_Cron_Model_Schedule $schedule)
    {
        $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode('advancedsearch_suggest');
        if ($indexProcess) {
            $indexProcess->reindexAll();
        }
    }
}
