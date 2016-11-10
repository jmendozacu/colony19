<?php
class Codazon_CodazonInstaller_Adminhtml_CodazoninstallerbackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Backend Page Title"));
	   $this->renderLayout();
    }
    public function registerInstalledTheme($theme){
    	$log = Mage::getBaseDir().'/var/.installedtheme';
    	$file = fopen($log, 'a');
    	fwrite($file, $theme."\n");
    	fclose($file);
    }
    
    public function importAction()
    {
    	$theme = $this->getRequest()->getParam('theme');
    	$path = Mage::getBaseDir('code').'/local/Codazon/CodazonInstaller/import/'.$theme;
    	//import cms block
    	$blockInstaller = Mage::getModel('codazoninstaller/block');
    	$widgetInstaller = Mage::getModel('codazoninstaller/widget');
    	$pageInstaller = Mage::getModel('codazoninstaller/page');
    	$slideshowInstaller = Mage::getModel('codazoninstaller/slideshow');
    	$blogPostInstaller = Mage::getModel('codazoninstaller/blog_post');
    	$blogCatInstaller = Mage::getModel('codazoninstaller/blog_cat');
    	$permissionBlockInstaller = Mage::getModel('codazoninstaller/permission_block');
    	
    	$pageInstaller->install($path.'/pages.csv');
    	$blockInstaller->install($path.'/blocks.csv');
    	$slideshowInstaller->install($path.'/slideshows.csv');
    	$widgetInstaller->install($path.'/widgets.csv');
    	$permissionBlockInstaller->install($path.'/permission_blocks.csv');
    	$blogCatInstaller->install($path.'/blog_cats.csv');
    	$blogPostInstaller->install($path.'/blog_posts.csv');
    	
    	$this->registerInstalledTheme($theme);
    	$this->_redirect('*/*/index');
    }
    
    public function exportAction()
    {
    	$pageInstaller = Mage::getModel('codazoninstaller/page');
    	$blockInstaller = Mage::getModel('codazoninstaller/block');
    	$widgetInstaller = Mage::getModel('codazoninstaller/widget');
    	$slideshowInstaller = Mage::getModel('codazoninstaller/slideshow');
    	$blogPostInstaller = Mage::getModel('codazoninstaller/blog_post');
    	$blogCatInstaller = Mage::getModel('codazoninstaller/blog_cat');
    	$permissionBlockInstaller = Mage::getModel('codazoninstaller/permission_block');
    	
    	$all = Mage::getSingleton('core/design_source_design')->getAllOptions();
		$themes = array();
		$result = array();
		foreach($all as $design){
			if($design['label'] == 'codazon_fastest'){
				$themes = $design['value'];
				break;
			}
		}
		foreach($themes as $theme){
			$pageInstaller->export($theme['value']);
			$blockInstaller->export($theme['value']);
			$widgetInstaller->export($theme['value']);
			$slideshowInstaller->export($theme['value']);
			$blogPostInstaller->export($theme['value']);
			$blogCatInstaller->export($theme['value']);
			$permissionBlockInstaller->export($theme['value']);
		}
    	
    	Mage::getSingleton('core/session')->addSuccess('Exported');
    	$this->_redirect('*/*/index');
    }
}
