<?php

class Admin_ConfiguracaoController extends DMG_Controller {
	public function aniversarioDataAction () {
		if ($this->getRequest()->isPost()) {
			try {
				$titulo_db = Doctrine::getTable('Parametro')->find(1);
				$mensagem_db = Doctrine::getTable('Parametro')->find(2);
				$titulo_db->valor = $this->getRequest()->getParam('titulo');
				$titulo_db->save();
				$mensagem_db->valor = $this->getRequest()->getParam('mensagem');
				$mensagem_db->save();
				echo $this->_helper->json(array('success' => true));
			} catch (Exception $e) {
				echo $this->_helper->json(array('success' => false));
			}
		} else {
			echo $this->_helper->json(array('success' => true, 'data' => array(
				'titulo' => DMG_Config::_(1),
				'mensagem' => DMG_Config::_(2)
			)));
		}
		echo $this->_helper->json(array('success' => false));
	}
	public function aniversarioAction () {
		//
	}
	public function alteraremailAction () {
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'configuracao', 'action' => 'alteraremail')));
		$form->setMethod('post');
		$form->setName('form_zend');
		
		$cadastrar = new Zend_Form_Element_Hidden('cadastrar');
		$confirmaAut = new Zend_Form_Element_Hidden('confirmaAut');
		
		$cpf = new Zend_Form_Element_Text('cpf');
		$cpf->setLabel('CPF');
		$cpf->setValue($this->getRequest()->getParam('cpf'));
		$cpf->setAttrib('onChange','javascript: submit()');
		
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel('Novo Email');
		$email->setValue($this->getRequest()->getParam('email'));
		
		$emailconf = new Zend_Form_Element_Text('emailconf');
		$emailconf->setLabel('Confirmar Novo Email');
		$emailconf->setValue($this->getRequest()->getParam('emailconf'));
		
		$send = new Zend_Form_Element_Button('send');
		$send->setLabel('Alterar');
		$send->setAttrib('onClick','javascript: fcn_alteraEmail()');
		
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost();
			try {
				$querySelect = Doctrine_Query::create()
				->select('c.email')
				->addSelect('c.nome')
				->addSelect('c.id')
				->from('ServidorPublico c')
				->addWhere('c.cpf = \''.substr(preg_replace('([^0-9])', '', $data['cpf']), 0, 11).'\'');
				
				$emailServidor = "";
				$nomeServidor = "";
				$idServidor = "";
				$mailAlterado = "";
				foreach ($querySelect->execute() as $k) {
					$idServidor = $k->id;
					$emailServidor = $k->email;
					$nomeServidor = $k->nome;
				}
				if($data['cadastrar']==1){
					if ($data['email'] != $data['emailconf']){
						$this->view->erroInc = '<script language="javascript"> alert("Emails diferentes!"); </script>';
					}else{
						if(trim($data['email'])==""){
							$this->view->erroInc = '<script language="javascript"> alert("Campo email vazio");</script>';
						}else{
							if (!$idServidor) {
								$this->view->erroInc = '<script language="javascript"> alert("CPF inexistente"); </script>';
							}else{
								$mailAlterado = 1;
								$this->view->erroInc = '';
								if($data['confirmaAut']==1){
									$this->view->confAutEmail = '';
									
									$query = Doctrine_Query::create()
									->update('ServidorPublico')
									->set('email', '\''.$data['email'].'\'')
									->where('cpf = \''.$data['cpf'].'\'');
									$query->execute();
									
									$this->view->erroInc = '<script language="javascript"> alert("Email Alterado"); </script>';
									
								}else{
									$this->view->confAutEmail = 'Deseja realmente alterar o Email: <b>'.$emailServidor.'</b> Para: <b>'.$data['email'].'</b> ?';
									
									$confAlter = new Zend_Form_Element_Button('confAlter');
									$confAlter->setLabel('Confirmar');
									$confAlter->setAttrib('onClick','javascript: fcn_confirmaEmail()');
																			
									$cancAlter = new Zend_Form_Element_Button('cancAlter');
									$cancAlter->setLabel('Cancelar');
									$cancAlter->setAttrib('onClick','javascript: fcn_cancelaEmail()');
																	
									$form->addElement($confAlter);
									$form->addElement($cancAlter);
								}
							}
						}
					}
				}
				if($mailAlterado != 1){
					if (!$idServidor) {
						$this->view->servAtualizar = '<br><br><b>Nenhum servidor encontrado com esse CPF</b>';
					}else{
						$this->view->servAtualizar = '<br><br><b>Nome do Servidor: </b>'.$nomeServidor.'<br><b>E-mail atual: </b>'.$emailServidor;
					}
				}
			}catch (Exception $e) {
				$this->view->erroInc = '<script language="javascript"> alert("Problema ao alterar o Email!"); </script>';
			}
			
		}
		$form->addElement($cadastrar);
		$form->addElement($confirmaAut);
		$form->addElement($cpf);
		$form->addElement($email);
		$form->addElement($emailconf);
		$form->addElement($send);
		$this->view->form = $form;
	}
}

?>