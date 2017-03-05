<?php

class DMG_Auth_Adapter_Admin implements Zend_Auth_Adapter_Interface {
	private $_username;
	private $_password;

	public function __construct($username, $password) {
		$this->_username = $username;
		$this->_password = $password;
	}
	public function authenticate() {
		try {
			$user = Doctrine_Query::create()
				->from('UsuarioAdmin')
				->where('login = ?', $this->_username)
				->addWhere('senha = ?', crypt($this->_password, HASH_SENHA))
				->fetchOne();
			if (!$user) {
				DMG_Log::_(6, array('usuario' => $this->_username, 'senha' => $this->_password));
				return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array('Usuário e/ou senha inválidos'));
			} else {
				DMG_Log::_(5);
				return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user, array());
			}
		} catch(Exception $e) {
			DMG_LOG::_(6, array('usuario' => $this->_username, 'senha' => $this->_password));
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null, array('Usuário e/ou senha inválidos'));
		}
	}
}