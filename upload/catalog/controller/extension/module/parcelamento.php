<?php
class ControllerExtensionModuleParcelamento extends Controller {

	public function index( $args = array() ) {
        
		$this->load->language('extension/module/parcelamento');


		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		$this->load->model('extension/module/parcelamento');
		
		$model_parcelamento = $this->model_extension_module_parcelamento->load([
			//'preco'=>$product_info['price'],
			'juros'=> 1.99,
			'parcelas_sem_juros' => 3,
			'tipo_de_calculo' => 0,
			'preco' => $this->currency->format(  $this->tax->calculate($product_info['price']
									, $product_info['tax_class_id']
									, $this->config->get('config_tax'))
									, $this->session->data['currency'])
		]);
	
		
		$_currency = $this->currency;
		$_tax = $this->tax;
		$_config = $this->config->get('config_tax');
		$_session = $this->session->data['currency'];
		
		
		$parcelamento = $model_parcelamento->calculo();
		
		// var_dump($parcelamento['total']);
		
		$parcelamento['parcelas'] = array_map( function($parcela) use($product_info, $_currency, $_tax, $_config, $_session){
			$parcela['valor'] = @$_currency->format(  @$_tax->calculate($parcela['valor']
									, $product_info['tax_class_id']
									, $_config)
									, $_session);
			return $parcela;
		}, $parcelamento['parcelas'] );
		
		$data['parcelas'] = $parcelamento['parcelas'];
		$data['juros'] = sprintf($this->language->get('text_juros'), $parcelamento['juros']);
		
		return $this->load->view($this->config->get('config_template') . '/extension/module/parcelamento', $data);
	}

}