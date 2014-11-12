<?php

class EventRegistrationDetailsStep extends EventRegistrationStep {

	public function getTitle() {
		return 'Registration Details';
	}

	public function getFields() {
		$fields = new FieldList();
		//get registration front-end fields
		$fields->merge(singleton("EventRegistration")->getFrontEndFields());

		$registration = $this->getRegistration();
		if($registration->isInDB()){
			$fields->push(
				new FrontEndGridField("Attendees", "Attendees", $registration->Attendees(),
					$editorconfig = new FrontEndGridFieldConfig_RecordEditor()
				)
			);
			$attendeefields = singleton('EventAttendee')->getFrontEndFields();
			$availabletickets = $this->form->getController()->getEvent()
									->getAvailableTickets();
			$attendeefields->push(
				new DropdownField("TicketID", "Ticket", $availabletickets->map('ID', 'Summary')->toArray())
			);
			$detailform = $editorconfig->getComponentByType("FrontEndGridFieldDetailForm");
			$detailform->setFields($attendeefields);
			$detailform->setValidator(new RequiredFields("FirstName", "Surname", "Email", "TicketID"));

		}
		$this->extend('updateFields', $fields);

		return $fields;
	}

	public function getValidator() {
		return new RequiredFields(
			'Name',
			'Email'
		);
	}

	public function loadData() {
		$data = array();
		//member data
		if ($member = Member::currentUser()) {
			$data['Name'] = $member->Name;
			$data['Email'] = $member->Email;
		}
		//registration data
		$registration = $this->getRegistration();
		$data = array_merge($data, $registration->toMap());
		//session data
		$data = array_merge($data, parent::loadData());

		return $data;
	}

	public function validateStep($data, $form) {
		$rego = $this->getRegistration();
		//TODO: fix me - not working properly
		// if($rego->isInDB() && !$rego->Attendees()->exists()) {
		// 	$form->sessionMessage('You need to add attendees', 'bad');
		// 	return false;
		// }

		return parent::validateStep($data, $form);
	}

	public function saveData($data) {
		$registration = $this->getRegistration();
		$registration->update($data);
		$registration->write();
		parent::saveData($data);
	}

	/**
	 * @return string
	 */
	public function getNextStep() {
		$registration = $this->getRegistration();
		//if not attendees, redirect back here
		if(!$registration || !$registration->Attendees()->exists()){
			return 'EventRegistrationStep';
		}

		if ($this->getTotalCost($registration) > 0) {
			return 'EventRegisterPaymentStep';
		}
		return 'EventRegisterFreeConfirmationStep';
	}

	/**
	 * There is no back for the first step.
	 */
	public function canGoBack() {
		return false;
	}

}