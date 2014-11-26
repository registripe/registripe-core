<table id="$ID" class="table $CSSClasses event-tickets field">
	<thead>
		<tr>
			<th>FirstName</th>
			<th>Surname</th>
			<th>Email</th>
			<th>Ticket</th>
			<th>Cost</th>
		</tr>
	</thead>
	<tbody>
		<% loop $Attendees %>
			<tr class="$EvenOdd $FirstLast <% if $Last %>last <% end_if %>">
				<td class="firstname">$FirstName</td>
				<td class="surname">$Surname</td>
				<td class="surname">$Email</td>
				<td class="ticket">$Ticket.Title</td>
				<td class="cost">
					<% if Ticket.Price %>
						$Ticket.Price.Nice
					<% else %>
						Free
					<% end_if %>
				</td>
			</tr>
			<% if $Ticket.Description %>
				<tr class="event-tickets-description">
					<td colspan="5">$Ticket.Description</td>
				</tr>
			<% end_if %>
		<% end_loop %>
	</tbody>
	<tfoot>
		<tr>
			<th>Total Cost</th>
			<td colspan="2"><% if Total %>$Total.Nicer<% end_if %></td>
		</tr>
	</tfoot>
</table>