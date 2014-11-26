<dl id="registration-details">
	<dt>Date:</dt><dd>$Created.Nice</dd>
	<% if Name %><dt>Name:</dt><dd>$Name</dd><% end_if %>
	<% if Email %><dt>Email:</dt><dd>$Email</dd><% end_if %>
	<dt>Event:</dt><dd><a href="$Event.Link">$Event.Title</a></dd>
	<dt>Status:</dt><dd>$Status</dd>
</dl>

<% if HasBasicDetails %>
<%-- For cases where no attendee details are recorded, only ticket selections --%>
	<% if $Tickets %>
		<h3>Tickets</h3>
		<% include EventTicketsTable %>
	<% end_if %>
<% else %>
	<% if $Attendees %>
		<h3>Attendees</h3>
		<% include EventAttendeesTable %>
	<% end_if %>
<% end_if %>

<% if $Payment %>
	<% with $Payment %>
		<h3>Payment Details</h3>
		<dl id="payment-details">
			<dt>Method:</dt>
				<dd>$GatewayTitle</dd>
			<dt>Amount:</dt>
				<dd>$Amount.Nice</dd>
			<dt>Status:</dt>
				<dd>$Status</dd>
		</dl>
	<% end_with %>
<% end_if %>

<% if $HasTicketFile %>
	<% with $Registration %>
		<% if $Status = "Valid" %>
			<h3>Ticket File</h3>
			<p><a href="$Top.Link("ticketfile")">Download ticket file.</a></p>
		<% end_if %>
	<% end_with %>
<% end_if %>