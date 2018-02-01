<?php
/**
 * @author	Vitor Pereira
 * @foo		Bar
 **/
class ModelExtensionModuleParcelamento extends Model {
	/**
	 *	Atributes from the Class
	 **/
	private $preco;
	private $special;
	private $juros;
	private $qtd_parcelas;
	private $parcela_minima;
	private $tipo_de_calculo;
	private $moeda_da_loja;
	
	/**
	 *	Configuracoes do sistema de parcelamento
	 *	----------------------------------------
	 *	$qtd_parcelas = Define a quantidade de parcelas a ser exibida para os produtos
	 *	$juros = Taxa de juros mensal (deixe em 0 para parcelamento sem juros)
	 *	$moeda_da_loja = Permite especificar a moeda utilizada na loja
	 *
	 *	$tipo_de_calculo = Permite escolher o tipo de calculo a ser utilizado
	 *	0 = Juros simples (Pagamento Digital)
	 *	1 = Tabela Price (PagSeguro e outros)
	 **/
	
	/**
	 * Esse metodo é encarregado de receber os parametros de configuração e retornar o contexto do model
	 * 
	 **/
	public function load( $args = array() ) {
		

		$default = array(
			/**
			 *	preco = Valor do Produto
			 **/
			'preco' => 1
			/**
			 *	special = valor especial do Produto(promoção, desconto, ...)
			 **/
			, 'special' => false
			/**
			 *	qtd_parcelas = Quantidade maxima de parcelas
			 **/
			, 'qtd_parcelas' => 6
			/**
			 *	juros = Valor do Juros sobre o calculo
			 **/
			, 'juros' => 1.99 // 3.99
			/**
			 * parcelas_sem_juros = Quantidade de parcelas que não serão afetadas pelo juros
			 */
			, 'parcelas_sem_juros' => 1 // int
			/**
			 *	moeda_da_loja = Simbolos da moeda corrente da Loja
			 */
			, 'moeda_da_loja' => 'R$'
			/**
			 *	parcela_minima = Valor minimo da parcela
			 */
			, 'parcela_minima' => 5
			/*	tipo_de_calculo = Permite escolher o tipo de calculo a ser utilizado
			 *	0 = Juros simples (Pagamento Digital)
			 *	1 = Tabela Price (PagSeguro e outros)
			 **/
			 , 'tipo_de_calculo' => 0
		);
		
		$args = self::parse_array_merge($default, $args);
		
        $this->preco = $args['preco'];
		$this->special = $args['special'];
		$this->juros = $args['juros'];
		$this->parcelas_sem_juros = $args['parcelas_sem_juros'];
		$this->qtd_parcelas = $args['qtd_parcelas'];
		$this->parcela_minima = $args['parcela_minima'];
		$this->tipo_de_calculo = $args['tipo_de_calculo'];
		$this->moeda_da_loja = $args['moeda_da_loja'];

		return $this;
	}


    /**
     * Merge two array in a other with the values clean.
     * @param array		First array to merge
     * @param array 	Seconde array to merge
     * @return array	Result in the merge to array params
     **/
	private function parse_array_merge($default = array(), $args = array() ){
		if(!$default || !$args )return;
		$arr = array_merge($default, $args);
		return array_map(function($a){
			return is_array($a) ? $a[ count($a) -1] : $a;
		},$arr);
	}
	
	/**
	 * @param string	
	 * @param string	
	 * @result string	Result on price clean of the '.' and ','
	 **/
	private function limpar_preco($moeda_da_loja, $preco){
		return 	trim(str_replace(',','.',trim(str_replace('.','', trim(str_replace($moeda_da_loja,"",strip_tags($preco)))))));
	}
	
	private function calculo_valor_parcela( $parcela=false ){
		$qtd_parcelas = $parcela!=false ? $parcela : $this->qtd_parcelas;
		switch($this->tipo_de_calculo){
			case 0: return ( $this->preco_numero * pow(1+($this->juros/100), $qtd_parcelas))/$qtd_parcelas;
			case 1: return ( $this->preco_numero * ($this->juros/100))/(1-(1/pow(1+($this->juros/100),$qtd_parcelas)));
			default: return 0;
		}
	}
	
	/**
	 * Make a calcle of the values with price parceled
	 * @return array	Return a List of a values of the parceled
	 **/
	public function calculo() {
		
		$parcelamento['total'] = self::calculo_valor_parcela();
		$parcelamento['juros'] = $this->juros;
		
		$this->preco_numero = self::limpar_preco($this->moeda_da_loja, $this->preco);
		
		$max_parcelas = intval( self::calculo_valor_parcela() / $this->parcela_minima );
		if( $max_parcelas < $this->qtd_parcelas ) {
			$this->qtd_parcelas = $max_parcelas;
		}
		
		$parcelamento['parcelas'] = array_map(function($parcela){
			$juros = false;
			if( $parcela == 1 && $this->parcelas_sem_juros >= 1 ){
				$valor = $this->preco_numero;
			} else if ( $parcela > $this->parcelas_sem_juros ){
				$valor = self::calculo_valor_parcela($parcela);//parcela($parcela);
				$juros = true;
			} else {
				$valor = $this->preco_numero / $parcela; // $this->qtd_parcelas ;
			}
			return array(
				'parcela' => $parcela,
				'valor' => $valor,
				'juros' => $juros
			);
		},range(1, $this->qtd_parcelas ));
		return $parcelamento;
	}
	
}