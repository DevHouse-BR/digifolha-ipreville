<?php

$query = Doctrine_Query::create()
	->from('Postagem p')
	->addWhere('p.fl_processado = ?', false)
	->addWhere('p.fl_publicado = ?', true)
	->addWhere('p.ente_id = ?', 2)
;

foreach ($query->execute() as $postagem) {
	try {
		Doctrine_Manager::getInstance()->getCurrentConnection()->beginTransaction();
		echo "Importando...\n";
		$ti = time() + microtime();
		$file = file(APPLICATION_PATH . '/../data/' . $postagem->arquivo_postado);
		if (!$file) {
			throw new Exception('Arquivo inválido #0');
		}
		$holerite = array();
		$tmp = array();
		for ($i = 0; $i < count($file); $i++) {
			$tmp[] = $file[$i];
			if (substr($file[$i], 0, 2) == '08') {
				$holerite[] = $tmp;
				$tmp = array();
			}
		}
		unset($file);
		unset($tmp);
		if (!count($holerite)) {
			throw new Exception('Arquivo inválido #1');
		}
		foreach ($holerite as $k) {
			$linha = array();
			if (!count($k)) {
				throw new Exception('Arquivo inválido #2');
			}
			foreach ($k as $l) {
				switch (substr($l, 0, 2)) {
					case '01':
						$linha['matricula'] = subtrim($l, 2, 10);
						$linha['mes_referencia'] = subtrim($l, 14, 2) . '/' . subtrim($l, 16, 4);
						$linha['banco'] = subtrim($l, 198, 29);
						$linha['agencia'] = subtrim($l, 227, 6);
						$linha['conta_corrente'] = subtrim($l, 233, 10);
						$linha['nome_funcionario'] = subtrim($l, 133, 45);
						$linha['cpf'] = preg_replace('/([^0-9])/', '', subtrim($l, 178, 11));
						$linha['nome_empresa'] = subtrim($l, 26, 45);
						$linha['cnpj_empresa'] = subtrim($l, 260, 14);
					break;
					case '02':
						// nada a importar...
					break;
					case '03':
						if ($l[14] != 'V') {
							throw new Exception('Arquivo inválido #3');
						}
						$linha['descricao'][] = array(
							'codigo' => (int) subtrim($l, 15, 3),
							'descricao' => subtrim($l, 249, 35),
							'referencia' => (int) subtrim($l, 18, 11),
							'proventos' => number_format(((int) subtrim($l, 27, 15))/100, 2, ',', '.'),
							'descontos' => ''
						);
					break;
					case '04':
						if ($l[14] != 'D') {
							throw new Exception('Arquivo inválido #4');
						}
						$linha['descricao'][] = array(
							'codigo' => (int) subtrim($l, 15, 3),
							'descricao' => subtrim($l, 249, 35),
							'referencia' => (int) subtrim($l, 18, 11),
							'proventos' => '',
							'descontos' => number_format(((int) subtrim($l, 27, 15))/100, 2, ',', '.')
						);
					break;
					case '05':
						$linha['total_proventos'] = number_format(((int) subtrim($l, 14, 15))/100, 2, ',', '.');
						$linha['total_descontos'] = number_format(((int) subtrim($l, 29, 15))/100, 2, ',', '.');
						$linha['liquido'] = number_format(((int) subtrim($l, 44, 15))/100, 2, ',', '.');
					break;
					case '06':
						$linha['mensagem_1'] = subtrim($l, 14, 80);
					break;
					case '07':
						$linha['mensagem_2'] = subtrim($l, 14, 80);
					break;
					case '08':
						$linha['mensagem_3'] = subtrim($l, 14, 80);
					break;
					default:
						throw new Exception('Arquivo inválido #3');
					break;
				}
			}
			salvarLinha($linha, $postagem->id);
		}
		$tf = time() + microtime();
		$postagem->fl_processado = true;
		$postagem->save();
		$postagem->free();
		echo "Tempo de processamento: " . ($tf-$ti) . "\n";
		Doctrine_Manager::getInstance()->getCurrentConnection()->commit();
	} catch (Exception $e) {
		echo "Erro de importação: " . $e->getMessage() . "\n";
		Doctrine_Manager::getInstance()->getCurrentConnection()->rollback();
		DMG_Log::_(4, array('periodo' => $postagem->periodo, 'descricao' => $e->getMessage(), 'registro_erro' => ''), $postagem->ente_id, $postagem->id);
	}
	$postagem->save();
}