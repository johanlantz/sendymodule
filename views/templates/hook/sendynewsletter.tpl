<!-- Sendy Newsletter module-->
{if ($page_name =='index') ||  ($page_name =='product') ||  ($page_name =='category')}
{* if {$lang_iso} == "es" *}
<div id="sendy_newsletter" class="">
	<span id="newsletter_span1" class="newsletter_element"><img src="{$modules_dir}sendynewsletter/img/{$lang_iso}/newsletter-signup-products-img.jpg"></span>
	<span id="newsletter_span2" class="newsletter_element"><img height="150" width="286" src="{$modules_dir}sendynewsletter/img/{$lang_iso}/newsletter-signup-subscribe-img-retina.png"></span>
	<span id="newsletter_span3" class="newsletter_element"><img src="{$modules_dir}sendynewsletter/img/{$lang_iso}/newsletter-signup-mail-img.jpg"></span>
	
	<span class="newsletter_element">
		<form id="sendynewsletter_form" style="display:inline;" action="{$sendynews.url}/subscribe" method="post">
			<input type="hidden" id="sendynewsletter_list" name="list" value="{$sendynews.list}" />
			<input type="hidden" id="sendynewsletter_ip" name="{if $sendynews.ip == 1}{$sendynews.ipfield}{else}ip{/if}" value="{$sendynews.ipval}" />
			{if $sendynews.name == 1}
			<input id="sendynewsletter_name" type="text" name="name" placeholder="{l s='Your name' mod='sendynewsletter'}" {if $sendynews.namereq == 1}data-req="true" required{/if}/>
			{/if}

			{* Add honeypot support for sendy 3.x *}
			<div style="display:none;">
				<label for="hp">HP</label><br/>
				<input type="text" name="hp" id="hp"/>
			</div>

			<input id="sendynewsletter_email" type="text" name="email" class="account_input" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="{l s='Your email address' mod='sendynewsletter'}" required/>
			<input type="submit" value="ok" class="button_large"  name="submitNewsletter" />
		</form>
		<p id="sn_error" class="sn_warning">{l s='There was an error please try again.' mod='sendynewsletter'}</p>
		<p id="sn_email" class="sn_warning">{l s='Invalid email address.' mod='sendynewsletter'}</p>
		<p id="sn_subscribed" class="sn_warning">{l s='Already subscribed.' mod='sendynewsletter'}</p>
		<p id="sn_name" class="sn_warning">{l s='Please enter your name.' mod='sendynewsletter'}</p>
		<p class="sn_success">{l s='Subscription successful.' mod='sendynewsletter'}</p>
	</span>
</div>
{* /if *}
{/if}
<!-- /Sendy Newsletter module-->