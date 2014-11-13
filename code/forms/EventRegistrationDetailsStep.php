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
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			$fields->push(
				new FrontEndGridField("Attendees", "Attendees", $registration->Attendees(),
					$editorconfig = new FrontEndGridFieldConfig_SimpleEditor()
				)
			);
			$attendeefields = singleton('EventAttendee')->getFrontEndFields();
			$availabletickets = $this->form->getController()->getEvent()
									->getAvailableTickets();
			$attendeefields->push(
				new DropdownField("TicketID", "Ticket",
					$availabletickets->map('ID', 'Summary')->toArray()
				)
			);

			$detailform = $editorconfig
					->getComponentByType("FrontEndGridFieldDetailForm");
			$detailform->setFields($attendeefields);
			$detailform->setValidator(new RequiredFields(
				"FirstName", "Surname", "Email", "TicketID"
			));

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

	public function saveData($data) {
		$rego = $this->getRegistration();
		$rego->update(Convert::raw2sql($data));
		if($id = Member::currentUserID()) {
			$rego->MemberID = $id;
		}
		parent::saveData($data);
	}

	public function validateStep($data, $form) {
		$rego = $this->getRegistration();
		if($rego->isInDB() && !$rego->Attendees()->exists()) {
			$form->sessionMessage('You need to add one or more attendees.', 'bad');
			return false;
		}
		$valid = parent::validateStep($data, $form);
		//write/start the registration once the first step is correctly validated
		if($valid){
			$this->getRegistration()->write();
		}
		
		return $valid;
	}

	/**
	 * @return string
	 */
	public function getNextStep() {
		$registration = $this->getRegistration();
		//if no attendees, redirect back to this step
		if(!$registration || !$registration->Attendees()->exists()){
			return 'EventRegistrationStep';
		}

		if ($this->getTotalCost($registration)->Amount > 0) {
			return 'EventRegisterPaymentStep';
		}
		return 'EventRegisterFreeConfirmationStep';
	}

	public function getNextText(){
		if(!$this->getRegistration()->isInDB()){
			return 'Start Registration';
		}

		return parent::getNextText();
	}

	/**
	 * There is no back for the first step.
	 */
	public function canGoBack() {
		return false;
	}

}

class FrontEndGridFieldConfig_SimpleEditor extends GridFieldConfig{

    public function __construct($itemsPerPage=null) {
        parent::__construct($itemsPerPage);

        $this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
        $this->addComponent(new GridFieldTitleHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new GridFieldEditButton());
		$this->addComponent(new GridFieldDeleteAction());
		$this->addComponent(new GridFieldFooter(null, false));
		$this->addComponent(new FrontEndGridFieldDetailForm());
    }

}

