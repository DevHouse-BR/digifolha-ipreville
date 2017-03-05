<?php

class IndexController extends DMG_Controller
{
	public function marcarLidaAction () {
		$id = (int) $this->getRequest()->getParam('id');
		try {
			$lida = new MensagemServidorLeitura();
			$lida->mensagem_id = $id;
			$lida->servidor_publico_id = Zend_Registry::get('auth')->getIdentity()->id;
			$lida->save();
			echo $this->_helper->json(array('success' => true, 'mensagem' => 'Mensagem marcada como lida'));
		} catch (Exception $e) {
			echo $this->_helper->json(array('success' => false, 'mensagem' => 'Houve um erro ao marcar a mensagem como lida'));
		}
	}
	public function marcarNaoLidaAction () {
		$id = (int) $this->getRequest()->getParam('id');
		try {
			Doctrine::getTable('MensagemServidorLeitura')->findByMensagemIdAndServidorPublicoId($id, Zend_Registry::get('auth')->getIdentity()->id)->delete();
			echo $this->_helper->json(array('success' => true, 'mensagem' => 'Mensagem marcada como não lida'));
		} catch (Exception $e) {
			echo $this->_helper->json(array('success' => false, 'mensagem' => 'Houve um erro ao marcar a mensagem como não lida'));
		}
		$this->_helper->redirector('index', 'mensagem');
	}
    public function indexAction() {
		$data_nascimento = Zend_Registry::get('auth')->getIdentity()->data_nascimento;
		if ($data_nascimento) {
			$data = new Zend_Date($data_nascimento);
			if ($data->get(Zend_Date::MONTH) == date('m')) {
				$this->view->aniversario = true;
			}
		}
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/ipreville.ini', APPLICATION_ENV);
		$query = Doctrine_Query::create()
			->select('c.id')
			->addSelect('c.mes_referencia')
			->addSelect('c.nome_empresa')
			->from('Comprovante c')
			->innerJoin('c.Postagem p')
			->addWhere('p.fl_publicado = ?', true)
			->orderBy('p.dt_postagem DESC')
			->addWhere('c.servidor_publico_id = ?', Zend_Registry::get('auth')->getIdentity()->id)
			->addWhere('c.cpf = ?', Zend_Registry::get('auth')->getIdentity()->cpf)
			->orderBy('c.id DESC')
			->limit($config->comprovantes->limite)
		;
		$comprovantes = array();
		foreach ($query->execute() as $k) {
			$tmp = new StdClass();
			$tmp->id = $k->id;
			$tmp->referencia = $k->mes_referencia;
			$tmp->empresa = $k->nome_empresa;
			$comprovantes[] = $tmp;
		}
		$this->view->comprovantes = $comprovantes;
		
		$query = Doctrine_Query::create()
			->addSelect('m.id')
			->addSelect('m.titulo')
			->addSelect('m.mensagem')
			->addSelect('m.data_publicacao')
			->from('Mensagem m')
			->orderBy('m.data_publicacao DESC')
			->addWhere('m.data_publicacao > ?', date('Y-m-d H:i:s', time()-86400*60))
			->addWhere('m.data_publicacao < ?', date('Y-m-d H:i:s'))
			->addWhere('(m.data_expiracao IS NULL OR m.data_expiracao > ?)', date('Y-m-d H:i:s'))
			->addWhere('m.fl_publicada = ?', true)
			->addSelect('(SELECT COUNT(l.mensagem_id) FROM MensagemServidorLeitura l WHERE l.servidor_publico_id = ' . Zend_Registry::get('auth')->getIdentity()->id . ' AND l.mensagem_id = m.id) AS lida')
			->limit(10)
		;
		if (Zend_Registry::get('auth')->getIdentity()->tipo_servidor == 'A') {
			$query->addWhere('m.fl_ativos = ?', true);
		} else {
			$query->addWhere('m.fl_inativos = ?', true);
		}
		$mensagens = array();
		foreach ($query->execute() as $k) {
			$dt = new Zend_Date($k->data_publicacao);
			$tmp = new StdClass();
			$tmp->id = $k->id;
			$tmp->titulo = $k->titulo;
			$tmp->mensagem = $k->mensagem;
			$tmp->data_publicacao = $dt->toString('dd/MM/YYYY');
			$tmp->lida = (boolean) $k->lida;
			$mensagens[] = $tmp;
		}
		$this->view->mensagens = $mensagens;
    }
	public function visualizarAction () {
		$this->_helper->viewRenderer->setNoRender(); 
		$this->_helper->layout->disableLayout();
		$id = (int) $this->getRequest()->getParam('id');
		$comprovante = Doctrine::getTable('Comprovante')->find($id);
		if (!$comprovante || $comprovante->servidor_publico_id != Zend_Registry::get('auth')->getIdentity()->id) {
			$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao visualizar o comprovante');
			$this->_helper->redirector('index', 'index');
		}
		$this->view->comprovante = $comprovante;
		require_once(realpath(APPLICATION_PATH . '/../library/DOMPDF/dompdf_config.inc.php'));
		spl_autoload_register('DOMPDF_autoload');
		
		switch ($comprovante->Postagem->ente_id) {
			case 1:
				$html = $this->view->render('index/visualizar.prefeitura.phtml');
			break;
			case 2:
				$html = $this->view->render('index/visualizar.ipreville.phtml');
			break;
			case 3:
				$html = $this->view->render('index/visualizar.hospital.phtml');
			break;
		}
		#die($html);
		$html = utf8_decode($html);
		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->set_paper('a4');
		$dompdf->render();
		$dompdf->stream("comprovante.pdf");
	}
	public function logoutAction () {
		$auth = Zend_Registry::get('auth');
		$auth->clearIdentity();
		$this->_helper->redirector('login', 'index');
	}
	public function loginAction () {
		$this->_helper->layout->setLayout('layout-noauth');
		if (Zend_Registry::get('auth')->hasIdentity()) {
			$this->_helper->redirector('index', 'index');
		}

		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'login')));
		$form->setMethod('post');
		
		$cpf = new Zend_Form_Element_Text('cpf');
		$cpf->setLabel('CPF');
		
		$pass = new Zend_Form_Element_Password('pass');
		$pass->setLabel('Senha');
		
		$send = new Zend_Form_Element_Submit('send');
		$send->setLabel('Ok');
		
		$form->addElement($cpf);
		$form->addElement($pass);
		$form->addElement($send);
		
		if ($this->getRequest()->isPost()) {
			$auth = Zend_Registry::get('auth');
			$result = $auth->authenticate(new DMG_Auth_Adapter_Default(substr(preg_replace('([^0-9])', '', $this->getRequest()->getParam('cpf')), 0, 11), $this->getRequest()->getParam('pass')));
			Zend_Registry::set('auth', $auth);
			if (count($result->getMessages())) {
				$this->getHelper('FlashMessenger')->addMessage('Dados incorretos, por favor, tente novamente');
				$this->_helper->redirector('login', 'index');
			} else {
				$this->_helper->redirector('index', 'index');
			}
		}
		
		$this->view->form = $form;
	}
	public function registroAction () {
		$this->_helper->layout->setLayout('layout-noauth');
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'registro')));
		$form->setMethod('post');
		
		$cpf = new Zend_Form_Element_Text('cpf');
		$cpf->setLabel('CPF');
		
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel('E-mail');
		
		$send = new Zend_Form_Element_Submit('send');
		$send->setLabel('Enviar');
		
		$form->addElements(array($cpf, $email, $send));
		
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost();
			if ($form->isValid($data)) {
				try {
					$cpfe = substr(preg_replace('([^0-9])', '', $data['cpf']), 0, 11);
					$emaile = $data['email'];
					$servidor = Doctrine::getTable('ServidorPublico')->findOneByCpf($cpfe);
					if (!$servidor) {
						$this->getHelper('FlashMessenger')->addMessage('Nenhum servidor foi encontrado com estas informações');
						$this->_helper->redirector('registro', 'index');
					}
					if ($servidor->fl_ativo) {
						$this->getHelper('FlashMessenger')->addMessage('Você já ativou sua conta. <a href="' . $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'recuperar-senha'), null, true) . '">Clique aqui</a> se deseja recuperar sua senha');
						$this->_helper->redirector('registro', 'index');
					}
					$emailValidator = new Zend_Validate_EmailAddress();
					if (!$emailValidator->isValid($emaile)) {
						$this->getHelper('FlashMessenger')->addMessage('O endereço de e-mail informado é inválido');
						$this->_helper->redirector('registro', 'index');
					}
					$senha = '';
					$pw = '0123456789abcdefghijklmnopqrstuvwxyz';
					do {
						$senha .= $pw[mt_rand(0, strlen($pw)-1)];
					} while (strlen($senha) < 6);
					$servidor->senha = crypt($senha, HASH_SENHA);
					$servidor->email = $emaile;
					$servidor->fl_ativo = true;
					$servidor->ip_origem = $_SERVER['REMOTE_ADDR'];
					$servidor->dt_ativacao = date('Y-m-d H:i:s');
					$servidor->save();
					$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/ipreville.ini', APPLICATION_ENV);
					$tr = new Zend_Mail_Transport_Smtp($config->smtp->server, $config->smtp->toArray());
					$mail = new Zend_Mail('UTF-8');
					$file = fopen(APPLICATION_PATH . '/configs/email_ativacao.txt', 'r');
					if (!$file) {
						throw new Exception();
					}
					$body = fread($file, filesize(APPLICATION_PATH . '/configs/email_ativacao.txt'));
					fclose($file);
					$body = str_replace(array('{nome}', '{cpf}', '{senha}'), array($servidor->nome, $servidor->cpf, $senha), $body);
					$mail->setBodyHTML($body);
					$mail->setFrom($config->smtp->from, $config->smtp->from_name);
					$mail->addTo($servidor->email, $servidor->nome);
					$mail->setSubject('Digifolha - sua conta foi ativada');
					$mail->setDefaultTransport($tr);
					$mail->send();
					$this->getHelper('FlashMessenger')->addMessage('Sua senha de acesso ao sistema foi enviada para o e-mail informado');
					$this->_helper->redirector('login', 'index');
				} catch (Exception $e) {
					$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao ativar sua conta');
					$this->_helper->redirector('registro', 'index');
				}
			}
		}
		
		$this->view->form = $form;
	}
	public function recuperarSenhaAction () {
		$this->_helper->layout->setLayout('layout-noauth');
		if (Zend_Registry::get('auth')->hasIdentity()) {
			$this->_helper->redirector('index', 'index');
		}
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'recuperar-senha')));
		$form->setMethod('post');
		
		$cpf = new Zend_Form_Element_Text('cpf');
		$cpf->setLabel('CPF');
		
		$send = new Zend_Form_Element_Submit('send');
		$send->setLabel('Enviar');
		
		$form->addElements(array($cpf, $send));
		
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost();
			if ($form->isValid($data)) {
				try {
					$cpfe = substr(preg_replace('([^0-9])', '', $data['cpf']), 0, 11);
					$servidor = Doctrine::getTable('ServidorPublico')->findOneByCpf($cpfe);
					if (!$servidor) {
						$this->getHelper('FlashMessenger')->addMessage('Nenhum servidor foi encontrado com estas informações');
						$this->_helper->redirector('recuperar-senha', 'index');
					}
					if (!$servidor->fl_ativo) {
						$this->getHelper('FlashMessenger')->addMessage('Servidor não ativo no sistema. Por favor, <a href="' . $this->view->url(array('module' => 'default', 'controller' => 'index', 'action')) . '">clique aqui</a>para ativar sua conta');
						$this->_helper->redirector('recuperar-senha', 'index');
					}
					$senha = '';
					$pw = '0123456789abcdefghijklmnopqrstuvwxyz';
					do {
						$senha .= $pw[mt_rand(0, strlen($pw)-1)];
					} while (strlen($senha) < 6);
					$servidor->senha = crypt($senha, HASH_SENHA);
					$servidor->save();
					$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/ipreville.ini', APPLICATION_ENV);
					$tr = new Zend_Mail_Transport_Smtp($config->smtp->server, $config->smtp->toArray());
					$mail = new Zend_Mail('UTF-8');
					$file = fopen(APPLICATION_PATH . '/configs/email_recuperar_senha.txt', 'r');
					if (!$file) {
						throw new Exception();
					}
					$body = fread($file, filesize(APPLICATION_PATH . '/configs/email_recuperar_senha.txt'));
					fclose($file);
					$body = str_replace(array('{nome}', '{cpf}', '{senha}'), array($servidor->nome, $servidor->cpf, $senha), $body);
					$mail->setBodyHtml($body);
					$mail->setFrom($config->smtp->from, $config->smtp->from_name);
					$mail->addTo($servidor->email, $servidor->nome);
					$mail->setSubject('Digifolha - Recuperação de senha');
					$mail->setDefaultTransport($tr);
					$mail->send();
					$this->getHelper('FlashMessenger')->addMessage('Senha enviada para: ' . $servidor->email);
					$this->_helper->redirector('login', 'index');
				} catch (Exception $e) {
					$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao recuperar sua senha');
					$this->_helper->redirector('recuperar-senha', 'index');
				}
			}
		}
		
		$this->view->form = $form;
	}
	
	public function contatoAction () {
		$this->_helper->layout->setLayout('layout-noauth');
		if(isset(Zend_Registry::get('auth')->getIdentity()->cpf)){
			$cpfLogado = Zend_Registry::get('auth')->getIdentity()->cpf;
			$emailLogado = Zend_Registry::get('auth')->getIdentity()->email;
		}else{
			$cpfLogado = " ";
			$emailLogado = " ";
		}
		
		if(isset(Zend_Registry::get('auth')->getIdentity()->nome)){
			$nomeLogado = Zend_Registry::get('auth')->getIdentity()->nome;
		}else{
			$nomeLogado = " ";
		}
		
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'contato')));
		$form->setMethod('post');
				
		$nome = new Zend_Form_Element_Text('nome');
		$nome->setLabel('Nome');
		$nome->setValue($nomeLogado);
			
		$cpf = new Zend_Form_Element_Text('cpf');
		$cpf->setLabel('CPF');
		$cpf->setValue($cpfLogado);
		
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel('Email');
		$email->setValue($emailLogado);
		
		$mensagem = new Zend_Form_Element_Textarea('mensagem');
		$mensagem->setLabel('Mensagem');
		$mensagem->addValidator('NotEmpty');
		$mensagem->addFilter('StripTags');
		$mensagem->setRequired(true);
		$mensagem->addFilter('StringTrim'); 
		$mensagem->setAttrib('rows', 3);
		$mensagem->setAttrib('cols', 30);
		
		$send = new Zend_Form_Element_Submit('send');
		$send->setLabel('Enviar');
		
		$form->addElements(array($nome, $cpf, $email, $mensagem, $send));
		
		
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost();
			if ($form->isValid($data)) {
				try {
					$cpfe = substr(preg_replace('([^0-9])', '', $data['cpf']), 0, 11);
					$lida = new Log();
					$lida->tipo = 'Solicitação de Suporte';
					$lida-> send_mail = "1";
					$lida->data =  date('Y-m-d H:i:s');
					$lida->ip = $_SERVER['REMOTE_ADDR'];
					$lida->status = false;
					$lida->mensagem = '<br/><b>Data/hora:</b> '.date('Y-m-d H:i:s').' <br/><b>Nome do Servidor:</b> ' . $data['nome']. ' <br/><b>CPF:</b> '.$cpfe . '<br/><b>Email:</b> '.$data['email'] . ' <br/><b>Mensagem: </b>'.$data['mensagem'].'<br/>';
					$lida->save();
					$this->getHelper('FlashMessenger')->addMessage('Mensagem Enviada com sucesso');
					$this->_helper->redirector('login', 'index');
				} catch (Exception $e) {
					$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao enviar a mensagem');
					$this->_helper->redirector('login', 'index');
				}
			}
		}
		
				
		$this->view->form = $form;
	}
	
	public function contatologadoAction () {
		$this->_helper->layout->setLayout('layout');
		$valorGet = $this->getRequest()->getParam('id');
		
		if(isset(Zend_Registry::get('auth')->getIdentity()->cpf)){
			$cpfLogado = Zend_Registry::get('auth')->getIdentity()->cpf;
		}else{
			$cpfLogado = " ";
		}
		
		if(isset(Zend_Registry::get('auth')->getIdentity()->nome)){
			$nomeLogado = Zend_Registry::get('auth')->getIdentity()->nome;
		}else{
			$nomeLogado = " ";
		}
		
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'contatoLogado')));
		$form->setMethod('post');
		
		$nome = new Zend_Form_Element_Text('nome');
		$nome->setLabel('Nome');
		$nome->setAttrib('size', 46);
		$nome->setValue($nomeLogado);
		$nome->setAttrib('readonly', 'true');
		
		$cpf = new Zend_Form_Element_Text('cpf');
		$cpf->setLabel('CPF');
		$cpf->setAttrib('size', 46);
		$cpf->setValue($cpfLogado);
		$cpf->setAttrib('readonly', 'true');
		
		$mensagem = new Zend_Form_Element_Textarea('mensagem');
		$mensagem->setLabel('Mensagem');
		$mensagem->addValidator('NotEmpty');
		$mensagem->addFilter('StripTags');
		$mensagem->setRequired(true);
		$mensagem->addFilter('StringTrim'); 
		$mensagem->setAttrib('rows', 4);
		$mensagem->setAttrib('cols', 60);
		
		$send = new Zend_Form_Element_Submit('send');
		$send->setLabel('Enviar');
		
		$form->addElements(array($nome, $cpf, $mensagem, $send));
		
		
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost();
			if ($form->isValid($data)) {
				try {
					$cpfe = substr(preg_replace('([^0-9])', '', $data['cpf']), 0, 11);
					$lida = new Log();
					if($valorGet == "sa"){
						$lida->tipo = 'Simulação de Aposentadoria';
					}else{
						$lida->tipo = 'Solicitação de Suporte';
					}
					$lida-> send_mail = "1";
					$lida->data =  date('Y-m-d H:i:s');
					$lida->ip = $_SERVER['REMOTE_ADDR'];
					$lida->status = false;
					$lida->mensagem = '<br/><b>Data/hora:</b> '.date('Y-m-d H:i:s').' <br/><b>Nome do Servidor:</b> ' . $data['nome']. ' <br/><b>CPF:</b> '.$cpfe . '<br/><b>Email:</b> '.Zend_Registry::get('auth')->getIdentity()->email . ' <br/><b>Mensagem: </b>'.$data['mensagem'].'<br/>';
					$lida->save();
					$this->getHelper('FlashMessenger')->addMessage('Mensagem Enviada com sucesso');
					$this->_helper->redirector('index', 'index');
				} catch (Exception $e) {
					$this->getHelper('FlashMessenger')->addMessage('Houve um erro ao enviar a mensagem');
					$this->_helper->redirector('index', 'index');
				}
			}
		}
		$this->view->form = $form;
	}
	
}