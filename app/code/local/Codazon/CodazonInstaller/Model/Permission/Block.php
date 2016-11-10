<?php 
class Codazon_CodazonInstaller_Model_Permission_Block extends Mage_Core_Model_Abstract
{
    
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
			array('block_name', 'is_allowed')
		);
		
		$collection = Mage::getModel('admin/block')->getCollection();
		
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
		$fp = fopen($path.'/permission_blocks.csv', 'w');
		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
		echo 'export permission blocks finish'.'<br/>';
    }

    public function install($csvFile)
    {
        $file_handle = fopen($csvFile, 'r');
        $attributes = fgetcsv($file_handle, 1024);
		while (!feof($file_handle) ) {
			$data = fgetcsv($file_handle, 1024);
			if($data){
				$collection = Mage::getModel('admin/block')->getCollection();
		        $collection->addFieldToFilter('block_name', $data[0]);

		        if ($collection->count() > 0) {
		            continue;
		        }
				$model = Mage::getModel('admin/block');
				foreach($attributes as $key => $att){
					$model->setData($att,$data[$key]);
				}
				$model->save();
			}
		}
		fclose($file_handle);
		Mage::getSingleton('core/session')->addSuccess('Permission blocks were installed');
    }
}
