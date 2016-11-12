<?php
	
class SDW_Provider_Block_Adminhtml_Listing_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
   public function __construct()
   {	
        parent::__construct();
        $this->_objectId = 'id';
        //vous remarquerez qu’on lui assigne le même blockGroup que le Grid Container
        $this->_blockGroup = 'provider';
        //et le meme controlleur
        $this->_controller = 'adminhtml_listing';
        //on definit les labels pour les boutons save et les boutons delete
        $this->_updateButton('save', 'label','Sauvegarder le fournisseur');
        $this->_updateButton('delete', 'label', 'Supprimer le fournisseur');
    }
      
	/* Ici,  on regarde si on a transmit un objet au formulaire, afin de mettre le bon texte dans le  header (Editer ou Ajouter) */
    public function getHeaderText()
    {
        if( Mage::registry('provider_data') && Mage::registry('provider_data')->getId())
         {
              return 'Editer le fournisseur '.$this->htmlEscape(
              Mage::registry('provider_data')->getTitle()).'<br />';
         }
         else
         {
             return 'Ajouter un fournisseur';
         }
    }

}
