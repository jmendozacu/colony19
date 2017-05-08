<?php
class Agentom_Video_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Packs Vidéos"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("packs vidéos", array(
                "label" => $this->__("Packs Vidéos"),
                "title" => $this->__("Packs Vidéos")
		   ));

      $this->renderLayout(); 
	  
    }
}