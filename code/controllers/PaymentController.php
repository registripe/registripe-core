<?php

/**
 * Handles making payment for a given Payable.
 */
class PaymentController extends Page_Controller{

	private static $allowed_actions = array(
		'GatewaySelectForm',
		'GatewayDataForm'
	);

	protected $payable;
	protected $amount;
	protected $successurl;
	protected $cancelurl;

	public function __construct($record, $payable, $amount, $successurl) {
		parent::__construct($record);
		$this->payable = $payable;
		$this->amount = $amount;
		$this->successurl = $successurl;
	}

	public function init() {
		parent::init();
		//check if payment is even required
		if($this->isPaid()){
			return $this->redirect($this->successurl);
		}
	}

	public function index() {
		$form = '';
		$content = "You are about to make payment for ".$this->amount;
		$newpayment = $this->getCurrentPayment();

		if($newpayment){
			$form = $this->GatewayDataForm();
			$content .= " via ".GatewayInfo::nice_title($newpayment->Gateway).".";
		}else{
			$form = $this->GatewaySelectForm();
			//TODO: display historical payments, if any
		}

		return array(
			'Content' => $content,
			'Form' => $form
		);
	}

	/**
	 * Get the most recently created payment that
	 * has not started interacting with its gateway.
	 * @return Payment|null
	 */
	public function getCurrentPayment() {
		return $this->payable->Payments()
						->filter("Status", "Created")
						->sort("Created", "DESC")
						->first();
	}

	/**
	 * Form for selecting a gateway.
	 */
	public function GatewaySelectForm() {
		$gateways = GatewayInfo::get_supported_gateways();
		$fields = new FieldList(
			new OptionsetField("Gateway", "Gateway", $gateways)
		);
		$validator = new RequiredFields(
			'Gateway'
		);
		$actions = new FieldList(
			new FormAction("select", "Make Payment")
		);

		return new Form($this, "GatewaySelectForm", $fields, $actions, $validator);
	}

	public function select($data, $form) {
		$currency = "NZD"; //TODO: move this
		if(!GatewayInfo::is_supported($data['Gateway'])){
			$form->addErrorMessage("Gateway", "Method is not supported", "bad");
			return $this->redirectBack();
		}
		//create payment using gateway
		$payment = $this->createPayment($data['Gateway'], $currency); 

		//redirect to offsite gateway, if there are no fields to fill out
		return $this->redirectBack();
	}

	protected function createPayment($gateway, $currency) {
		$payment = Payment::create()
					->init($gateway, $this->amount, $currency);
		$this->payable->Payments()->add($payment);

		return $payment;
	}

	/**
	 * Form for collecting gateway data.
	 */
	public function GatewayDataForm() {
		$payment = $this->getCurrentPayment();

		$factory = new GatewayFieldsFactory($payment->Gateway);
		$fields = $factory->getFields();
		//collect the required on-site data
		//choose different gateway
		//back to app?
		$actions = new FieldList(
			new FormAction("cancel", "Choose Different Method"),
			new FormAction("pay", "Make Payment")
			
		);
		$validator = new RequiredFields(
			//GatewayInfo::required_fields($payment->Gateway)
			//TODO: required fields
				//but disable validation for cancel
		);
		return new Form($this, "GatewayDataForm", $fields, $actions, $validator);
	}

	public function cancel($data, $form) {
		$payment = $this->getCurrentPayment();
		if($payment){
			//TODO: store message / log
			$payment->Status = $void;
			$payment->write();
		}
		return $this->redirect($this->Link());
	}

	public function pay($data, $form) {
		$payment = $this->getCurrentPayment();

		//get safer data
		$data = $form->getData();
		//TODO: pass in custom data
		return $this->processPayment($payment, $data);
	}

	protected function processPayment($payment, $data) {
		$response = PurchaseService::create($payment)
					->setReturnUrl($this->Link()) //TODO: change me
					->setCancelUrl($this->Link())
					->purchase($data);

		return $response->redirect();
	}

	protected function isPaid() {
		return ($this->payable->TotalPaid() >= $this->amount);
	}

}
