<?php
/**
 * User: florin
 * Date: 25/01/13
 * Time: 20:33
 */
namespace Ingress;

use Goutte\Client;
use Doctrine\Common\Cache\ArrayCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Backoff\BackoffPlugin;

class Crawl
{
    const USER = 'florinutz';
    const PASSWORD = 'myPassword';
    const URL_START = 'http://www.ingress.com/intel';
    const URL_LOGIN = 'https://accounts.google.com/ServiceLogin?service=ah&passive=true&continue=https://appengine.google.com/_ah/conflogin%3Fcontinue%3Dhttp://www.ingress.com/intel';
    const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11';

    public $client;

    public function __construct()
    {
        $history = new \Symfony\Component\BrowserKit\History();
        $cookieJar = new \Symfony\Component\BrowserKit\CookieJar();
        $config = array('HTTP_USER_AGENT' => self::USER_AGENT);
        $this->client = new Client($config, $history, $cookieJar);

        $guzzleClient = $this->client->getClient();

        $backoffPlugin = BackoffPlugin::getExponentialBackoff();
        $guzzleClient->addSubscriber($backoffPlugin);
        $cachePlugin = new CachePlugin(array('adapter' => new DoctrineCacheAdapter(new ArrayCache())));
        $guzzleClient->addSubscriber($cachePlugin);
        $cookiePlugin = new CookiePlugin(new ArrayCookieJar());
        $guzzleClient->addSubscriber($cookiePlugin);

        $guzzleClient->setConfig(array(
            'ssl.certificate_authority' => false,
            'curl.options' => array(
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT => 120
            )
        ));
    }

    public function login()
    {
        $crawler = $this->client->request('GET', self::URL_LOGIN);
        $form = $crawler->selectButton('Sign in')->form(array(
            'Email' => self::USER,
            'Passwd' => self::PASSWORD
        ));
        $form['PersistentCookie']->tick();
        $crawler = $this->client->submit($form);
    }
}

$username = 'xxxx';
$password = 'xxxx';
$cookies = 'cookies.txt';

function getFormFields($data)
{
    if (preg_match('/(<form.*?id=.?gaia_loginform.*?<\/form>)/is', $data, $matches)) {
        $inputs = getInputs($matches[1]);
        return $inputs;
    } else {
        die('didnt find login form');
    }
}

function getInputs($form)
{
    $inputs = array();
    $elements = preg_match_all('/(<input[^>]+>)/is', $form, $matches);

    if ($elements > 0) {
        for ($i = 0; $i < $elements; $i++) {
            $el = preg_replace('/\s{2,}/', ' ', $matches[1][$i]);
            if (preg_match('/name=(?:["\'])?([^"\'\s]*)/i', $el, $name)) {
                $name = $name[1];
                $value = '';
                if (preg_match('/value=(?:["\'])?([^"\'\s]*)/i', $el, $value)) {
                    $value = $value[1];
                }
                $inputs[$name] = $value;
            }
        }
    }

    return $inputs;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt(
    $ch,
    CURLOPT_USERAGENT,
    "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17"
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

curl_setopt(
    $ch,
    CURLOPT_URL,
    'https://accounts.google.com/ServiceLogin?service=ah&passive=true&continue=https://appengine.google.com/_ah/conflogin%3Fcontinue%3Dhttp://www.ingress.com/intel'
);
$data = curl_exec($ch);

$formFields = getFormFields($data);
$formFields['Email'] = $username;
$formFields['Passwd'] = $password;
unset($formFields['PersistentCookie']);

$post_string = '';
foreach ($formFields as $key => $value) {
    $post_string .= $key . '=' . urlencode($value) . '&';
}
$post_string = substr($post_string, 0, -1);

curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/ServiceLoginAuth');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
$result = curl_exec($ch);

curl_setopt($ch, CURLOPT_URL, 'https://www.ingress.com/intel');
curl_setopt($ch, CURLOPT_POST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, null);
$result = curl_exec($ch);

echo $result;

curl_close($ch);
@unlink($cookies);
