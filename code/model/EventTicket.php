<?php
/**
 * A ticket that can be attached to a registrable event. Each ticket can
 * have a specific quantity available for each event time.
 *
 * @package registripe
 */
class EventTicket extends DataObject {

	private static $db = array(
		'Title'       => 'Varchar(255)',
		'Price'       => 'Currency',
		'Description' => 'Text',
		'StartDate'   => 'SS_Datetime',
		'EndDate'     => 'SS_Datetime'
	);

	private static $has_one = array(
		'Event' => 'RegistrableEvent'
	);

	private static $has_many = array(
		'Attendees' => 'EventAttendee'
	);

	private static $summary_fields = array(
		'Title'        => 'Title',
		'StartSummary' => 'Sales Start',
		'EndSummary' => 'Sales End',
		'PriceSummary' => 'Price'
	);

	private static $searchable_fields = array(
		'Title'
	);

	private static $singular_name = "Ticket";
	private static $plural_name = "Tickets";

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('EventID');
		$fields->removeByName('Attendees');

		foreach (array('Start', 'End') as $type) {
			$fields->addFieldsToTab('Root.Main', 
				$dateTime = new DatetimeField("{$type}Date", "{$type} Date / Time")
			);
			$dateTime->getDateField()->setConfig('showcalendar', true);
			$dateTime->getTimeField()->setConfig('showdropdown', true);
		}

		return $fields;
	}

	/**
	 * @return RequiredFields
	 */
	public function getValidator() {
		return new RequiredFields('Title');
	}

	public function populateDefaults() {
		$this->StartDate = date('Y-m-d H:i:s');
		parent::populateDefaults();
	}

	/**
	 * Get the attendees that have booked this ticket.
	 * @return DataList
	 */
	public function getBookedAttendees() {
		return $this->Attendees()
			->innerJoin("EventRegistration", "EventRegistration.ID = EventAttendee.EventRegistrationID")
			->filter("Status:not", "Canceled");
	}

	public function isAvailable() {
		$availability = $this->getAvailability();
		return (bool)$availability['available'];
	}

	public function getAvailabilityReason(){
		$availability = $this->getAvailability();
		if(isset($availability['reason'])){
			return $availability['reason'];
		}
	}

	/**
	 * Returns the number of tickets available for an event time.
	 *
	 * @param  int $excludeId A registration ID to exclude from calculations.
	 * @return array
	 */
	public function getAvailability($excludeId = null) {
		$start = strtotime($this->StartDate);
		if ($start >= time()) {
			return array(
				'available'    => false,
				'reason'       => 'Tickets are not yet available.',
				'available_at' => $start);
		}
		$end = strtotime($this->EndDate);
		if (time() >= $end) {
			return array(
				'available' => false,
				'reason'    => 'Tickets are no longer available.');
		}
		if (!$quantity = $this->Available) {
			return array(
				'available' => true
			);
		}
		$bookings = $this->getBookedAttendees();
		if ($excludeId) {
			$bookings = $bookings->filter('EventRegistration.ID:not', $excludeId);
		}
		$bookedcount = $bookings->count();
		if ($bookedcount >= $quantity) {
			return array(
				'available' => false,
				'reason'    => 'All tickets have been booked.');
		}
		return array(
			'available' => $quantity - $bookedcount
		);
	}

	/**
	 * @return string
	 */
	public function StartSummary() {
		return $this->obj('StartDate')->Nice();
	}

	/**
	 * @return string
	 */
	public function EndSummary() {
		return $this->obj('EndDate')->Nice();
	}

	/**
	 * @return string
	 */
	public function PriceSummary() {
		return $this->obj('Price')->Nicer();
	}

	/**
	 * Check if this ticket has a price
	 * @return boolean
	 */
	public function hasPrice(){
		return $this->Price > 0;
	}

	/**
	 * Check if this ticket is a free one.
	 * @return boolean
	 */
	public function isFree(){
		return $this->Price <= 0;
	}

	/**
	 * @return string
	 */
	public function Summary() {
		$summary = "{$this->Title} ({$this->PriceSummary()})";
		return $summary . ($this->Available ? " ($this->Available available)" : '');
	}

	public function canEdit($member = null) {
		return $this->Event()->canEdit($member);
	}

	public function canCreate($member = null) {
		return $this->Event()->canCreate($member);
	}

	public function canDelete($member = null) {
		return $this->Event()->canDelete($member);
	}

	public function canView($member = null) {
		return $this->Event()->canView($member);
	}
}
