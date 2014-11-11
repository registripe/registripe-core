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
				<td class="quantity">$Attendees.filter(RegistrationID,$Up.ID).Count</td>
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