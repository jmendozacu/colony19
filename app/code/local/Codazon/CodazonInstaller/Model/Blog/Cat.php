<?php 
class Codazon_CodazonInstaller_Model_Blog_Cat extends Mage_Core_Model_Abstract
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
    	$path = dirname(dirname(__DIR__)).'/import/'.$code;
    	$file = new Varien_Io_File(); 
		//Create folder
		$result = $file->mkdir($path);

        $list = array (
			array('title', 'identifier', 'sort_order', 'cat_image')
		);
		
		$collection = Mage::getModel('blog/cat')->getCollection();
		
		$collection->addFieldToSelect('*');
		//$this->blockCollection->addFieldToFilter('identifier',array('like'=>$filter.'%'));
		//echo $this->blockCollection->getSelect();die;
		foreach($collection as $item){
			$data = array();
			foreach($list[0] as $attribute){
				$data[] = $item->getData($attribute);
			}
			$list[] = $data;
		}
		$fp = fopen($path.'/blog_cats.csv', 'w');
		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
		echo 'export blog cat finish'.'<br/>';
    }

    public function install($csvFile)
    {
        $file_handle = fopen($csvFile, 'r');
        $attributes = fgetcsv($file_handle, 1024);
		while (!feof($file_handle) ) {
			$data = fgetcsv($file_handle, 1024);
			if($data){
				$collection = Mage::getModel('blog/cat')->getCollection();
		        $collection->addFieldToFilter('identifier', $data[1]);

		        if ($collection->count() > 0) {
		            continue;
		        }
				$cat = Mage::getModel('blog/cat');
				foreach($attributes as $key => $att){
					$cat->setData($att,$data[$key]);
				}
				$cat->setStores(array(0));
				$cat->save();
			}
		}
		fclose($file_handle);
		Mage::getSingleton('core/session')->addSuccess('Blog cat were installed');
    }
}
