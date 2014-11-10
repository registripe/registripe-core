<?php
/**
 * A form step that gets the user to select the tickets they wish to purchase,
 * as well as enter their details.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterTicketsStep extends MultiFormStep {

	private static $create_member = false;

	public function getTitle() {
		return 'Event Tickets';
	}

	/**
	 * @return string
	 */
	public function getNextStep() {
		if ($this->getTotal()->getAmount() > 0) {
			return 'EventRegisterPaymentStep';
		} else {
			return 'EventRegisterFreeConfirmationStep';
		}
	}

	public function loadData() {
		$data = parent::loadData();
		if ($member = Member::currentUser()) {
			$data['Name'] = $member->Name;
			$data['Email'] = $member->Email;
		}

		return $data;
	}

	/**
	 * Returns the total sum of all the tickets the user is purchasing.
	 *
	 * @return Money
	 */
	public function getTotal() {
		$amount   = 0;
		$currency = null;
		$data     = $this->loadData();

		if (isset($data['Tickets'])) {
			foreach ($data['Tickets'] as $id => $quantity) {
				$ticket = EventTicket::get()->byID($id);
				$price  = $ticket->obj('Price');

				if ($ticket->Type == 'Free' || !$quantity) {
					continue;
				}

				$amount  += $price->getAmount() * $quantity;
				$currency = $price->getCurrency();
			}
		}

		return DBField::create_field('Money', array(
			'Amount'   => $amount,
			'Currency' => $currency
		));
	}

	public function getFields() {
		$tickets = $this->getForm()->getController()->getEvent()->Tickets();
		$session  = $this->getForm()->getSession();

		$fields = new FieldList(
			$tickets = new EventRegistrationTicketsTableField('Tickets', $tickets)
		);

		$tickets->setExcludedRegistrationId($session->RegistrationID);

		if ($member = Member::currentUser()) {
			$fields->push(new ReadonlyField('Name', 'Your name'));
			$fields->push(new ReadonlyField('Email', 'Email address'));
		} else {
			$fields->push(new TextField('Name', 'Your name'));
			$fields->push(new EmailField('Email', 'Email address'));
		}

		$this->extend('updateFields', $fields);

		return $fields;
	}

	public function getValidator() {
		if ($member = Member::currentUser()) {
			$validator = new RequiredFields();
		} else {
			$validator = new RequiredFields('Name', 'Email');
		}

		$this->extend('updateValidator', $validator);
		
		return $validator;
	}

	public function validateStep($data, $form) {
		$this->saveData($form->getData());
		$form->clearMessage(); //hack until MultiForm forTemplate is fixed

		$event = $this->getForm()->getController()->getEvent();
		$data  = $form->getData();

		if ($event->OneRegPerEmail) {
			if (Member::currentUserID()) {
				$email = Member::currentUser()->Email;
			} else {
				$email = $data['Email'];
			}

			$existing = EventRegistration::get()
				->filter("Email", $email)
				->filter("Status:not",'Canceled')
				->filter("EventID", $event->ID)
				->first();

			if ($existing) {
				$form->addErrorMessage(
					'Email',
					'A registration for this email address already exists',
					'required');
				return false;
			}
		}

		// Ensure that the entered ticket data is valid.
		if (!$this->form->validateTickets($data['Tickets'], $form)) {
			$form->sessionMessage('Please enter a valid quantity for your ticket order', 'bad');

			return false;
		}

		// Finally add the tickets to the actual registration.
		$registration = $this->form->getSession()->getRegistration();
		$form->saveInto($registration);

		if ($member = Member::currentUser()) {
			$registration->Name  = $member->getName();
			$registration->Email = $member->Email;
		} else {
			if(Config::inst()->get('EventRegisterTicketsStep', 'create_member')) {
				$member = Member::get()->filter(array(
					'Email' => $data['Email']
				))->first();

				if(!$member) {
					$member = Injector::inst()->create('Member');
					$member->FirstName = trim(substr($data['Name'], strpos($data['Name'], ' ')));
					$member->Surname = trim(substr($data['Name'], strpos($data['Name'], ''), strlen($data['Name'])));
					$member->Email = $data['Email'];

					$member->extend('onAfterCreateRegistrationMember');
					$member->write();
					$member->logIn();
				}
			}

			$registration->Name  = $data['Name'];
			$registration->Email = $data['Email'];
		}

		$registration->EventID   = $event->ID;
		$registration->MemberID = Member::currentUserID();

		$total = $this->getTotal();

		$registration->Total->setCurrency($total->getCurrency());
		$registration->Total->setAmount($total->getAmount());
		$registration->write();

		//add attendees to registration
		$registration->Attendees()->removeAll();
		foreach ($data['Tickets'] as $id => $quantity) {
			$ticket = EventTicket::get()->byID($id);
			for ($i=0; $i < $quantity; $i++) {
				$registration->createAttendee($ticket);
			}
		}
		
		$this->extend('onAfterValidateStep', $data, $registration);
		
		return parent::validateStep($data, $form);
	}

}