<?php 
class Codazon_CodazonInstaller_Model_Block extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("block/block");

    }
    
    public function export($packageTheme)
    {
    	$code = str_replace('/', '_', $packageTheme);
		$code = str_replace('codazon_','',$code);
    	$filter = str_replace('/', '-', $packageTheme);
		$filter = str_replace('codazon_','',$filter);
    	$path = dirname(__DIR__).'/import/'.$code;
    	$file = new Varien_Io_File(); 
		//Create folder
		$result = $file->mkdir($path);
		
        $list = array (
			array('title', 'identifier', 'content')
		);
		
		$this->blockCollection = Mage::getModel('cms/block')->getCollection();
		
		$this->blockCollection->addFieldToSelect('*');
		$this->blockCollection->addFieldToFilter('identifier',array('like'=>$filter.'%'));
		//echo $this->blockCollection->getSelect();die;
		foreach($this->blockCollection as $block){
			$data = array();
			foreach($list[0] as $attribute){
				$data[] = $block->getData($attribute);
			}
			$list[] = $data;
		}

		$fp = fopen($path.'/blocks.csv', 'w');

		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
		echo 'export block finish'.'<br/>';
    }

    public function install($csvFile)
    {
        $file_handle = fopen($csvFile, 'r');
        $data = fgetcsv($file_handle, 1024);
		while (!feof($file_handle) ) {
			$data = fgetcsv($file_handle, 1024);
			if($data){
				$collection = Mage::getModel('cms/block')->getCollection();
		        $collection->addFieldToFilter('title', $data[0]);

		        if ($collection->count() > 0) {
		            continue;
		        }
				$block = Mage::getModel('cms/block');
				$block->setStores(array(0));
				$block->setData('title',$data[0]);
				$block->setData('identifier',$data[1]);
				$block->setData('content',$data[2]);
				$block->save();
			}
		}
		fclose($file_handle);
		Mage::getSingleton('core/session')->addSuccess('Blocks were installed');
    }
}

