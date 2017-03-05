<?php

$query = Doctrine_Query::create()
	->from('Postagem p')
	->addWhere('p.fl_processado = ?', false)
	->addWhere('p.fl_publicado = ?', true)
	->addWhere('p.ente_id = ?', 3)
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
			if ($file[$i][0] == 3) {
				$holerite[] = $tmp;
				$tmp = array();
			}
		}
		if (!count($holerite)) {
			throw new Exception('Arquivo inválido #1');
		}
		foreach ($holerite as $k) {
			$linha = array();
			if (!count($k)) {
				throw new Exception('Arquivo inválido #2');
			}
			foreach ($k as $l) {
				switch ($l[0]) {
					case 1:
						$linha['nome_empresa'] = subtrim($l, 1, 40);
						$linha['cnpj_empresa'] = subtrim($l, 41, 20);
						$linha['nome_funcionario'] = subtrim($l, 61, 40);
						$linha['matricula'] = subtrim($l, 101, 13);
						$linha['funcao'] = subtrim($l, 114, 40);
						$linha['setor'] = subtrim($l, 154, 50);
						$linha['mes_referencia'] = subtrim($l, 204, 18);
						$linha['data_admissao'] = subtrim($l, 222, 10);
						$linha['banco'] = subtrim($l, 232, 20);
						$linha['conta_corrente'] = subtrim($l, 252, 20);
						$linha['vinculo'] = subtrim($l, 272, 3);
						$linha['categoria_vinculo'] = subtrim($l, 275, 3);
						$linha['nivel_salarial'] = subtrim($l, 278, 20);
						$linha['cpf'] = preg_replace('/([^0-9])/', '', subtrim($l, 298, 14));
						$linha['salario_hora'] = subtrim($l, 312, 14);
					break;
					case ' ':
					case 2:
						$linha['descricao'][] = array(
							'codigo' => subtrim($l, 1, 4),
							'descricao' => subtrim($l, 5, 35),
							'referencia' => subtrim($l, 40, 14),
							'proventos' => subtrim($l, 54, 14),
							'descontos' => subtrim($l, 68, 14)
						);
					break;
					case 3:
						$linha['salario_base'] = subtrim($l, 1, 14);
						$linha['total_proventos'] = subtrim($l, 15, 14);
						$linha['total_descontos'] = subtrim($l, 29, 14);
						$linha['liquido'] = subtrim($l, 43, 14);
						$linha['f_irrf'] = subtrim($l, 57, 14);
						$linha['sal_cont_inss'] = subtrim($l, 71, 14);
						$linha['base_calc_fgts'] = subtrim($l, 85, 14);
						$linha['fgts_mes'] = subtrim($l, 99, 14);
						$linha['base_calc_irrf'] = subtrim($l, 113, 14);
						$linha['base_consignados'] = subtrim($l, 127, 14);
						$linha['mensagem_1'] = subtrim($l, 141, 70);
						$linha['mensagem_2'] = subtrim($l, 211, 70);
						$linha['mensagem_3'] = subtrim($l, 281, 70);
					break;
				}
			}
			salvarLinha($linha, $postagem->id);
		}
		$tf = time() + microtime();
		$postagem->fl_processado = true;
		$postagem->save();
		echo "Tempo de processamento: " . ($tf-$ti) . "\n";
		Doctrine_Manager::getInstance()->getCurrentConnection()->commit();
	} catch (Exception $e) {
		echo "Erro de importação: " . $e->getMessage() . "\n";
		echo "Erro de importação: " . $e->getTraceAsString() . "\n";
		Doctrine_Manager::getInstance()->getCurrentConnection()->rollback();
		DMG_Log::_(4, array('periodo' => $postagem->periodo, 'descricao' => $e->getMessage(), 'registro_erro' => ''), $postagem->ente_id, $postagem->id);
	}
	$postagem->save();
}