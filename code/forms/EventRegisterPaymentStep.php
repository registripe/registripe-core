<?php

/**
 * Uses the payment module to allow the user to choose an option to pay for
 * their tickets, then validates the payment.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterPaymentStep extends EventRegistrationStep {

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
		$registration = $this->form->getSession()->getRegistration();
		$data['Tickets'] = $registration->getTicketQuantities();

		return $data;
	}

	public function getFields() {
		if (!class_exists('GatewayFieldsFactory')) throw new Exception(
			'Please install the Omnipay module to accept event payments.'
		);

		$tickets = $this->getEvent()->Tickets();
		$registration  = $this->getRegistration();
		$total  = $registration->Total;

		$table = new EventRegistrationTicketsTableField('Tickets', $tickets);
		$table->setReadonly(true);
		$table->setExcludedRegistrationId($registration->ID);
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

		$registration = $this->getRegistration();

		//complete registration if registration is already valid
		if($registration->Status == 'Valid'){
			return true;
		}

		$total  = $registration->Total;

		$payment = $registration->Payment();
		$paymentstatus = $form->getRequest()->getVar('payment');
		//save payments that hve been captured
		
		if($payment->exists() && $paymentstatus){
			if($paymentstatus == "success" && $payment->isComplete()){
				if($payment->isCaptured()){
					$registration->Status = 'Valid';
					$registration->write();
					return true;
				}else{
					$form->sessionMessage($payment->LatestMessage, 'bad');
					return false;
				}
			}
			if($paymentstatus == "failure"){
				$form->sessionMessage($payment->LatestMessage, 'bad');
				return false;
			}
			$form->sessionMessage("Payment failed", 'bad');
			return false;
		}

		$payment = Payment::create()
			->init($gateway, $total->getAmount(), $total->getCurrency());
		$payment->write();
		
		$registration->PaymentID = $payment->ID;
		$registration->write();

		//prevent redirect back to homepage
		$backurl = $this->Link();
		$linkbase = $this->Link("RegisterForm")."&action_finish=Submit&BackURL=$backurl";

		//redirect back to the form after offsite payment for revalidation
		$successlink = $linkbase."&payment=success";
		$failurelink = $linkbase."&payment=failure";

		$data = array_merge($form->getData(),array(
			'name' => $registration->Name,
			'email' => $registration->Email,
			'description' => $registration->Description

		));

		$response = PurchaseService::create($payment)
	        ->setReturnUrl($successlink)
	        ->setCancelUrl($failurelink)
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