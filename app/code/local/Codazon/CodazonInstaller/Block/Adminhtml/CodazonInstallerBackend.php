<?php  

class Codazon_CodazonInstaller_Block_Adminhtml_CodazonInstallerBackend extends Mage_Adminhtml_Block_Template {
	public function getDesigns(){
		$all = Mage::getSingleton('core/design_source_design')->getAllOptions();
		$themes = array();
		$result = array();
		foreach($all as $design){
			if($design['label'] == 'codazon_fastest'){
				$themes = $design['value'];
				break;
			}
		}
		$installed = '';
		$logFile = Mage::getBaseDir().'/var/.installedtheme';
		if(file_exists($logFile)){
			$installed = file_get_contents($logFile);
		}
		foreach($themes as $theme){
			$code = str_replace('/', '_', $theme['value']);
			$code = str_replace('codazon_','',$code);
			$theme['value'] = $code;
			
			if (strpos($installed, $theme['value']) !== false) {
				$theme['status'] = 1;
			}else{
				$theme['status'] = 0;
			}
			$result[] = $theme;
			
		}
		return $result;
	}
}
