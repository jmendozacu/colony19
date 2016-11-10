<?php 
class Codazon_CodazonInstaller_Model_Blog_Post extends Mage_Core_Model_Abstract
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
			array('title', 'cats', 'post_content', 'status', 'identifier', 'user', 'update_user', 'tags', 'short_content', 'post_image')
		);
		
		$collection = Mage::getModel('blog/post')->getCollection();
		
		$collection->addFieldToSelect('*');
		//$this->blockCollection->addFieldToFilter('identifier',array('like'=>$filter.'%'));
		//echo $this->blockCollection->getSelect();die;
		foreach($collection as $item){
			$model = Mage::getModel('blog/post')->load($item->getId());
			$data = array();
			foreach($list[0] as $attribute){
				if($attribute == 'cat_id'){
					$data[] = implode(',',$model->getData($attribute));
				}else{
					$data[] = $model->getData($attribute);
				}
			}
			$list[] = $data;
		}
		$fp = fopen($path.'/blog_posts.csv', 'w');
		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
		echo 'export blog post finish'.'<br/>';
    }

    public function install($csvFile)
    {
        $file_handle = fopen($csvFile, 'r');
        $attributes = fgetcsv($file_handle, 1024);
		while (!feof($file_handle) ) {
			$data = fgetcsv($file_handle, 1024);
			if($data){
				$collection = Mage::getModel('blog/post')->getCollection();
		        $collection->addFieldToFilter('title', $data[0]);

		        if ($collection->count() > 0) {
		            continue;
		        }
				$post = Mage::getModel('blog/post');
				foreach($attributes as $key => $att){
					if($att == 'cats'){
						$cats = explode(',',$data[$key]);
						$post->setData($att,$cats);
					}else{
						$post->setData($att,$data[$key]);
					}
				}
				$post->setStores(array(0));
				$post->save();
			}
		}
		fclose($file_handle);
		Mage::getSingleton('core/session')->addSuccess('Blog posts were installed');
    }
}
