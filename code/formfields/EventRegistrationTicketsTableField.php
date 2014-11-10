<?php
/**
 * A table that allows a user to select the tickets to register for, as well as
 * displaying messages for tickets that are unavailable.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationTicketsTableField extends FormField {

	protected $tickets;
	protected $excludedRegistrationId;
	protected $showUnavailableTickets = true;
	protected $showUnselectedTickets = true;
	protected $forceTotalRow;
	protected $total;

	public function __construct($name, DataList $tickets, $value = array()) {
		$this->tickets = $tickets;
		parent::__construct($name, '', $value);
	}

	public function Field($properties = array()) {
		return $this->renderWith('EventRegistrationTicketsTableField', $properties);
	}

	/**
	 * @return array
	 */
	public function dataValue() {
		return (array) $this->value;
	}

	/**
	 * @param array|object $value
	 */
	public function setValue($value) {
		if (is_object($value)) {
			$value = $value->map('ID', 'Quantity');
		}

		parent::setValue($value);
	}

	/**
	 * Sets a registration ID to exclude from any availibility calculations.
	 *
	 * @param int $id
	 */
	public function setExcludedRegistrationId($id) {
		$this->excludedRegistrationId = $id;
	}

	/**
	 * @param bool $bool
	 */
	public function setShowUnavailableTickets($bool) {
		$this->showUnavailableTickets = $bool;
	}

	/**
	 * @param bool $bool
	 */
	public function setShowUnselectedTickets($bool) {
		$this->showUnselectedTickets = $bool;
	}

	/**
	 * @param bool $bool
	 */
	public function setForceTotalRow($bool) {
		$this->forceTotalRow = $bool;
	}

	/**
	 * @param Money $money
	 */
	public function setTotal(Money $money) {
		$this->total = $money;
	}

	/**
	 * @return EventRegistrationTicketsTableField
	 */
	public function performReadonlyTransformation() {
		$table = clone $this;
		$table->setReadonly(true);
		return $table;
	}

	public function Tickets() {
		$result  = new ArrayList();
		$tickets = $this->tickets;

		foreach ($tickets as $ticket) {
			$available = $ticket->getAvailability(
				$this->excludedRegistrationId
			);

			if ($avail = $available['available']) {
				$name = "{$this->name}[{$ticket->ID}]";
				$min  = $ticket->MinTickets;
				$max  = $ticket->MaxTickets;

				$val = array_key_exists($ticket->ID, $this->value)
					? $this->value[$ticket->ID] : null;

				if (!$val && !$this->showUnselectedTickets) {
					continue;
				}

				if ($this->readonly) {
					$field = $val ? $val : '0';
				} elseif ($max) {
					$field = new DropdownField(
						$name, '',
						ArrayLib::valuekey(range($min, min($available, $max))),
						$val, null, true);
				} else {
					$field = new NumericField($name, '', $val);
				}

				$result->push(new ArrayData(array(
					'Title'       => $ticket->Title,
					'Description' => $ticket->Description,
					'Available'   => $avail === true ? 'Unlimited' : $avail,
					'Price'       => $ticket->PriceSummary(),
					'End'         => DBField::create_field('SS_Datetime', strtotime($ticket->EndDate)),
					'Quantity'    => $field,
					'Ticket'	=> $ticket
				)));
			} elseif ($this->showUnavailableTickets) {
				$availableAt = null;

				if (array_key_exists('available_at', $available)) {
					$availableAt = DBField::create_field('SS_Datetime', $available['available_at']);
				}

				$result->push(new ArrayData(array(
					'Title'       => $ticket->Title,
					'Description' => $ticket->Description,
					'Available'   => false,
					'Reason'      => $available['reason'],
					'AvailableAt' => $availableAt
				)));
			}
		}

		$this->extend('updateTickets', $result);

		return $result;
	}

	/**
	 * @return bool
	 */
	public function ShowTotalRow() {
		return $this->forceTotalRow || ($this->readonly && $this->Total() && $this->Total()->exists());
	}

	/**
	 * @return Money
	 */
	public function Total() {
		return $this->total;
	}


	public function validate($validator) {

		if($this->readonly){
			return true;
		}

		// check there is at least one ticket selected
		if(empty($this->value) || !is_array($this->value) || !array_filter($this->value)){
			$validator->validationError($this->name,
				_t('EventRegistrationTicketsTableField.SELECTATLEASTONE',
					'Please add a quantity to at least one ticket.'),
				"validation"
			);
			return false;
		}
		
		foreach($this->value as $id => $quantity) {
			//numeric quantities
			if ($quantity && !is_int($quantity) && !ctype_digit($quantity)) {
				$validator->validationError($this->name,
					_t('EventRegistrationTicketsTableField.NONNUMERICALQUANTITY',
						'Please only enter numerical amounts for ticket quantities.'),
					'validation'
				);
				return false;
			}
			//valid ticket ids
			if (!$this->tickets->byID($id)) {
				$validator->validationError($this->name,
					_t('EventRegistrationTicketsTableField.INVALIDTICKETID',
						'Invalid ticket selection.'),
					'validation'
				);
				return false;
			}
		}

		return true;
	}

}