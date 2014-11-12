<?php

/**
 * Represents the details of an event registration attendee.
 */
class EventAttendee extends DataObject{

	private static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
		'Email' => 'Varchar(256)'
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
	 	'Ticket.Summary' => 'Ticket'
	 );

	//TODO:: test me, or perhaps only failover certian fields
	public function __construct($record = null, $isSingleton = false, $model = null) {
		parent::__construct($record, $isSingleton, $model);
		$this->failover = $this->Member();
	}

	public function getFrontEndFields($params = null) {
		$fields = parent::getFrontEndFields();
		$fields->removeByName(array(
			'RegistrationID', 'TicketID', 'MemberID'
		));

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

}
