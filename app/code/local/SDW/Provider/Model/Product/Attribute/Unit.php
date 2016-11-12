<?php
class SDW_Provider_Model_Product_Attribute_Unit extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	private function orderbyCode($a,$b){
		return strcmp($a["label"], $b["label"]);
	}

	public function getAllOptions()
	{
		$model = Mage::getModel('provider/provider');
		$collection = $model->getCollection();
		$providerArr = array();
		$providerArr[] = array('value' => '0','label' => '');
		foreach($collection as $provider)
		{
			$data = $provider->getData();
			
			$providerArr[] = array(
				'value' => $data['id_provider'],
				'label' => $data['code'].' - '.$data['sociale'],
			);
		}
		usort($providerArr, array('SDW_Provider_Model_Product_Attribute_Unit','orderbyCode'));
		if (!$this->_options) {
			$this->_options = $providerArr;
		}
		return $this->_options;
	}
}
?> 