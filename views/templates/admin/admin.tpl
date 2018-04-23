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
<fieldset>
    <h2>Sync customer lists</h2>
    <p>Here you can choose to sync your existing Customer lists with Sendy.</p>
    <p>Clicking any language button below will sync all customers with the corresponding CUSTOMER list above for that language.</p>
    <p>The setting above to respect the customer opt-in to your newsletter will be respected. So that if respecting the users opt-in is enabled, only customers
    that have actively opted-in to your newsletter will be added.</p>
    <p>This is normally only needed once, after the first installation but you can run it anytime, for instance if you change lists above etc.
    Important: If a customer is already subscribed to a list, nothing happens. HOWEVER if the customer has unsubscribed directly with Sendy and not in his account settings, running this sync to an existing list will re-subscribe the customer.
    </p>
    <p><b>If you have a lot of customers, this operation might take a long time to complete. Do not reload the page, instead refresh your Sendy webpage and you should see the list filling up.</b></p>
    <p></p>

    <form action="" method="post">
        {foreach from=$sendyBack.availableLanguages item=lang}
        <div>
            <input style="width:100px" class="btn btn-default" type="submit" name="sendy_integration_customers_sync_form" value="{$lang.iso_code}" />
        </div>
        {/foreach}
    </form>
</fieldset>

<fieldset>
    <h2>Sync Prestashop newsletter module list</h2>
    <p>If you are using the native Prestashop newsletter module, here you can sync that list with Sendy.</p>
    <p>The Prestashop newsletter module does not support different languages so you can only choose one Sendy destination list</p>
    <p>This is normally only needed once, when you migrate to using this module. It is however ok to run the sync at any point.</p>
    <p>Do note that only users with ACTIVE status will be migrated to Sendy. Unsubscribed users will not be synched.</p>
    <p><i>If you are running a multishop, only the newsletter subscriber list for the current shop will be synced.</i></p>
    <p><b>If you have a lot of subscribers, this operation might take a long time to complete. Do not reload the page, instead refresh your Sendy webpage and you should see the list filling up.</b></p>

    <form action="" method="post">
        <div>
            <label style="text-align:left;">Sendy list to sync native Prestashop newsletter list to:</label>
            <input type="text" name="list_to_sync_to" />
            <br />
            <input style="width:100px" class="btn btn-default" type="submit" name="sendy_integration_native_newsletter_sync_form" value="Sync" />
        </div>
    </form>
</fieldset>