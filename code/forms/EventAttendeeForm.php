<?php

/**
 * Event Attendee Form
 * For adding and editing attendees
 */
class EventAttendeeForm extends Form{

	public function __construct($controller, $name = "EventAttendeeForm") {
		$fields = singleton("EventAttendee")
					->getFrontEndFields();
		//hide the ticketd field by default (can be re-introduced using setAllowedTickets)
		$fields->push(
			new HiddenField("TicketID")
		);
		//store the attendee id for editing
		$fields->push(
			new HiddenField("ID")
		);
		$actions = new FieldList(
			new FormAction("save", "Next Step")
		);
		//default required fields are configurable
		$required = EventAttendee::config()->required_fields;
		if(!$required){
			$required = array("FirstName", "Surname", "Email");
		}
		$required[] = "TicketID"; //ticket is always required
		$validator = new RequiredFields($required);

		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->extend("updateForm", $this);
	}

	/**
	 * Add a link to "go back"
	 * @param string $url url to link to
	 * @param string $label
	 */
	public function addCancelLink($url, $label = "Cancel") {
		$this->actions->removeByname("cancellink");
		$cancellink = new AnchorField("cancellink", $label, $url);
		$this->actions->unshift($cancellink);
	}

	public function hideTicketField() {
		$this->fields->push(
			new HiddenField("TicketID")
		);
	}

	/**
	 * Remove the ticket hidden field, and add a dropdown containing
	 * the available tickets.
	 * @param DataList $tickets 
	 */
	public function setAllowedTickets(SS_List $tickets) {
		$this->fields->removeByName("TicketID");
		$this->fields->unshift(
			new DropdownField("TicketID", "Ticket",
				$tickets->toArray()
			)
		);
	}

}
