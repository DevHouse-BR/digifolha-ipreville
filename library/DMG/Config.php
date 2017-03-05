<?php

class DMG_Config {
	protected function __construct () {
		throw new Exception("This class can't be instanciated.");
	}
	public static function _ ($id) {
		static $configs = array();
		if (empty($configs)) {
			foreach (Doctrine::getTable('Parametro')->findAll() as $k) {
				$configs[$k->id] = $k->valor;
			}
		}
		return $configs[$id];
	}
}