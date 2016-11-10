<?php 
class Codazon_CodazonInstaller_Model_Widget extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("widget/widget");

    }
    
    protected function _initWidgetInstance($widget)
    {
        $widgetInstance = Mage::getModel('widget/widget_instance');

        $code = 'cms/widget_block';
        $instanceId = $widget->getInstanceId();
        if ($instanceId) {
            $widgetInstance->load($instanceId)->setInstanceType($code);
            if (!$widgetInstance->getId()) {
                $this->messageManager->addError(__('Please specify a correct widget.'));
                return false;
            }
        } else {
            // Widget id was not provided on the query-string.  Locate the widget instance
            // type (namespace\classname) based upon the widget code (aka, widget id).
            $theme = $widget->getPackageTheme();
            $type = $code != null ? $widgetInstance->getWidgetReference('code', $code, 'type') : null;
            $widgetInstance->setInstanceType($code)->setPackageTheme($theme);
        }
        return $widgetInstance;
    }
    
    public function export($packageTheme)
    {
    	$code = str_replace('/', '_', $packageTheme);
		$code = str_replace('codazon_','',$code);
    	$path = dirname(__DIR__).'/import/'.$code;
    	$file = new Varien_Io_File(); 
		//Create folder
		$result = $file->mkdir($path);
        $list = array (
			array('block_identifier', 'instance_type', 'package_theme', 'title', 'page_groups', 'sort_order')
		);
		
		$widgetCollection = Mage::getModel('widget/widget_instance')->getCollection()->addFieldToSelect('*');
		$widgetCollection->addFieldToFilter('package_theme',$packageTheme);
		//$widgetCollection->addFieldToFilter('instance_id',20);
		//$widgetCollection->join(array('wip' => 'widget_instance_page'), 'main_table.instance_id = wip.instance_id');
		//$widgetCollection->join(array('t' => 'theme'), 'main_table.theme_id = t.theme_id',array('theme_path'));
		foreach($widgetCollection as $widget){
			
			$data = array();
			$params = $widget->getWidgetParameters();
			$instance_type = $widget->getData('instance_type');
			if($instance_type == 'slideshow/slideshow'){
	        	$block_identifier = Mage::getModel('slideshow/slideshow')->load($params['slideshow_id'])->getData('identifier');
	        }else if($instance_type == 'cms/widget_block'){
			    $block_identifier = Mage::getModel('cms/block')->load($params['block_id'])->getData('identifier');
	        }
			$data['block_identifier'] = $block_identifier;
			$data['instance_type'] = $widget->getData('instance_type');
			$data['package_theme'] = $widget->getData('package_theme');
			$data['title'] = $widget->getTitle();
			//$data['page_group'] = $widget->getPageGroup();
			//$params = [];
			//$params['block'] = $widget->getData('block_reference');
			//$params['layout_handle'] = $widget->getData('layout_handle');
			$widget = $this->_initWidgetInstance($widget);
			$pageGroups = $widget->getPageGroups();
			$tmpPg = array();
			foreach($pageGroups as $pageGroup){
				$tmp = array();
				$pg = $pageGroup['page_group'];
				$tmp['page_group'] = $pg;
				$tmp[$pg] = array();
				$tmp[$pg]['for'] = $pageGroup['page_for'];
				$tmp[$pg]['layout_handle'] = $pageGroup['layout_handle'];
				$tmp[$pg]['block'] = $pageGroup['block_reference'];
				//$tmp[$pg]['template'] = $pageGroup['page_template'];
				//$tmp[$pg]['page_id'] = '';
				$tmpPg[] = $tmp;
			}
			$pageGroups = $tmpPg;
			$data['page_groups'] = serialize($pageGroups);
			
			$data['sort_order'] = $widget->getData('sort_order');
			$list[] = $data;
		}

		$fp = fopen($path.'/widgets.csv', 'w');

		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
		echo 'export widget finish'.'<br/>';
    }
    

    public function install($csvFile)
    {
        $pageGroupConfig = array(
            'pages' => array(
                'block' => '',
                'for' => 'all',
                'layout_handle' => 'default',
                'template' => 'cms/widget/static_block/default.phtml',
                'page_id' => '',
            ),
            'all_pages' => array(
                'block' => '',
                'for' => 'all',
                'layout_handle' => 'default',
                'template' => 'cms/widget/static_block/default.phtml',
                'page_id' => '',
            ),
            'all_products' => array(
                'block' => '',
                'for' => 'all',
                'layout_handle' => 'catalog_product_view',
                'template' => 'cms/widget/static_block/default.phtml',
                'page_id' => '',
            ),
            'anchor_categories' => array(
                'entities' => '',
                'block' => '',
                'for' => 'all',
                'is_anchor_only' => 0,
                'layout_handle' => 'catalog_category_view_type_layered',
                'template' => 'cms/widget/static_block/default.phtml',
                'page_id' => '',
            ),
        );
		$file_handle = fopen($csvFile, 'r');
		$tmp = fgetcsv($file_handle, 1024);
		while (!feof($file_handle) ) {
			$row = fgetcsv($file_handle, 1024);
			/** @var \Magento\Widget\Model\ResourceModel\Widget\Instance\Collection $instanceCollection */
			if($row){
		        $instanceCollection = Mage::getModel('widget/widget_instance')->getCollection();
		        $instanceCollection->addFieldToFilter('title', $row[3]);

		        if ($instanceCollection->count() > 0) {
		            continue;
		        }
		        
		        $block = '';
		        $slideshow = '';
		        $parameters = array();
		        
		        if($row[1] == 'slideshow/slideshow'){
		        	$collection = Mage::getModel('slideshow/slideshow')->getCollection();
		        	$collection->addFieldToFilter('identifier', $row[0]);
		        	$slideshow = $collection->getFirstItem();
				    if (!$slideshow) {
				        continue;
				    }
				    $parameters = array('slideshow_id' => $slideshow->getId());
		        }else if($row[1] == 'cms/widget_block'){
		        	$collection = Mage::getModel('cms/block')->getCollection();
		        	$collection->addFieldToFilter('identifier', $row[0]);
		        	$block = $collection->getFirstItem();
				    if (!$block) {
				        continue;
				    }
				    $parameters = array('block_id' => $block->getId());
		        }
		        $widgetInstance = Mage::getModel('widget/widget_instance');

		        $code = $row[1];

		        $type = $widgetInstance->getWidgetReference('code', $code, 'type');
		        $pageGroups = unserialize($row[4]);
		        $tmpPg = array();
				
			    foreach($pageGroups as $pageGroup){
			    	$group = $pageGroup['page_group'];
				    $pageGroup[$group] = array_merge($pageGroupConfig[$group], $pageGroup[$group]);
				    if (!empty($pageGroup[$group]['entities'])) {
				        $pageGroup[$group]['entities'] = $this->getCategoryByUrlKey(
				            $pageGroup[$group]['entities']
				        )->getId();
				    }
				    $tmpPg[] = $pageGroup;
			    }
				$pageGroups = $tmpPg;

		        $widgetInstance->setInstanceType($code)->setPackageTheme($row[2]);
		        $widgetInstance->setTitle($row[3])
		        	//->setSortOrder($row['sort_order'])
		            ->setStoreIds(array(0))
		            ->setSortOrder($row[5])
		            ->setWidgetParameters($parameters)
		            ->setPageGroups($pageGroups);
		        //print_r($widgetInstance->getData());die;
		        $widgetInstance->save();
            }
		}
		fclose($file_handle);
		Mage::getSingleton('core/session')->addSuccess('Widgets were installed');
    }
}

