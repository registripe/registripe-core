<?php

class EventRegistrationHtmlTicketGenerator implements EventRegistrationTicketGenerator {

	/**
	 * Returns a human-readable name for the ticket generator.
	 *
	 * @return string
	 */
	public function getGeneratorTitle() {
		return 'HTML Ticket';
	}

	/**
	 * Returns the file name the generated ticket file should have.
	 *
	 * @param  EventRegistration $registration
	 * @return string
	 */
	public function getTicketFilenameFor(EventRegistration $registration) {
		return null;
	}

	/**
	 * Returns the mime type that the generated ticket file for a registration
	 * should have.
	 *
	 * @param  EventRegistration $registration
	 * @return string
	 */
	public function getTicketMimeTypeFor(EventRegistration $registration) {
		return null;
	}

	/**
	 * Generates a ticket file for a registration, and returns the path to the
	 * ticket.
	 *
	 * NOTE: The ticket generator is responsible for caching the result.
	 *
	 * @param  EventRegistration $registration
	 * @return string The path to the generated file.
	 */
	public function generateTicketFileFor(EventRegistration $registration) {
		return null;
	}
}