<table id="$ID" class="table $CSSClasses event-tickets field">
	<thead>
		<tr>
			<th>FirstName</th>
			<th>Surname</th>
			<th>Email</th>
			<th>Ticket</th>
		</tr>
	</thead>
	<tbody>
		<% loop $Attendees %>
			<tr class="$EvenOdd $FirstLast <% if $Last %>last <% end_if %>">
				<td class="firstname">$FirstName</td>
				<td class="surname">$Surname</td>
				<td class="surname">$Email</td>
				<td class="ticket">$Ticket.Title <% if Ticket.Price %> - $Ticket.Price.Nice<% end_if %></td>
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