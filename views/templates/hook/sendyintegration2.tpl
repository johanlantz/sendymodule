{* 
/**
 * @author Givensa
 * @copyright  Givensa Home and Design S.L
 * @license  Commercial closed source
 * @version  Release: $Revision$
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
 *}

{* The recaptcha script could potentially go to a general header or footer *}
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
function onSubmitNewsletterSignup(token) {
	document.getElementById("submit_oneseven").disabled = false;
}
</script>

<!-- Sendy Newsletter module-->
{if ($sendynews.activeOnPages|strstr:$page.page_name) }
	{if {$sendynews.list|count_characters} > 1}
		<div id="sendy_newsletter">
			<div class="d-none d-lg-block"><img height="150" width="188" src="{$urls.base_url}modules/sendyintegration/views/img/sendy-newsletter-signup-products.jpg"></div>
			<div class=""><img height="150" width="286" src="{$urls.base_url}modules/sendyintegration/views/img/{$language.iso_code}/sendy-newsletter-signup-subscribe.png"></div>
			<div class="d-none d-lg-block"><img height="150" width="229" src="{$urls.base_url}modules/sendyintegration/views/img/sendy-newsletter-signup-mail.png"></div>

			<div class="">
				<form id="sendynewsletter_form" style="display:inline;" action="{$sendynews.url}/subscribe" method="post">
					<input type="hidden" id="sendynewsletter_list" name="list" value="{$sendynews.list}" />
					{if $sendynews.ip == 1}
						<input type="hidden" id="sendynewsletter_ip" name="ipaddress" value="{$sendynews.ipval}" />
					{/if}
					{if $sendynews.name == 1}
					<input id="sendynewsletter_name_oneseven" type="text" name="name" placeholder="{l s='Your name' mod='sendyintegration'}" {if $sendynews.namereq == 1}data-req="true" required{/if}/>
					{/if}

					{* Add honeypot *}
					<input class="c-input__t" type="text" name="lastName" id="lastName"/>

					<div>
						<div class=""><input id="sendynewsletter_email_oneseven" type="text" name="email" class="account_input" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="john@example.com" required/></div>
						<div class="g-recaptcha" data-sitekey="{$recaptchaKey}" data-callback="onSubmitNewsletterSignup"></div>
					</div>
					<input id="submit_oneseven" type="submit" value="Submit" disabled class="btn btn-primary name="submitNewsletter" />
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

				<p id="sn_error" class="sn_warning">{l s='There was an error please try again.' mod='sendyintegration'}</p>
				<p id="sn_email" class="sn_warning">{l s='Invalid email address.' mod='sendyintegration'}</p>
				<p id="sn_subscribed" class="sn_warning">{l s='Already subscribed.' mod='sendyintegration'}</p>
				<p id="sn_name" class="sn_warning">{l s='Please enter your name.' mod='sendyintegration'}</p>
				<p class="sn_success">{l s='Subscription successful.' mod='sendyintegration'}</p>
			</div>
		</div>
	{/if}
{/if}
<!-- /Sendy Newsletter module-->