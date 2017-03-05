<?php

class DMG_Log {
	public static function _ ($tipo, $data = array(), $ente = null, $postagem = null) {
		if ($ente) {
			$ente_nome = Doctrine::getTable('Ente')->find($ente)->nome;
		}
		$enviaEmail = false;
		switch ($tipo) {
			case 1:
				$titulo = "Arquivo Postado com sucesso";
				$items = array(
					'Data' => date('Y-m-d H:i:s'),
					'Ente' => $ente_nome,
					'Período' => $data['periodo']
				);
			break;
			case 2:
				$titulo = "Falha na Postagem de Arquivo";
				$items = array(
					'Data' => date('Y-m-d H:i:s'),
					'Ente' => $ente_nome,
					'Período' => $data['periodo'],
					'Descrição' => $data['descricao']
				);
			break;
			case 3:
				$titulo = "Importação de Arquivos";
				$items = array(
					'Data' => date('Y-m-d H:i:s')
				);
				$enviaEmail = true;
			break;
			case 4:
				$titulo = "Falha na Importação de Arquivos";
				$items = array(
					'Data' => date('Y-m-d H:i:s'),
					'Ente' => $ente_nome,
					'Período' => $data['periodo'],
					'Descrição' => 'Layout inválido: ' . $data['descricao'],
					'Registro que originou o erro' => $data['registro_erro']
				);
				$enviaEmail = true;
			break;
			case 5:
				$titulo = "Acesso Administrador";
				$items = array(
					'Data' => date('Y-m-d H:i:s'),
					'IP' => $_SERVER['REMOTE_ADDR']
				);
				$enviaEmail = true;
			break;
			case 6:
				$titulo = "Tentativa de Acesso Administrador";
				$items = array(
					'Data' => date('Y-m-d H:i:s'),
					'IP' => $_SERVER['REMOTE_ADDR'],
					'Usuário informado' => $data['usuario'],
					'Senha informada' => $data['senha']
				);
				$enviaEmail = true;
			break;
		}
		
		$mensagem = "";
		foreach ($items as $nome => $valor) {
			$mensagem .= $nome . ": " . $valor . "\r\n";
		}
		
		$log = new Log();
		$log->data = date('Y-m-d H:i:s');
		$log->ente_id = $ente;
		$log->postagem_id = $postagem;
		$log->tipo = $titulo;
		$log->mensagem = $mensagem;
		$log->status = false;
		$log->ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
		$log->send_mail = $enviaEmail;
		$log->save();
		
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/ipreville.ini', APPLICATION_ENV);
		$tr = new Zend_Mail_Transport_Smtp($config->smtp->server, $config->smtp->toArray());
		$query = Doctrine_Query::create()->from('Log')->addWhere('status = ?', false)->addWhere('send_mail = ?', 1);
		foreach ($query->execute() as $k) {
			try {
				$template = "Log: " . $k->tipo . "\r\n\r\n" . str_replace("</b>","",str_replace("<b>","",str_replace("<br/>","\r\n",$k->mensagem))) . "\r\nSistema Digifolha";
				$mail = new Zend_Mail('UTF-8');
				$mail->setBodyText($template);
				
				$mail->setFrom($config->smtp->from, $config->smtp->from_name);
				if($k->tipo == "Simulação de Aposentadoria"){
					$mail->addTo(explode(";", $config->log->emailApo), $config->log->nome);
				}else{
					$mail->addTo($config->log->email, $config->log->nome);
				}
				$mail->setSubject(sprintf($config->log->assunto, $k->tipo));
				$mail->setDefaultTransport($tr);
				
				$mail->send();
				$k->status = true;
				$k->save();
			} catch (Exception $e) {
				$f = fopen(APPLICATION_PATH . '/../log.txt', 'a');
				if ($f) {
					fwrite($f, $template . "\r\nErro de envio:\r\n". $e->getMessage() . "\r\n----------\r\n\r\n");
					fclose($f);
				}
			}
		}
	}
}

?>