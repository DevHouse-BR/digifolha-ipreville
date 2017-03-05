<?php

date_default_timezone_set('America/Sao_Paulo');

set_include_path(implode(PATH_SEPARATOR, array(get_include_path(), dirname(__FILE__) . '/library/')));

define('APPLICATION_PATH', dirname(__FILE__) . '/application/');
define('APPLICATION_ENV', 'production');

function __autoload ($a) {
	$a = explode('_', $a);
	$a = implode('/', $a);
	require_once(dirname(__FILE__) . '/library/' . $a . '.php');
}

include_once 'library/Doctrine.php';
include_once 'library/DMG/Log.php';

$loader = Zend_Loader_Autoloader::getInstance();
$loader->pushAutoloader(array('Doctrine', 'autoload'));
$doctrineConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'development');
$doctrineConfig = $doctrineConfig->toArray();
$doctrineConfig = $doctrineConfig['doctrine'];
$manager = Doctrine_Manager::getInstance();
$manager->setAttribute(Doctrine::ATTR_USE_DQL_CALLBACKS, true);
$manager->setAttribute(Doctrine::ATTR_MODEL_LOADING, Doctrine::MODEL_LOADING_CONSERVATIVE);
$manager->setCollate('utf8_unicode_ci');
$manager->setCharset('utf8');
$manager->openConnection($doctrineConfig['connection_string']);

foreach (array_merge(glob($doctrineConfig['models_path'] . '/generated/*.php'), glob($doctrineConfig['models_path'] . '/*.php')) as $k) {
	require $k;
}

function subtrim($a, $b, $c) {
	return utf8_encode(trim(iconv_substr($a, $b, $c, 'ISO-8859-1')));
}

function salvarLinha ($linha, $postagem_id) {
	if (isset($linha['cpf'])) {
		$servidor = Doctrine::getTable('ServidorPublico')->findOneByCpf($linha['cpf']);
	}
	if (!isset($servidor) || !$servidor) {
		$servidor = new ServidorPublico();
		$servidor->cpf = $linha['cpf'];
		$servidor->nome = $linha['nome_funcionario'];
		$servidor->tipo_servidor = 'A';
		$servidor->senha = '';
		$servidor->email = '';
		$servidor->fl_ativo = false;
		$servidor->save();
	}
	$comprovante = new Comprovante();
	$comprovante->servidor_publico_id = $servidor->id;
	$comprovante->postagem_id = $postagem_id;
	$comprovante->nome_empresa = (isset($linha['nome_empresa']) ? $linha['nome_empresa'] : '');
	$comprovante->cnpj = (isset($linha['cnpj_empresa']) ? $linha['cnpj_empresa'] : '');
	$comprovante->nome_funcionario = (isset($linha['nome_funcionario']) ? $linha['nome_funcionario'] : '');
	$comprovante->matricula = (isset($linha['matricula']) ? $linha['matricula'] : '');
	$comprovante->funcao = (isset($linha['funcao']) ? $linha['funcao'] : '');
	$comprovante->setor = (isset($linha['setor']) ? $linha['setor'] : '');
	$comprovante->mes_referencia = (isset($linha['mes_referencia']) ? $linha['mes_referencia'] : '');
	$comprovante->data_admissao = (isset($linha['data_admissao']) ? $linha['data_admissao'] : '');
	$comprovante->banco = (isset($linha['banco']) ? $linha['banco'] : '');
	$comprovante->agencia = (isset($linha['agencia']) ? $linha['agencia'] : '');
	$comprovante->conta_corrente = (isset($linha['conta_corrente']) ? $linha['conta_corrente'] : '');
	$comprovante->salario_base = (isset($linha['salario_base']) ? $linha['salario_base'] : '');
	$comprovante->total_proventos = (isset($linha['total_proventos']) ? $linha['total_proventos'] : '');
	$comprovante->total_descontos = (isset($linha['total_descontos']) ? $linha['total_descontos'] : '');
	$comprovante->liquido = (isset($linha['liquido']) ? $linha['liquido'] : '');
	$comprovante->f_irrf = (isset($linha['f_irrf']) ? $linha['f_irrf'] : '');
	$comprovante->base_calc_irrf = (isset($linha['base_calc_irrf']) ? $linha['base_calc_irrf'] : '');
	$comprovante->sal_cont_inss = (isset($linha['sal_cont_inss']) ? $linha['sal_cont_inss'] : '');
	$comprovante->base_calc_fgts = (isset($linha['base_calc_fgts']) ? $linha['base_calc_fgts'] : '');
	$comprovante->fgts_mes = (isset($linha['fgts_mes']) ? $linha['fgts_mes'] : '');
	$comprovante->mensagem_1 = (isset($linha['mensagem_1']) ? $linha['mensagem_1'] : '');
	$comprovante->mensagem_2 = (isset($linha['mensagem_2']) ? $linha['mensagem_2'] : '');
	$comprovante->mensagem_3 = (isset($linha['mensagem_3']) ? $linha['mensagem_3'] : '');
	$comprovante->vinculo = (isset($linha['vinculo']) ? $linha['vinculo'] : '');
	$comprovante->categoria_vinculo = (isset($linha['categoria_vinculo']) ? $linha['categoria_vinculo'] : '');
	$comprovante->nivel_salarial = (isset($linha['nivel_salarial']) ? $linha['nivel_salarial'] : '');
	$comprovante->cpf = (isset($linha['cpf']) ? $linha['cpf'] : '');
	$comprovante->salario_hora = (isset($linha['salario_hora']) ? $linha['salario_hora'] : '');
	$comprovante->base_consignados = (isset($linha['base_consignados']) ? $linha['base_consignados'] : '');
	$comprovante->save();
	$servidor->free();
	$i = 0;
	$desc = $linha['descricao'];
	unset($linha);
	while ($i < count($desc)) {
		$l = $desc[$i];
		$comprovanteLinha = new ComprovanteLinha();
		$comprovanteLinha->comprovante_id = $comprovante->id;
		$comprovanteLinha->codigo = (int) $l['codigo'];
		$comprovanteLinha->descricao = $l['descricao'];
		$comprovanteLinha->referencia = $l['referencia'];
		if (strlen($l['proventos'])) {
			$comprovanteLinha->tipo = 'P';
			$comprovanteLinha->valor = $l['proventos'];
		} else {
			$comprovanteLinha->tipo = 'D';
			$comprovanteLinha->valor = $l['descontos'];
		}
		$comprovanteLinha->save();
		$comprovanteLinha->free();
		$i++;
	}
	unset($desc);
	$comprovante->free();
}

include_once 'importar-prefeitura.php';
include_once 'importar-ipreville.php';
include_once 'importar-hospital.php';

// envia o log a cada 2 horas...
if (time()%7200 < 600) {
	DMG_Log::_(3);
}