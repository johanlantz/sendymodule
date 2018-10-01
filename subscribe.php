<?php
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

include_once dirname(__FILE__) . '/../../config/config.inc.php';
include_once dirname(__FILE__) . '/../../init.php';

/*if (isset($_POST)) {
extract($_POST);
}
else {
exit;
}*/

$email = null;
$name = null;
$ip = null;
$honeypot = null;

if (Tools::getIsset("submitNewsletter")) {
    $email = Tools::getValue("email");
    $name = Tools::getValue("name");
    $honeypot = Tools::getValue("lastName");
    $ip = Tools::getValue("ip");
} else {
    exit;
}

if ($honeypot && Tools::strlen($honeypot) > 0) {
    exit;
}

$url = Configuration::get('SENDYNEWSLETTER_INSTALLATION') . '/subscribe';
$ip_set = (int) Configuration::get('SENDYNEWSLETTER_IP');
$ip_var = Configuration::get('SENDYNEWSLETTER_IPVALUE');
$list = Configuration::get('SENDYNEWSLETTER_LIST');
$name_input = Configuration::get('SENDYNEWSLETTER_NAME');

$data = array(
    'list' => $list,
    'email' => $email,
    'boolean' => 'true',
);

if ($name_input) {
    $data['name'] = $name;
}

if ($ip_set == 1 && $ip_var && !empty($ip_var)) {
    $data[$ip_var] = $ip;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

curl_exec($ch);
