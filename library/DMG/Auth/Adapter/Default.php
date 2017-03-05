<?php

class DMG_Auth_Adapter_Default implements Zend_Auth_Adapter_Interface {
	private $_username;
	private $_password;

	public function __construct($username, $password) {
		$this->_username = $username;
		$this->_password = $password;
	}
	public function authenticate() {
		try {
			$user = Doctrine_Query::create()
				->from('ServidorPublico s')
				->where('cpf = ?', $this->_username)
				->addWhere('senha = ?', crypt($this->_password, HASH_SENHA))
				->fetchOne()
			;
			if (!$user) {
				return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, array('Usuário e/ou senha inválidos'));
			} else {
				return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user, array());
			}
		} catch(Exception $e) {
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null, array('Usuário e/ou senha inválidos'));
		}
	}
}