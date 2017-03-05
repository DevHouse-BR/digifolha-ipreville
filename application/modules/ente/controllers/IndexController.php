<?php

class Ente_IndexController extends DMG_Controller
{
    public function indexAction() {
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'ente', 'controller' => 'index', 'action' => 'index'), null, true));
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		
		$periodo = new Zend_Form_Element_Text('periodo');
		$periodo->setLabel('Período')->setRequired(true);
		
		$arquivo = new Zend_Form_Element_File('arquivo');
		$arquivo->setRequired(true);
		$arquivo->setLabel('Arquivo');
		$arquivo->addValidator('Count', false, 1);
		$arquivo->addValidator('Size', false, 20480000);
		$arquivo->addValidator('Extension', false, 'txt');
		
		$send = new Zend_Form_Element_Submit('send');
		$send->setLabel('Enviar');
		
		$form->addElements(array($periodo, $arquivo, $send));

		if ($this->getRequest()->isPost()) {
			$dados = $this->getRequest()->getPost();
			if ($form->isValid($dados)) {
				try {
					Doctrine_Manager::getInstance()->getCurrentConnection()->beginTransaction();
					$arquivo = $form->arquivo->getTransferAdapter();
					$arquivo->receive();
					$arquivo = $arquivo->getFileInfo();
					$arquivo = $arquivo['arquivo'];
					do {
						$tmpnm = md5(time() + microtime()) . '.txt';
					} while (file_exists(APPLICATION_PATH . '/data/' . $tmpnm));
					if ($arquivo['error'] == 0) {
						$postagem = new Postagem();
						$postagem->ente_id = Zend_Registry::get('auth')->getIdentity()->id;
						$postagem->periodo = $dados['periodo'];
						$postagem->arquivo_postado = $tmpnm;
						$postagem->dt_postagem = date('Y-m-d H:i:s');
						$postagem->ip_origem = $_SERVER['REMOTE_ADDR'];
						$postagem->fl_publicado = true;
						$postagem->fl_processado = false;
						$postagem->save();
						$move = new Zend_Filter_File_Rename(array('target' => APPLICATION_PATH . '/../data//' . $tmpnm));
						$move->filter($arquivo['tmp_name']);
						Doctrine_Manager::getInstance()->getCurrentConnection()->commit();
						DMG_Log::_(1, array('periodo' => $postagem->periodo), $postagem->ente_id, $postagem->id);
						$this->getHelper('FlashMessenger')->addMessage('O arquivo foi enviado com sucesso');
						$this->_helper->redirector('index', 'index');
					}
				} catch (Exception $e) {
					Doctrine_Manager::getInstance()->getCurrentConnection()->rollback();
					$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao enviar o arquivo');
					$this->_helper->redirector('index', 'index');
				}
				$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao enviar o arquivo');
				$this->_helper->redirector('index', 'index');
			} else {
				$errors = $form->getErrors();
				if (isset($errors['arquivo'])) {
					foreach ($errors['arquivo'] as $k) {
						switch ($k) {
							case 'fileExtensionFalse':
								DMG_Log::_(2, array('periodo' => $dados['periodo'], 'descricao' => 'Arquivo de extensão inválida enviado'), Zend_Registry::get('auth')->getIdentity()->id);
							break;
							case 'fileUploadErrorIniSize':
								DMG_Log::_(2, array('periodo' => $dados['periodo'], 'descricao' => 'Arquivo de tamanho excedido enviado'), Zend_Registry::get('auth')->getIdentity()->id);
							break;
						}
					}
				}
			}
		}
		
		$this->view->form = $form;
    }
	public function loginAction () {
		$this->_helper->layout->disableLayout();
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'ente', 'controller' => 'index', 'action' => 'login')));
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
			$result = $auth->authenticate(new DMG_Auth_Adapter_Ente($this->getRequest()->getParam('user'), $this->getRequest()->getParam('pass')))->isValid();
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
	public function listAction () {
		$limit = (int) $this->getRequest()->getParam('limit');
		$offset = (int) $this->getRequest()->getParam('start');
		$query = Doctrine_Query::create()
			->addSelect('p.id')
			->addSelect('p.periodo')
			->addSelect('p.dt_postagem')
			->addSelect('p.fl_publicado')
			->addSelect('p.fl_processado')
			->from('Postagem p')
			->addWhere('p.ente_id = ?', Zend_Registry::get('auth')->getIdentity()->id)
			->orderBy('p.id DESC')
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
				'fl_processado' => $postagem->fl_processado
			);
		}
		echo $this->_helper->json(array('success' => true, 'total' => $query->count(), 'data' => $postagens));
	}
}