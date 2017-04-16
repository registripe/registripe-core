<?php

/**
 * Event Registration Attendee.
 */
class EventAttendee extends DataObject{

	private static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
		'Email' => 'Varchar(256)',
		'Cost' => 'Currency'
	);

	public static $has_one = array(
		'Registration' => 'EventRegistration',
		'Ticket' => 'EventTicket',
		'Member' => 'Member'
	);

	public static $default_sort = "Surname ASC, FirstName ASC";

	private static $summary_fields = array(
		'FirstName' => 'First Name',
		'Surname' => 'Last Name',
		'Email' => 'Email',
		'Ticket.Title' => 'Ticket',
		'Cost.Nicer' => 'Cost'
	);

	private static $export_fields = array(
		'FirstName' => 'First Name',
		'Surname' => 'Last Name',
		'Email' => 'Email',
		'Ticket.Title' => 'Ticket',
		'Cost' => 'Cost',
		'Registration.Registrant' => 'Registrant'
	);

	public function getFrontEndFields($params = null) {
		$fields = parent::getFrontEndFields();
		$fields->removeByName(array(
			'RegistrationID', 'MemberID', 'Cost'
		));
		$fields->replaceField("Email",
			EmailField::create("Email")
		);

		return $fields;
	}

	public function getTitle(){
		return $this->getName();
	}

	public function getName() {
		if($this->FirstName || $this->Surname){
			return sprintf("%s %s", $this->FirstName, $this->Surname);
		}
		elseif($member = $this->Member()){
			return $member->Name;	
		}
	}

	public function validate() {
		$result = parent::validate();
		if(!$this->Ticket()){
			$result->error("Attendee must have a ticket.");
		}
		if(!$this->Registration()){
			$result->error("Attendee must have a registration.");
		}
		return $result;
	}

	public function canCreate($member = null) {
		return true;
	}

	public function canEdit($member = null) {
		return true;
	}

	public function canDelete($member = null) {
		return true;
	}

	public function canView($member = null) {
		return true;
	}

}
