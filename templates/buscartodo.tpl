<h1>{$searchQuery|lower|capitalize}</h1>

{space5}

<table width="100%" cellpadding="10" cellspacing="0">
{foreach from=$items item=item name=tienda}
	<tr>
		<td bgcolor="{cycle values="#f2f2f2,white"}">
			<!--Amount-->
			{if $item->price neq 0 AND $item->price neq ""}
				<font color="#5EBB47">${$item->price|number_format} {$item->currency}</font>
				{separator}
			{/if}

			<!--title-->
			{link href="REVOLICO VER {$item->id}" caption="{$item->ad_title|capitalize|truncate:55:' ...'}"}

			<br/>

			<!--description-->
			{$item->ad_body|truncate:150:' ...'}

			<br/>

			<!--Emails-->
			<small><font color="gray">
			{if $item->contact_email_1 neq ""}
				{$item->contact_email_1}{separator}
			{elseif $item->contact_email_2 neq ""}
				{$item->contact_email_2}{separator}
			{elseif $item->contact_email_3 neq ""}
				{$item->contact_email_3}{separator}
			{/if}

			<!--Phones-->
			{if $item->contact_cellphone neq ""}
				{$item->contact_cellphone|cuba_phone_format}{separator}
			{elseif $item->contact_phone neq ""}
				{$item->contact_phone|cuba_phone_format}{separator}
			{/if}

			<!--Date posted-->
			{$item->date_time_posted|date_format:"%d/%m/%Y"}

			<!--Number of pictures-->
			{if $item->number_of_pictures gt 0}
				{separator}
				{$item->number_of_pictures} foto{if $item->number_of_pictures gt 1}s{/if}
			{/if}
			</font></small>
		</td>
	</tr>
{/foreach}
</table>
