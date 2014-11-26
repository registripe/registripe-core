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
	
	/**
	 * Add action
	 * @param HTTPRequest $request
	 * @return array
	 */
	public function add($request) {
		$form = $this->AttendeeForm();
		$ticket = $this->registration->Event()->Tickets()
						->byID((int)$request->param('ID'));
		if($ticket) {
			$form->loadDataFrom(array(
				"TicketID" => $ticket->ID
			));
		}else{
			$form->setAllowedTickets(
				$this->registration->Event()->getAvailableTickets()
			);
		}

		return array(
			'Title' => $ticket ? $ticket->Title : null,
			'Form' => $form
		);
	}

	/**
	 * Edit action
	 * @param HTTPRequest $request
	 * @return array
	 */
	public function edit($request) {
		//get attendee from registration
		$attendee = $this->registration->Attendees()
			->byID($request->param('ID'));
		if(!$attendee) {
			return $this->httpError(400, "Attendee not found");
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
		$form->addCancelLink($this->BackURL);
		$form->getValidator()->addRequiredField("ID");

		return array(
			'Title' => $attendee->Ticket()->Title,
			'Form' => $form
		);
	}

	/**
	 * Create the EventAttendeeForm for adding/editing records
	 */
	public function AttendeeForm() {
		$form = new EventAttendeeForm($this, "AttendeeForm");
		$this->extend("updateAttendeeForm", $form);

		return $form;
	}

	/**
	 * Save new and edited attendees
	 * @param  array $data
	 * @param  Form $form
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
		//create attendee
		if(!$attendee){
			$attendee = new EventAttendee();
			$attendee->RegistrationID = $this->registration->ID;
		}
		//save ticket selection
		$form->saveInto($attendee);
		$attendee->write();

		$this->registration->calculateTotal();
		$this->registration->write();
		
		return $this->redirect($this->NextURL);
	}
	
	/**
	 * Delete action
	 * @param  HTTPRequest $request
	 */
	public function delete($request) {
		//get attendee from registration
		$attendee = $this->registration->Attendees()
			->byID($request->param('ID'));
		if(!$attendee) {
			return $this->httpError(400, "Attendee not found");
		}elseif($this->registration->Attendees()->count() <= 1){
			//there must be at least one attendee
		}else{
			$attendee->delete();
		}

		return $this->redirect($this->BackURL);
	}

}