<?php

class DMG_Controller extends Zend_Controller_Action {
	public function init () {
		$this->_helper->layout->getLayoutInstance()->messages = $this->getHelper('FlashMessenger')->getMessages();
		$this->view->messages = $this->getHelper('FlashMessenger')->getMessages();
		$this->view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=utf-8')->appendHttpEquiv('Content-Language', 'pt-BR');
		$this->view->headLink()->appendStylesheet($this->view->baseUrl() . '/css/' . $this->getRequest()->getModuleName() . '.css');
		// b64_hmac_md5
	}
}
?>