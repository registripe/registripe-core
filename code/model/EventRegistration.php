<?php
/**
 * Represents a registration to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistration extends DataObject {

	private static $db = array(
		'Name'   => 'Varchar(255)',
		'Email'  => 'Varchar(255)',
		'Status' => 'Enum("Unsubmitted, Unconfirmed, Valid, Canceled")',
		'Total'  => 'Money',
		'Token'  => 'Varchar(40)'
	);

	private static $has_one = array(
		'Event'   => 'RegistrableEvent',
		'Member' => 'Member'
	);

	private static $has_many = array(
		'Attendees' => 'EventAttendee'
	);

	private static $summary_fields = array(
		'Name'          => 'Name',
		'Email'         => 'Email',
		'TotalQuantity' => 'Places'
	);

	protected function onBeforeWrite() {
		if (!$this->isInDB()) {
			$generator = new RandomGenerator();
			$this->Token = substr($generator->randomToken(), 0,40);
		}

		parent::onBeforeWrite();
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('Total');
		$fields->removeByName('Token');
		$memberfield = $fields->fieldByName("Root.Main.MemberID");
		$fields->replaceField("MemberID", $memberfield->performReadonlyTransformation());
		if (class_exists('Payment')) {
			$fields->addFieldToTab('Root.Main', new ReadonlyField(
				'TotalNice', 'Total', $this->Total->Nice()
			));
			$paymentfield = $fields->fieldByName("Root.Main.PaymentID");
			$fields->replaceField("PaymentID", $paymentfield->performReadonlyTransformation());
		}
		$fields->fieldByName("Root.Attendees.Attendees")->getConfig()
			->removeComponentsByType("GridFieldAddNewButton")
			->removeComponentsByType("GridFieldAddExistingAutocompleter");

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
	 * @see EventRegistration::EventTitle()
	 */
	public function getTitle() {
		return $this->Name;
	}

	/**
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
	public function createAttendee(EventTicket $ticket){
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