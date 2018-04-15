<!-- Sendy Newsletter module-->
{if ($sendynews.activeOnPages|strstr:$page.page_name) }
	{if {$sendynews.list|count_characters} > 1}
		<div id="sendy_newsletter" class="">
			<span id="newsletter_span1" class="newsletter_element"><img height="150" width="188" src="{$urls.base_url}modules/sendyintegration/img/sendy-newsletter-signup-products.jpg"></span>
			<span id="newsletter_span2" class="newsletter_element"><img src="{$urls.base_url}modules/sendyintegration/img/{$language.iso_code}/sendy-newsletter-signup-subscribe.png"></span>
			<span id="newsletter_span3" class="newsletter_element"><img height="150" width="229" src="{$urls.base_url}modules/sendyintegration/img/sendy-newsletter-signup-mail.png"></span>

			<span class="newsletter_element">
				<form id="sendynewsletter_form" style="display:inline;" action="{$sendynews.url}/subscribe" method="post">
					<input type="hidden" id="sendynewsletter_list" name="list" value="{$sendynews.list}" />
					{if $sendynews.ip == 1}
						<input type="hidden" id="sendynewsletter_ip" name="ipaddress" value="{$sendynews.ipval}" />
					{/if}
					{if $sendynews.name == 1}
					<input id="sendynewsletter_name" type="text" name="name" placeholder="{l s='Your name' mod='sendynewsletter'}" {if $sendynews.namereq == 1}data-req="true" required{/if}/>
					{/if}

					{* Add honeypot *}
					<div style="display:none;">
						<label for="xip">xip</label><br/>
						<input type="text" name="xip" id="xip"/>
					</div>

					<input id="sendynewsletter_email" type="text" name="email" class="account_input" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="{l s='Your email address' mod='sendynewsletter'}" required/>
					<input type="submit" value="ok" class="button_large"  name="submitNewsletter" />
				</form>

				{if $sendynews.showInfo}
					{* Bootstrap tooltip for GDPR *}
					<script>
						$(document).ready(function(){
							$('[data-toggle="tooltip"]').tooltip(); 
						});
					</script>
					<i class="icon icon-info-circle" data-toggle="tooltip" title="{l s='You can unsubscribe at any time' mod='sendyintegration'}"></i>
					<script type="text/javascript">(function () { $('[type=data-toggle]').tooltip(); }());</script>
				{/if}

				<p id="sn_error" class="sn_warning">{l s='There was an error please try again.' mod='sendynewsletter'}</p>
				<p id="sn_email" class="sn_warning">{l s='Invalid email address.' mod='sendynewsletter'}</p>
				<p id="sn_subscribed" class="sn_warning">{l s='Already subscribed.' mod='sendynewsletter'}</p>
				<p id="sn_name" class="sn_warning">{l s='Please enter your name.' mod='sendynewsletter'}</p>
				<p class="sn_success">{l s='Subscription successful.' mod='sendynewsletter'}</p>
			</span>
		</div>
	{/if}
{/if}
<!-- /Sendy Newsletter module-->