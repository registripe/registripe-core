<?php

/**
 * Uses the payment module to allow the user to choose an option to pay for
 * their tickets, then validates the payment.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterPaymentStep extends MultiFormStep {

	public static $is_final_step = true;

	public function getTitle() {
		return 'Payment';
	}

	/**
	 * Returns this step's data merged with the tickets from the previous step.
	 *
	 * @return array
	 */
	public function loadData() {
		$data    = parent::loadData();
		$tickets = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');

		$tickets = $tickets->loadData();
		$data['Tickets'] = $tickets['Tickets'];

		return $data;
	}

	public function getFields() {
		if (!class_exists('GatewayFieldsFactory')) throw new Exception(
			'Please install the Omnipay module to accept event payments.'
		);

		$datetime = $this->getForm()->getController()->getDateTime();
		$session  = $this->getForm()->getSession();

		$total  = $this->form->getSession()->getRegistration()->Total;

		$table = new EventRegistrationTicketsTableField('Tickets', $datetime);
		$table->setReadonly(true);
		$table->setExcludedRegistrationId($session->RegistrationID);
		$table->setShowUnavailableTickets(false);
		$table->setShowUnselectedTickets(false);
		$table->setTotal($total);

		$group = FieldGroup::create('Tickets',
				new LiteralField('ConfirmTicketsNote',
					'<p>Please confirm the tickets you wish to purchase:</p>'),
				$table
			);

		$group->addExtraClass('confirm_tickets');

		$fields = new FieldList(
			$group
		);

		$gateways = GatewayInfo::get_supported_gateways();
		//TODO: allow choosing gateway (may require additional step, or hiding groups of fields)
		//get fields for the first gateway in the list
		$factory = new GatewayFieldsFactory(key($gateways));

		$paymentFields = $factory->getFields();
		$fields->merge($paymentFields);

		$this->extend('updateFields', $fields);

		return $fields;
	}

	public function getValidator() {
		$gateways = GatewayInfo::get_supported_gateways();
		//get first gateway in list
		$validator = new RequiredFields(GatewayInfo::required_fields(key($gateways)));

		$this->extend('updateValidator', $validator);

		return $validator;
	}

	public function Link($action = null) {
		return Controller::join_links(
			$this->form->getDisplayLink(),
			$action
		)."?MultiFormSessionID={$this->Session()->Hash}";
	}

	public function validateStep($data, $form) {
		Session::set("FormInfo.{$form->FormName()}.data", $form->getData());

		$gateways = GatewayInfo::get_supported_gateways();
		//use first gateway on list
		$gateway = key($gateways);

		$registration = $this->form->getSession()->getRegistration();
		$total  = $registration->Total;

		$payment = $registration->Payment();

		//save payments that hve been captured
		if($payment->exists() && $payment->isComplete() ){
			if($payment->isCaptured()){
				$registration->Status = 'Valid';
				$registration->write();
				return true;
			}else{
				$form->sessionMessage($payment->Message, 'bad');
				return false;
			}
		}

		$payment = Payment::create()
			->init($gateway, $total->getAmount(), $total->getCurrency());
		$payment->write();
		
		$registration->PaymentID = $payment->ID;
		$registration->write();

		//redirect back to the form after offsite payment for revalidation
		$returnlink = Director::absoluteURL(
			$this->Link("RegisterForm")."&action_finish=Submit&payment=finish"
		);

		$data = array_merge($form->getData(),array(
			'name' => $registration->Name,
			'email' => $registration->Email
		));

		$response = PurchaseService::create($payment)
	        ->setReturnUrl($returnlink)
	        ->setCancelUrl($this->Link())
	        ->purchase($data);

		// will be null if already processed
		if ($response) {
			if($response->isSuccessful()) {
				$registration->Status = 'Valid';
				$registration->write();

				return true;
			} else if ($response->isRedirect()) {
    			$response->redirect();

	    		return false;
			} else {
				$form->sessionMessage($response->getMessage(), 'bad');
				
				return false;
			}
		}
		return true;
	}
}