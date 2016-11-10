<?php 
class Codazon_CodazonInstaller_Model_Slideshow extends Mage_Core_Model_Abstract
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
			array('title', 'identifier', 'slider', 'parameters', 'is_active')
		);
		
		$this->pageCollection = Mage::getModel('slideshow/slideshow')->getCollection();
		
		$this->pageCollection->addFieldToSelect('*');
		//$this->blockCollection->addFieldToFilter('identifier',array('like'=>$filter.'%'));
		//echo $this->blockCollection->getSelect();die;
		foreach($this->pageCollection as $page){
			$data = array();
			foreach($list[0] as $attribute){
				$data[] = $page->getData($attribute);
			}
			$list[] = $data;
		}

		$fp = fopen($path.'/slideshows.csv', 'w');

		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
		echo 'export slideshow finish'.'<br/>';
    }

    public function install($csvFile)
    {
        $file_handle = fopen($csvFile, 'r');
        $attributes = fgetcsv($file_handle, 1024);
		while (!feof($file_handle) ) {
			$data = fgetcsv($file_handle, 1024);
			if($data){
				$collection = Mage::getModel('slideshow/slideshow')->getCollection();
		        $collection->addFieldToFilter('title', $data[0]);

		        if ($collection->count() > 0) {
		            continue;
		        }
				$slideshow = Mage::getModel('slideshow/slideshow');
				foreach($attributes as $key => $att){
					$slideshow->setData($att,$data[$key]);
				}
				//$slideshow->setStores([0]);
				$slideshow->save();
			}
		}
		fclose($file_handle);
		Mage::getSingleton('core/session')->addSuccess('Slideshow were installed');
    }
}
