<?php
/**
 * Represents a registration to an event.
 *
 * @package registripe
 */
class EventRegistration extends DataObject {

	private static $db = array(
		'FirstName'   => 'Varchar',
		'Surname' => 'Varchar',
		'Email'  => 'Varchar(255)',
		'Status' => 'Enum("Unsubmitted, Unconfirmed, Valid, Canceled","Unsubmitted")',
		'Total'  => 'Currency',
		'Token'  => 'Varchar(40)'
	);

	private static $has_one = array(
		'Event'   => 'RegistrableEvent',
		'Member' => 'Member',
		'RegistrantAttendee' => 'EventAttendee'
	);

	private static $has_many = array(
		'Attendees' => 'EventAttendee'
	);

	private static $summary_fields = array(
		'Name'          => 'Name',
		'Email'         => 'Email',
		'TotalQuantity' => 'Places',
		'Total.Nicer' 		=> 'Total',
		'Created.Nice' 		=> 'Date'
	);

	private static $casting = array(
		'calculateTotal' => 'Currency'
	);

	protected function onBeforeWrite() {
		if (!$this->isInDB()) {
			$generator = new RandomGenerator();
			$this->Token = substr($generator->randomToken(), 0,40);
		}

		parent::onBeforeWrite();
	}

	public function getCMSFields() {
		$fields = $this->scaffoldFormFields(array(
			'restrictFields' => array(
				'FirstName', 'Surname', 'Email',
				'Status',
				'Attendees'
			),
			'includeRelations' => true
		));

		$conf = GridFieldConfig_RecordEditor::create()
				 ->removeComponentsByType(
				 	"GridFieldAddNewButton"
				 );
		$fields->fieldByName("Attendees")->setConfig($conf);
		
		if (class_exists('Payment')) {
			$fields->fieldByname("Payments")
				->setConfig($conf)
				->performReadonlyTransformation();
		}
		$this->extend("updateCMSFields", $fields);

		return $fields;
	}

	public function getFrontEndFields($params = null) {
		$fields = parent::getFrontEndFields();
		$fields->removeByName(array(
			"PaymentID", "MemberID", "EventID", "Status", "Total", "Token"
		));

		return $fields;
	}

	/**
	 * Return an appropriate name for this registration
	 */
	public function getTitle() {
		return $this->Name;
	}

	/**
	 * Get the full name of the registrant.
	 * @return string name
	 */
	public function getName() {
		return ($this->Surname) ? trim($this->FirstName . ' ' . $this->Surname) : $this->FirstName;
	}

	/**
	 * Get the name and email of the reistrant
	 * @return string
	 */
	public function getRegistrant() {
		return sprintf(
			_t("EventRegistration.REGISTRANT", "%s (%s)"),
			$this->Name,
			$this->Email
		);
	}

	/**
	 * Total number of atendees / spaces for this registration
	 * @return int
	 */
	public function TotalQuantity() {
		return $this->Attendees()->count();
	}

	/**
	 * Get all the ticket types selected for this registration.
	 */
	public function Tickets() {
		return EventTicket::get()
			->innerJoin("EventAttendee", "\"EventTicket\".\"ID\" = \"EventAttendee\".\"TicketID\"")
			->filter("EventAttendee.RegistrationID", $this->ID);
	}

	/**
	 * Get an array of ticketid => quantity
	 * @return array
	 */
	public function getTicketQuantities() {
		$quantities = array();
		foreach($this->Tickets() as $ticket){
			$quantities[$ticket->ID] = $this->Attendees()
										->filter("TicketID", $ticket->ID)
										->count();
		}
		
		return $quantities;
	}

	/**
	 * Create an attendee in this registration with the given ticket.
	 * @param  EventTicket $ticket
	 * @return EventAttendee
	 */
	public function createAttendee(EventTicket $ticket) {
		$attendee = new EventAttendee();
		$attendee->TicketID = $ticket->ID;
		$attendee->write();
		$this->Attendees()->add($attendee);
		return $attendee;
	}

	/**
	 * @return SS_Datetime
	 */
	public function ConfirmTimeLimit() {
		$unconfirmed = $this->Status == 'Unconfirmed';
		$limit = $this->Event()->ConfirmTimeLimit;
		if ($unconfirmed && $limit) {
			return DBField::create_field('SS_Datetime', strtotime($this->Created) + $limit);
		}
	}

	/**
	 * Generate a desicrption of the tickets in the registration
	 * @return string
	 */
	public function getDescription() {
		$parts = array();
		foreach($this->getTicketQuantities() as $ticketid => $quantity){
			if($ticket = EventTicket::get()->byID($ticketid)){
				$parts[] = $quantity."x".$ticket->Title;
			}
		}

		return $this->Event()->Title.": ".implode(",", $parts);
	}

	public function calculateTotal(){
		$componentNames = self::config()->calculator_components;
		$className = "EventRegistration\Calculator";
		$calculator = \Injector::inst()->create($className, $this, $componentNames);
		$amount = $calculator->calculate();
		return $this->Total = $amount;
	}

	public function getTotalOutstanding() {
		$outstanding = $this->Total - $this->TotalPaid();
		if($outstanding < 0){
			$outstanding = 0;
		}
		return $outstanding;
	}

	public function isSubmitted() {
		return ($this->Status != "Unsubmitted");
	}

	public function canPay() {
		return !$this->isSubmitted() && ($this->getTotalOutstanding() > 0);
	}

	public function canSubmit() {
		$hasattendees = $this->Attendees()->exists();
		$hasoutstanding = $this->getTotalOutstanding() > 0;

		return $hasattendees && !$hasoutstanding;
	}

	/**
	 * @return string
	 */
	public function Link($action = '') {
		return Controller::join_links(
			$this->Event()->Link(),
			'registration',
			$this->ID,
			$action,
			'?token=' . $this->Token
		);
	}

	public function canCreate($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

	public function canEdit($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

	public function canDelete($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

	public function canView($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

}