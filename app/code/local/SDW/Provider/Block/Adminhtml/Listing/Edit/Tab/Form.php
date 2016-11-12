<?php
class SDW_Provider_Block_Adminhtml_Listing_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('provider_form',array('legend'=>'Information'));
		
		$fieldset->addField('code', 'text',array(
											'label' => 'Code Fournisseur',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'code',
										));
		$fieldset->addField('sociale', 'text',array(
											'label' => 'Raison Sociale',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'sociale',
										));
		$fieldset->addField('adresse1', 'text',array(
											'label' => 'Adresse 1',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'adresse1',
										));
		$fieldset->addField('adresse2', 'text',array(
											'label' => 'Adresse 2',
											'required' => false,
											'name' => 'adresse2',
										));
		$fieldset->addField('adresse3', 'text',array(
											'label' => 'Adresse 3',
											'required' => false,
											'name' => 'adresse3',
										));
		$fieldset->addField('cp', 'text',array(
											'label' => 'Code Postal',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'cp',
										));
		$fieldset->addField('ville', 'text',array(
											'label' => 'Ville',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'ville',
										));
		$fieldset->addField('pays', 'select',array(
											'label' => 'Pays',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'pays',
											'values'=> Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(),
										));				
		$fieldset->addField('contact1', 'text',array(
											'label' => 'Contact 1',
											'required' => false,
											'name' => 'contact1',
										));
		$fieldset->addField('contact2', 'text',array(
											'label' => 'Contact 2',
											'required' => false,
											'name' => 'contact2',
										));
		$fieldset->addField('tel1', 'text',array(
											'label' => 'Téléphone 1',
											'required' => false,
											'name' => 'tel1',
										));
		$fieldset->addField('tel2', 'text',array(
											'label' => 'Téléphone 2',
											'required' => false,
											'name' => 'tel2',
										));
		$fieldset->addField('transport', 'text',array(
											'label' => 'Taux de transport',
											'required' => false,
											'name' => 'transport',
										));
		$fieldset->addField('delai', 'text',array(
											'label' => 'Délai de livraison (semaine)',
											'required' => false,
											'name' => 'delai',
										));
		$fieldset->addField('monnaie', 'select',array(
											'label' => 'Monnaie',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'monnaie',
											'values'=>array("EUR"=>"EUR","USD"=>"USD")
										));
		$fieldset->addField('email', 'text',array(
											'label' => 'Email',
											'class' => 'required-entry',
											'required' => true,
											'name' => 'email',
										));
		$fieldset->addField('commentaires', 'textarea',array(
											'label' => 'Commentaires',
											'required' => false,
											'name' => 'commentaires',
										));
										
		if ( Mage::registry('provider_data') )
		{
			$form->setValues(Mage::registry('provider_data')->getData());
		}
		return parent::_prepareForm();
	}
}
