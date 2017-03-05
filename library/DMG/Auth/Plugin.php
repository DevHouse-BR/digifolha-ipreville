<?php

class DMG_Auth_Plugin extends Zend_Controller_Plugin_Abstract {
	public function routeShutdown(Zend_Controller_Request_Abstract $request) {
		$module = $this->getRequest()->getModuleName();
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		$auth = Zend_Auth::getInstance();	
		if ($module == 'admin') {
			$auth->setStorage(new Zend_Auth_Storage_Session('ipreville_admin'));
		} else if ($module == 'ente') {
			$auth->setStorage(new Zend_Auth_Storage_Session('ipreville_ente'));
		} else {
			$auth->setStorage(new Zend_Auth_Storage_Session('ipreville_default'));
		}
		Zend_Registry::set('auth', $auth);
		if (!$auth->hasIdentity()) {
			if (!($module == 'default' && $controller == 'index' && $action == 'recuperar-senha') && !($module == 'default' && $controller == 'index' && $action == 'registro') && !($module == 'default' && $controller == 'index' && $action == 'contato') && !($module == 'default' && $controller == 'index' && $action == 'contatoLogado') && !($controller == 'index' && $action == 'login') && !($controller == 'error' & $action == 'login') ) {
				$request->setModuleName($module)->setControllerName('error')->setActionName('login')->setDispatched(false);
			}
		}
	}
}