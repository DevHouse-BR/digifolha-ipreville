<?php

class Admin_EnteController extends DMG_Controller
{
	public function listAction () {
		$limit = (int) $this->getRequest()->getParam('limit');
		$offset = (int) $this->getRequest()->getParam('start');
		$query = Doctrine_Query::create()
			->from('Ente e')
			->limit($limit)
			->offset($offset)
			->orderBy('e.id DESC')
		;
		$entes = array();
		foreach ($query->execute() as $k) {
			$entes[] = array(
				'id' => $k->id,
				'nome' => $k->nome,
				'email' => $k->email,
				'login' => $k->login
			);
		}
		echo $this->_helper->json(array('success' => true, 'total' => $query->count(), 'data' => $entes));
	}
	public function senhaAction () {
		$id = (int) $this->getRequest()->getParam('id');
		if ($this->getRequest()->isPost()) {
			try {
				$ente = Doctrine::getTable('Ente')->find($id);
				if (!$ente) {
					throw new Exception();
				}
				$senha_nova1 = crypt($this->getRequest()->getParam('senha_nova1'), HASH_SENHA);
				$senha_nova2 = crypt($this->getRequest()->getParam('senha_nova2'), HASH_SENHA);
				if ($senha_nova1 == $senha_nova2) {
					$ente->senha = $senha_nova1;
					$ente->save();
					echo $this->_helper->json(array('success' => true));
				} else {
					echo $this->_helper->json(array('success' => false));
				}
			} catch (Exception $e) {
				echo $this->_helper->json(array('success' => false));
			}
		}
		echo $this->_helper->json(array('success' => false));
	}
	public function indexAction () {
		//
	}
}