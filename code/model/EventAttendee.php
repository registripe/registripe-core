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
	 	'FirstName',
	 	'Surname',
	 	'Email',
	 	'Ticket.Type' => 'Title'
	 );

	//TODO:: test me, or perhaps only failover certian fields
	public function __construct($record = null, $isSingleton = false, $model = null) {
		parent::__construct($record, $isSingleton, $model);
		$this->failover = $this->Member();
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
