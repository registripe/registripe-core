<table id="$ID" class="table $CSSClasses event-tickets field">
	<thead>
		<tr>
			<th>Ticket</th>
			<th>Price</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<% loop $Tickets %>
			<tr class="$EvenOdd $FirstLast <% if $Last %>last <% end_if %>">
				<td class="title">$Title</td>
				<td class="price"><% if Price %>$Price.Nice<% else %>Free<% end_if %></td>
				<td>
					<% if isAvailable %>
						<a href="{$Up.Link}register/attendee/add/$ID" class="btn btn-primary">
							Add Ticket
						</a>
					<% else %>
						$AvailabilityReason
					<% end_if %>
				</td>
			</tr>
			<% if $Description %>
				<tr class="event-tickets-description">
					<td colspan="5">$Description</td>
				</tr>
			<% end_if %>
		<% end_loop %>
	</tbody>
</table>

$NextLink