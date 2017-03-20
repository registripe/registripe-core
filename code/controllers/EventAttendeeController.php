<?php

class EventAttendeeController extends Page_Controller{

	public static $allowed_actions = array(
		'add',
		'edit',
		'delete',
		'AttendeeForm'
	);

	protected $registration;

	public function __construct($record, EventRegistration $registration){
		parent::__construct($record);
		$this->registration = $registration;
	}

	public function index($request) {
		return $this->add($request);
	}

	/**
	 * Add action renders the add attendee form.
	 * @param HTTPRequest $request
	 * @return array
	 */
	public function add($request) {
		$tickets = $this->registration->Event()->getAvailableTickets();
		$form = $this->AttendeeForm();
		// check tickets are actually available
		if (!$tickets->count()) {
			return $this->redirect($this->BackURL);
		}
		$attendee = $this->createAttendee();
		// ticket selection in url
		$ticket = $tickets->byID((int)$request->param('ID'));
		if($ticket && !$ticket->exists()){
			$attendee->TicketID = $ticket->ID;
			$form->setAllowedTickets(
				$this->registration->Event()->getAvailableTickets()
			);
		}
		$form->loadDataFrom($attendee);
		if($ticket) {
			$form->loadDataFrom(array(
				"TicketID" => $ticket->ID
			));
		}else{
			$form->setAllowedTickets(
				$this->registration->Event()->getAvailableTickets()
			);
		}
		//automatically populate from previous attendee
		if($prepops = EventAttendee::config()->prepopulated_fields){
			$latestattendee = $this->registration->Attendees()
				->sort("LastEdited", "DESC")->first();
			if($latestattendee){
				$form->loadDataFrom($latestattendee, 0, $prepops);	
			}
		}
		$this->extend("onAdd", $form, $this->registration);
		return array(
			'Title' => $ticket ? $ticket->Title : null,
			'Form' => $form
		);
	}

	/**
	 * Edit action renders the attendee form, populated with existing details.
	 * @param HTTPRequest $request
	 * @return array
	 */
	public function edit($request) {
		//get attendee from registration
		$attendee = $this->registration->Attendees()
			->byID($request->param('ID'));
		if(!$attendee) {
			return $this->httpError(404, "Attendee not found");
		}
		$form = $this->AttendeeForm();
		//add tickets dropdown, if there is no selected ticket
		$ticket = $attendee->Ticket();
		if(!$ticket->exists()){
			$form->setAllowedTickets(
				$this->registration->Event()->getAvailableTickets()
			);
		}
		$form->loadDataFrom($attendee);
		//add tickets dropdown, if there is no selected ticket
		$form->getValidator()->addRequiredField("ID");
		$this->extend("onEdit", $form, $attendee, $this->registration);
		return array(
			'Title' => $attendee->Ticket()->Title,
			'Form' => $form
		);
	}

	/**
	 * Create the EventAttendeeForm for adding/editing records
	 * @return EventAttendeeForm
	 */
	public function AttendeeForm() {
		$form = new EventAttendeeForm($this, "AttendeeForm");
		$this->extend("updateAttendeeForm", $form, $this->registration);
		$form->addCancelLink($this->BackURL);
		return $form;
	}

	/**
	 * Save new and edited attendees
	 * @param  array $data
	 * @param  Form $form
	 * @return HTTPResponse
	 */
	public function save($data, $form) {
		//look for attendee id in form field or request params
		$attendeeid = $form->Fields()->fieldByName("ID")->dataValue();
		//look for existing attendee
		$attendee = $this->registration->Attendees()
			->byID((int)$attendeeid);
		//prevent changes to the type of ticket
		if($attendee && $attendee->TicketID && $attendee->TicketID != $data['TicketID']){
			$form->sessionMessage('You cannot change the ticket', 'bad');
			return $this->redirectBack();
		}
		//create new attendee
		if(!$attendee){
			$attendee = $this->createAttendee();
		}
		//save ticket selection
		$form->saveInto($attendee);
		$attendee->write();

		$this->registration->calculateTotal();
		$this->registration->write();

		$this->extend("onSave", $attendee, $this->registration);
		
		return $this->redirect($this->NextURL);
	}
	
	/**
	 * Delete action
	 * @param  HTTPRequest $request
	 * @return HTTPResponse
	 */
	public function delete($request) {
		//get attendee from registration
		$attendee = $this->registration->Attendees()
			->byID($request->param('ID'));
		if(!$attendee) {
			return $this->httpError(404, "Attendee not found");
		}elseif($this->registration->Attendees()->count() <= 1){
			//there must be at least one attendee
		}else{
			$attendee->delete();
		}
		return $this->redirect($this->BackURL);
	}

	/**
	 * Helper for creating new attendee on registration.
	 */
	protected function createAttendee() {
		$attendee = EventAttendee::create();
		$attendee->RegistrationID = $this->registration->ID;
		return $attendee;
	}

}