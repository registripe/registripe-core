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
		$tickets  = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$total    = $tickets->getTotal();

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
		$factory = new GatewayFieldsFactory(array_shift(
			$gateways
		));

		$paymentFields = $factory->getFields();
		$fields->merge($paymentFields);

		$this->extend('updateFields', $fields);

		return $fields;
	}

	public function getValidator() {
		$gateways = GatewayInfo::get_supported_gateways();

		$validator = new RequiredFields(GatewayInfo::required_fields(
			array_shift($gateways)
		));

		$this->extend('updateValidator', $validator);

		return $validator;
	}

	public function validateStep($data, $form) {
		Session::set("FormInfo.{$form->FormName()}.data", $form->getData());

		$gateways = GatewayInfo::get_supported_gateways();
		$gateway = array_shift($gateways);

		$tickets = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$total   = $tickets->getTotal();

		$registration = $this->form->getSession()->getRegistration();

		$payment = Payment::create()
			->init($gateway, $total->getAmount(), $total->getCurrency())
			->setReturnUrl(sprintf("%s?registration=%s&BackUrl=%s&FormName=%s",
				'EventPaymentController/complete/',
				$registration->ID,
				$this->Link(),
				$form->getName()
			))
			->setCancelUrl(sprintf("%s?registration=%s&BackUrl=%s&FormName=%s",
				'EventPaymentController/cancel/',
				$registration->ID,
				$this->Link(),
				$form->getName()
			));

		$registration->PaymentID = $payment->ID;
		$registration->write();

		$purchase = $payment->purchase($form->getData());

		if ($purchase->isSuccessful()) {
			$registration->Status = 'Valid';
			$registration->write();

			return true;
		} elseif ($purchase->isRedirect()) {
    		$purchase->redirect();

    		return false;
		} else {
			throw new SS_HTTPResponse_Exception($response->getMessage());

			return false;
		}
	}
}