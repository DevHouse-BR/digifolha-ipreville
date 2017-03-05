<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	public function _initDMG () {
		Zend_Loader_Autoloader::getInstance()->registerNamespace('DMG');
		define('HASH_SENHA', '/29ja#9@G()H1-03gjas\\a');
	}
	public function _initDoctrine () {
		require_once 'Doctrine.php';
		$loader = Zend_Loader_Autoloader::getInstance();
		$loader->pushAutoloader(array('Doctrine', 'autoload'));
		$doctrineConfig = $this->getOption('doctrine');
		$manager = Doctrine_Manager::getInstance();
		$manager->setAttribute(Doctrine::ATTR_USE_DQL_CALLBACKS, true);
		$manager->setAttribute(Doctrine::ATTR_MODEL_LOADING, Doctrine::MODEL_LOADING_CONSERVATIVE);
		Doctrine::loadModels($doctrineConfig['models_path']);
		foreach (glob($doctrineConfig['models_path'] . '/generated/*.php') as $k) {
			require_once($k);
		}
		foreach (glob($doctrineConfig['models_path'] . '/*.php') as $k) {
			require_once($k);
		}
		$manager->setCollate('utf8_unicode_ci');
		$manager->setCharset('utf8');
		$manager->openConnection($doctrineConfig['connection_string']);
		return $manager;
	}
	public function _initPlugins () {
		$this->getPluginResource('frontcontroller')->getFrontController()->registerPlugin(new DMG_Auth_Plugin());
	}
}

