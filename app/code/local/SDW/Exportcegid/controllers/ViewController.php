<?php
class SDW_Exportcegid_ViewController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

		return $this;
	}

	public function indexAction()
	{
        $missingFile=array();
        foreach(array(
            "app/design/adminhtml/default/default/layout/exportcegid.xml",
            "app/design/adminhtml/default/default/template/exportcegid/form.phtml",
            "app/design/adminhtml/default/default/template/exportcegid/result.phtml"
        ) as $file)
        {
            if(!file_exists(BP.DS.$file))
            {
                $missingFile[]=$file;
            }
        }
        
        if(count($missingFile))
        {
            echo "Fichiers manquants :<pre>";
            print_r($missingFile);
            die;
        }
        
	
		$this->loadLayout()->renderLayout();
	}
	
	public function previewAction()
	{
        return $this->indexAction();
	}
	
	public function exportAction()
	{
        $block=Mage::getBlockSingleton('exportcegid/view');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=export-cegid-".date("d-m-Y").".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $stdout = fopen('php://output','w');
        $headerSent=false;
        foreach($block->exportData() as $row)
        {
            if(!$headerSent)
            {
                fputcsv($stdout, array_keys($row),";");
                $headerSent=true;
            }
            
            fputcsv($stdout, $row,";");
        }
        
        fflush($stdout);
        fclose($stdout);
        
	}
}

