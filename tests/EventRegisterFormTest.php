<?php
/**
 * Tests for {@link EventRegisterForm}.
 *
 * @package    silverstripe-eventmanagement
 * @subpackage tests
 */
class EventRegisterFormTest extends SapphireTest {

	public static $fixture_file = 'eventmanagement/tests/EventRegisterFormTest.yml';

	public function testValidateTickets() {
		$controller = new EventRegisterFormTest_Controller();
		$datetime = $this->objFromFixture('RegistrableDateTime', 'datetime');
		$controller->datetime = $datetime;

		$form      = new EventRegisterForm($controller, 'Form');
		$ended     = $this->idFromFixture('EventTicket', 'ended');
		$minmax    = $this->idFromFixture('EventTicket', 'minmax');
		$quantity  = $this->idFromFixture('EventTicket', 'quantity');
		$unlimited = $this->idFromFixture('EventTicket', 'unlimited');

		$datetime->Tickets()->add($quantity, array('Available' => 10));

		// Check it validates we enter at least one ticket.
		$this->assertFalse($form->validateTickets(array(), $form));
		$this->assertEquals('no_tickets', $this->getTicketsError($form));

		// Check that at least one ticket quantity is valid.
		$this->assertFalse($form->validateTickets(array(1 => 'a', 2 => 1), $form));
		$this->assertEquals('non_numeric', $this->getTicketsError($form));

		// Check only valid ticket IDs are allowed.
		$this->assertFalse($form->validateTickets(array(-1 => 1), $form));
		$this->assertEquals('invalid_id', $this->getTicketsError($form));

		// Check expired tickets cannot be registered
		$this->assertFalse($form->validateTickets(array($ended => 1), $form));
		$this->assertEquals('not_available', $this->getTicketsError($form));

		// Valid tickets
		$this->assertTrue((bool)$form->validateTickets(array($quantity => 1), $form));
		$this->assertNull($this->getTicketsError($form));

		// Check we cannot book over the available quantity of tickets.
		$this->assertTrue($form->validateTickets(array($quantity => 1), $form));

		$this->assertFalse($form->validateTickets(array($quantity => 11), $form));
		$this->assertEquals('over_quantity', $this->getTicketsError($form));

		// Check the number of tickets booked must be within the allowed range.
		$this->assertTrue($form->validateTickets(array($minmax => 8), $form));

		$this->assertFalse($form->validateTickets(array($minmax => 4), $form));
		$this->assertEquals('too_few', $this->getTicketsError($form));

		$this->assertFalse($form->validateTickets(array($minmax => 11), $form));
		$this->assertEquals('too_many', $this->getTicketsError($form));

		// Check we cannot exceed the overall event capacity.
		$this->assertTrue($form->validateTickets(array($unlimited => 1000), $form));
		$this->assertFalse($form->validateTickets(array($unlimited => 1001), $form));
		$this->assertEquals('over_capacity', $this->getTicketsError($form));
	}

	/**
	 * Helper for checking form errors
	 * @param  Form   $form
	 * @return string|null
	 */
	protected function getTicketsError(Form $form) {
		$errors = Session::get("FormInfo.{$form->FormName()}.errors");

		if ($errors) foreach ($errors as $error) {
			if ($error['fieldName'] == 'Tickets') {
				Session::clear("FormInfo.{$form->FormName()}");
				return $this->getErrorTypeForMessage($error['message']);
			}
		}
		Session::clear("FormInfo.{$form->FormName()}");
	}

	/**
	 * Helper for converting error message into an error code
	 * @param  Message to convert
	 * @return string|null
	 */
	protected function getErrorTypeForMessage($message) {
		$static = array(
			'no_tickets'  => 'Please select at least one ticket to purchase.',
			'non_numeric' => 'Please only enter numerical amounts for ticket quantities.',
			'invalid_id'  => 'An invalid ticket ID was entered.');

		$static = array_flip($static);
		if (array_key_exists($message, $static)) {
			return $static[$message];
		}

		$regex = array(
			'not_available' => '/.+? is currently not available./',
			'over_quantity' => '/There are only [0-9]+ of "[^"]+" available./',
			'too_few'       => '/You must purchase at least [0-9]+ of "[^"]+"./',
			'too_many'      => '/You can only purchase at most [0-9]+ of "[^"]+"./');

		foreach ($regex as $name => $pattern) {
			if (preg_match($pattern, $message)) return $name;
		}

		if (strpos($message, 'The event only has') === 0) {
			return 'over_capacity';
		}

		 //return the given message to help with debugging
		return $message;
	}

}

/**
 * @ignore
 */
class EventRegisterFormTest_Controller extends Controller {

	public $datetime;

	public function getDateTime() {
		return $this->datetime;
	}

}