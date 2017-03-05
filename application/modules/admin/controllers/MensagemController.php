<?php

class Admin_MensagemController extends DMG_Controller {
	public function indexAction () {
		$query = Doctrine_Query::create()
			->from('Mensagem m')
			->orderBy('m.id DESC')
		;
		$paginator = new Zend_Paginator(new DMG_DoctrinePaginator($query));
		$paginator->setItemCountPerPage(10);
		$paginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
		$this->view->paginator = $paginator;
	}
	public function listAction () {
		$limit = (int) $this->getRequest()->getParam('limit');
		$offset = (int) $this->getRequest()->getParam('start');
		$query = Doctrine_Query::create()
			->from('Mensagem m')
			->orderBy('m.id DESC')
			->limit($limit)
			->offset($offset)
		;
		$mensagens = array();
		foreach ($query->execute() as $k) {
			$mensagens[] = array(
				'id' => $k->id,
				'usuario' => $k->UsuarioAdmin->nome,
				'titulo' => $k->titulo,
				'mensagem' => $k->mensagem,
				'data_criacao' => $k->data_criacao,
				'data_publicacao' => $k->data_publicacao,
				'data_expiracao' => $k->data_expiracao,
				'fl_ativos' => $k->fl_ativos,
				'fl_inativos' => $k->fl_inativos,
				'fl_publicada' => $k->fl_publicada
			);
		}
		echo $this->_helper->json(array('success' => true, 'total' => $query->count(), 'data' => $mensagens));
	}
	public function deletarAction () {
		try {
			$mensagem = Doctrine::getTable('Mensagem')->find((int) $this->getRequest()->getParam('id'));
			if (!$mensagem) {
				throw new Exception();
			}
			foreach ($mensagem->MensagemServidorLeitura as $k) {
				$k->delete();
			}
			$mensagem->delete();
			if ($mensagem) {
				throw new Exception();
			}
			echo $this->_helper->json(array('success' => true));
		} catch (Exception $e) {
			echo $this->_helper->json(array('success' => false));
		}
		echo $this->_helper->json(array('success' => false));
	}
	public function editarAction () {
		$id = (int) $this->getRequest()->getParam('id');
		
		if ($this->getRequest()->isPost()) {
			if ($id > 0) {
				try {
					$msg = Doctrine::getTable('Mensagem')->find($id);
					if (!$msg) {
						throw new Exception();
					}
 					$msg->titulo = $this->getRequest()->getParam('titulo');
					$msg->mensagem = $this->getRequest()->getParam('mensagem');
					$msg->data_publicacao = $this->getRequest()->getParam('data_publicacao');
					$msg->data_expiracao = (strlen($this->getRequest()->getParam('data_expiracao')) ? $this->getRequest()->getParam('data_expiracao') : null);
					$msg->fl_publicada = (boolean) $this->getRequest()->getParam('fl_publicada');
					$msg->fl_ativos = (boolean) $this->getRequest()->getParam('fl_ativos');
					$msg->fl_inativos = (boolean) $this->getRequest()->getParam('fl_inativos');
					$msg->save();
					echo $this->_helper->json(array('success' => true));
				} catch (Exception $e) {
					echo $this->_helper->json(array('success' => false,"erro"=>$e->getMessage()));
				}
			} else {
				try {
					$msg = new Mensagem();
					$msg->usuario_admin_id = Zend_Registry::get('auth')->getIdentity()->id;
 					$msg->titulo = $this->getRequest()->getParam('titulo');
					$msg->mensagem = $this->getRequest()->getParam('mensagem');
					$msg->data_criacao = date('Y-m-d H:i:s');
					$msg->data_publicacao = $this->getRequest()->getParam('data_publicacao');
					$msg->data_expiracao = (strlen($this->getRequest()->getParam('data_expiracao')) ? $this->getRequest()->getParam('data_expiracao') : null);
					$msg->fl_publicada = (boolean) $this->getRequest()->getParam('fl_publicada');
					$msg->fl_ativos = (boolean) $this->getRequest()->getParam('fl_ativos');
					$msg->fl_inativos = (boolean) $this->getRequest()->getParam('fl_inativos');
					$msg->save();
					echo $this->_helper->json(array('success' => true));
				} catch (Exception $e) {
					echo $this->_helper->json(array('success' => false));
				}
			}
			echo $this->_helper->json(array('success' => false));
		} else {
			$mensagem = Doctrine::getTable('Mensagem')->find($id);
			if ($mensagem) {
				echo $this->_helper->json(array('success' => true, 'data' => array(
					'id' => $mensagem->id,
					'titulo' => $mensagem->titulo,
					'mensagem' => $mensagem->mensagem,
					'data_publicacao' => $mensagem->data_publicacao,
					'data_expiracao' => $mensagem->data_expiracao,
					'fl_publicada' => $mensagem->fl_publicada,
					'fl_ativos' => $mensagem->fl_ativos,
					'fl_inativos' => $mensagem->fl_inativos
				)));
			} else {
				echo $this->_helper->json(array('success' => false));
			}
		}
	}
	public function publicarAction () {
		$id = (int) $this->getRequest()->getParam('id');
		try {
			$mensagem = Doctrine::getTable('Mensagem')->find($id);
			$mensagem->fl_publicada = true;
			$mensagem->data_publicacao = date('Y-m-d H:i:s');
			$mensagem->save();
			$this->getHelper('FlashMessenger')->addMessage('Mensagem publicada com sucesso');
		} catch (Exception $e) {
			$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao publicar a mensagem');
		}
		$this->_helper->redirector('index', 'mensagem');
	}
	public function despublicarAction () {
		$id = (int) $this->getRequest()->getParam('id');
		try {
			$mensagem = Doctrine::getTable('Mensagem')->find($id);
			$mensagem->fl_publicada = false;
			$mensagem->save();
			$this->getHelper('FlashMessenger')->addMessage('Mensagem despublicada com sucesso');
		} catch (Exception $e) {
			$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao despublicar a mensagem');
		}
		$this->_helper->redirector('index', 'mensagem');
	}	
}

?>