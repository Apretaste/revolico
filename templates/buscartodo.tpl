<table width="100%">
	<tr>
		<td align="right">{button caption="Comprar en Apretaste" size="small" href="MERCADO"}</td>
	</tr>
</table>

{foreach from=$items item=item name=tienda}
	<table width="100%">
		<tr>
			<td>
				{if $item->price neq 0 AND $item->price neq ""}
					<font color="#5EBB47">${$item->price|number_format} {$item->currency}</font>
					{separator}
				{/if}

				{link href="TIENDA VER {$item->id}" caption="{$item->ad_title|capitalize|truncate:55:' ...'}"}
			</td>
		</tr>
		<tr>
			<td>
				{$item->ad_body|truncate:150:' ...'}
			</td>
		</tr>
		<tr>
			<td>
				<small><font color="gray">
				{if $item->contact_email_1 neq ""}
					{$item->contact_email_1}{separator}
				{elseif $item->contact_email_2 neq ""}
					{$item->contact_email_2}{separator}
				{elseif $item->contact_email_3 neq ""}
					{$item->contact_email_3}{separator}
				{/if}

				{if $item->contact_cellphone neq ""}
					{$item->contact_cellphone|cuba_phone_format}{separator}
				{elseif $item->contact_phone neq ""}
					{$item->contact_phone|cuba_phone_format}{separator}
				{/if}

				{$item->date_time_posted|date_format:"%d/%m/%Y"}

				{if $item->number_of_pictures gt 0}
					{separator}
					{$item->number_of_pictures} foto{if $item->number_of_pictures gt 1}s{/if}
				{/if}
				</font></small>
			</td>
		</tr>
	</table>

	{if not $smarty.foreach.tienda.last}
		{space5}
		{hr}
		{space5}
	{/if}
{/foreach}