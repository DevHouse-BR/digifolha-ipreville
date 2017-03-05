<?php

class Admin_IndexController extends DMG_Controller
{
    public function indexAction() {
		//
    }
	public function logListAction () {
		$limit = (int) $this->getRequest()->getParam('limit');
		$offset = (int) $this->getRequest()->getParam('start');
		$query = Doctrine_Query::create()
			->from('Log')
			->orderBy('data DESC')
			->limit($limit)
			->offset($offset)
		;
		$log = array();
		foreach ($query->execute() as $k) {
			$log[] = array(
				'id' => $k->id,
				'data' => $k->data,
				'ente' => ($k->ente_id ? $k->Ente->nome : ''),
				'postagem' => ($k->postagem_id ? $k->Postagem->periodo : ''),
				'ip' => (string) $k->ip,
				'tipo' => $k->tipo,
				'mensagem' => nl2br($k->mensagem),
				'status' => $k->status
			);
		}
		echo $this->_helper->json(array('success' => true, 'total' => $query->count(), 'data' => $log));
	}
	public function loginAction () {
		$this->_helper->layout->disableLayout();
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'index', 'action' => 'login')));
		$form->setMethod('post');
		
		$user = new Zend_Form_Element_Text('user');
		$user->setLabel('Usuário');
		
		$pass = new Zend_Form_Element_Password('pass');
		$pass->setLabel('Senha');
		
		$send = new Zend_Form_Element_Submit('send');
		$send->setLabel('Entrar');
		
		$form->addElement($user);
		$form->addElement($pass);
		$form->addElement($send);
		
		if ($this->getRequest()->isPost()) {
			$auth = Zend_Registry::get('auth');
			$result = $auth->authenticate(new DMG_Auth_Adapter_Admin($this->getRequest()->getParam('user'), $this->getRequest()->getParam('pass')))->isValid();
			Zend_Registry::set('auth', $auth);
			if (!$result) {
				$this->getHelper('FlashMessenger')->addMessage('Usuário e/ou senha inválidos');
				$this->_helper->redirector('login', 'index');
			} else {
				$this->_helper->redirector('index', 'index');
			}
		}
		
		$this->view->form = $form;
	}
	public function logoutAction () {
		$auth = Zend_Registry::get('auth');
		$auth->clearIdentity();
		$this->_helper->redirector('login', 'index');
	}
	public function alterarSenhaAction () {
		if ($this->getRequest()->isPost()) {
			try {
				$user = Doctrine::getTable('UsuarioAdmin')->find(Zend_Registry::get('auth')->getIdentity()->id);
				if (!$user) {
					throw new Exception();
				}
				$senha_antiga = crypt($this->getRequest()->getParam('senha_antiga'), HASH_SENHA);
				$senha_nova1 = crypt($this->getRequest()->getParam('senha_nova1'), HASH_SENHA);
				$senha_nova2 = crypt($this->getRequest()->getParam('senha_nova2'), HASH_SENHA);
				if ($senha_nova1 != $senha_nova2) {
					throw new Exception('As senhas precisam ser iguais');
				}
				if ($senha_antiga != $user->senha) {
					throw new Exception('A senha atual não confere');
				}
				$user->senha = $senha_nova2;
				$user->save();
				echo $this->_helper->json(array('success' => true));
			} catch (Exception $e) {
				echo $this->_helper->json(array('success' => false, 'error' => $e->getMessage()));
			}
		}		
		echo $this->_helper->json(array('success' => false));
	}
	public function senhaAction () {
		//
	}
	public function arquivoAction () {
		//
	}
	public function logAction () {
		//
	}
	public function arquivoListAction () {
		$limit = (int) $this->getRequest()->getParam('limit');
		$offset = (int) $this->getRequest()->getParam('start');
		$query = Doctrine_Query::create()
			->addSelect('p.id')
			->addSelect('p.periodo')
			->addSelect('p.dt_postagem')
			->addSelect('p.fl_publicado')
			->from('Postagem p')
			->orderBy('p.dt_postagem DESC')
			->limit($limit)
			->offset($offset)
		;
		$postagens = array();
		foreach ($query->execute() as $postagem) {
			$postagens[] = array(
				'id' => $postagem->id,
				'periodo' => $postagem->periodo,
				'dt_postagem' => $postagem->dt_postagem,
				'fl_publicado' => $postagem->fl_publicado,
				'ente' => $postagem->Ente->nome
			);
		}
		echo $this->_helper->json(array('success' => true, 'total' => $query->count(), 'data' => $postagens));
	}
	public function postagemPublicarAction () {
		$id = $this->getRequest()->getParam('id');
		try {
			foreach ($id as $k) {
				$postagem = Doctrine::getTable('Postagem')->find((int )$k);
				$postagem->fl_publicado = true;
				$postagem->save();
			}
			echo $this->_helper->json(array('success' => true));
		} catch (Exception $e) {
			echo $this->_helper->json(array('success' => false));
		}
		echo $this->_helper->json(array('success' => false));
	}
	public function postagemDespublicarAction () {
		$id = $this->getRequest()->getParam('id');
		try {
			foreach ($id as $k) {
				$postagem = Doctrine::getTable('Postagem')->find((int) $k);
				$postagem->fl_publicado = false;
				$postagem->save();
			}
			echo $this->_helper->json(array('success' => true));
		} catch (Exception $e) {
			echo $this->_helper->json(array('success' => false));
		}
		echo $this->_helper->json(array('success' => false));
	}
}