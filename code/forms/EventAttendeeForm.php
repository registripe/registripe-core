<?php

/**
 * Event Attendee Form
 * For adding and editing attendees
 */
class EventAttendeeForm extends Form{

	public function __construct($controller, $name = "EventAttendeeForm") {
		$fields = singleton("EventAttendee")
					->getFrontEndFields();
		//hide the ticket id by default (can be re-enabled using setAllowedTickets)
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
		$validator = new EventAttendeeFormValidator(
			"FirstName", "Surname", "Email", "TicketID"
		);

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
		$cancellink = new LiteralField("cancellink",
			sprintf("<a href=\"%s\">%s</a>", $url, $label)
		);
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
	public function setAllowedTickets(DataList $tickets) {
		$this->fields->removeByName("TicketID");
		$this->fields->unshift(
			new DropdownField("TicketID", "Ticket",
				$tickets->map()->toArray()
			)
		);
	}

}

class EventAttendeeFormValidator extends RequiredFields{

	

}