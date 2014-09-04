<dl id="registration-details">
	<dt>Date:</dt><dd>$Created.Nice</dd>
	<% if Name %><dt>Name:</dt><dd>$Name</dd><% end_if %>
	<% if Email %><dt>Email:</dt><dd>$Email</dd><% end_if %>
	<dt>Event:</dt><dd><a href="$Time.Event.Link">$Time.Event.Title</a></dd>
	<dt>Status:</dt><dd>$Status</dd>
</dl>

<h3>Tickets</h3>
<table id="$ID" class="table $CSSClasses event-tickets field">
	<thead>
		<tr>
			<th>Ticket</th>
			<th>Price</th>
			<th>Quantity</th>
		</tr>
	</thead>
	<tbody>
		<% loop $Tickets %>
			<tr class="$EvenOdd $FirstLast <% if $Last %>last <% end_if %>">
				<td class="title">$Title</td>
				<td class="price"><% if Price %>$Price.Nice<% else %>Free<% end_if %></td>
				<td class="quantity">$Quantity</td>
			</tr>
			<% if $Description %>
				<tr class="event-tickets-description">
					<td colspan="5">$Description</td>
				</tr>
			<% end_if %>
		<% end_loop %>
	</tbody>
	<tfoot>
		<tr>
			<th>Total Cost</th>
			<td colspan="2"><% if Total %>$Total.Nice<% else %>Free<% end_if %></td>
		</tr>
	</tfoot>
</table>


<% if $Registration.Payment %>
	<% with $Registration.Payment %>
		<h3>Payment Details</h3>
		<dl id="payment-details">
			<dt>Method:</dt>
			<dd>$PaymentMethod</dd>
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