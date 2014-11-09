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

		//configure error messages
		//test strings are stored in lolcats lang
		i18n::set_locale('lc_XX');

		//configure controller
		$controller = new EventRegisterFormTest_Controller();
		$datetime = $this->objFromFixture('RegistrableDateTime', 'datetime');
		$controller->datetime = $datetime;

		//configure register form
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
				return $error['message'];
			}
		}
		Session::clear("FormInfo.{$form->FormName()}");
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