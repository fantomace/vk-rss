<?
// Based on http://vjikkk.wordpress.com/%D1%83%D0%B4%D0%B0%D0%BB%D1%8F%D0%B5%D0%BC-%D0%B3%D1%80%D1%83%D0%BF%D0%BF%D1%8B-%D0%B2%D0%BA%D0%BE%D0%BD%D1%82%D0%B0%D0%BA%D1%82%D0%B5-%D1%81-%D0%B8%D1%81%D0%BA%D0%BB%D1%8E%D1%87%D0%B5%D0%BD%D0%B8/

$email   = 'youmail@mail.ru';
$pass    = 'password';

include("config.php");
include("FeedItem.php");
include("FeedWriter.php");


function curl($url, $cookie = false, $headers = false, $post = false)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function vk_apiquery($api_method, $data = false, $format = 'JSON')
{
    global $viewer_id, $sid, $secret, $api_id;
    
    if ($data != false)
        $query = $data;
    $query['method'] = $api_method;
    $query['format'] = $format;
    $query['api_id'] = $api_id;
    $query['v']      = '3.0';
    ksort($query);
    $sig = '';
    foreach ($query as $a => $b)
        $sig .= $a . '=' . $b;
    $sig          = md5($viewer_id . $sig . $secret);
    $query['sid'] = $sid;
    $query['sig'] = $sig;
    ksort($query);
    foreach ($query as $a => $b)
        $n[] = $a . '=' . urlencode($b);
    $std_str = implode($n, '&');
    
    do {
        $res = '';
        $ch  = curl_init('http://api.vk.com/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $std_str);
        $res = curl_exec($ch);
        curl_close($ch);
        
        if ($format == 'XML') {
            preg_match('#<error_code>(.*)</error_code>#', $res, $tmp);
            $err_code = intval($tmp[1]);
        } else {
            $tmp      = json_decode($res);
            if (isset($tmp->error))
                $err_code = intval($tmp->error->error_code);
            else
                $err_code = 0;
        }
        if ($err_code == 6)
            sleep(1);
    } while ($err_code == 6);
    
    if ($format == 'XML')
        return $res;
    else
        return json_decode($res);
}



error_reporting(0);
set_time_limit(0);

// Login. Phase 1: get cookie
$headers = get_headers("http://login.vk.com/?act=login&email=$email&pass=$pass");

// filter 'Set-Cookie:'
$cookie = '';
foreach ($headers as $i=>$header) {
    if (stripos($header, 'Set-Cookie:') === 0) {
        if (substr_count($header, "remixsid")) {
            preg_match("/Set-Cookie: (.*?);/i", $header, $result);
            $cookie = $result[1];
            break;
        }
    }
}

// check
if (!$cookie) {
    echo "Wrong login or password\n";
    exit;
}
 

// Login. Phase 2: get sid
$res = curl('http://vk.com/login.php?layout=iphone&app=8&url=/?act=auth');
preg_match('#id="app_hash" value="([0-9a-f]{10,})"#', $res, $tmp);

$res = curl('http://login.vk.com/', false, 1, 'act=login&al_test=9&app=8&app_hash=' . $tmp[1] . '&vk=&auth_url=http://i.vkontakte.ru/?act=auth&email=' . urlencode($email) . '&pass=' . urlencode($pass));
preg_match("#hash=([0-9a-f]{30,})#", $res, $tmp);

$res = curl('http://vk.com/login.php?act=auth_result&m=4&permanent=&expire=1&app=8&hash=' . $tmp[1]);
preg_match('#"mid":([0-9]+),"sid":"([0-9a-f]{30,})","secret":"([0-9a-f]{8,})"#', $res, $tmp);

$viewer_id = $tmp[1];
$sid       = $tmp[2];
$secret    = $tmp[3];
$api_id    = '8';

// get groups
$json = vk_apiquery('groups.get', 
                    array(
                        'extended' => 1, 
                        'fields' => 'description,start_date'), 
                    'JSON');

// write RSS
$feed = new FeedWriter(RSS2);
$feed->setTitle('VK.COM');
$feed->setLink('http://vk.com/');
$feed->setDescription('vk-rss-events');

for ($i = 1; $i<=count($json->response)-1; $i++) {
    $group = $json->response[$i];
    
    // keep events only
    if ($group->type != 'event')
        continue;

    // skip closed
    if ($group->is_closed == 1)
        continue;
    
    // clean text
    $title = $group->name;
    $description = $group->description;
    $photo = $group->photo; // http://cs5902.userapi.com/g23974705/e_49e9e8d1.jpg
    $gid = $group->gid;
    $screen_name = $group->screen_name; // club23974705
    $start_date = $group->start_date; // 1334829600 - UNIX time
    
    // beautify description
    $description = "<img src='{$photo}'/><br>{$description}";
    
    // create rss item
    $newItem = $feed->createNewItem();
    $newItem->setTitle($title);
    $newItem->setLink("http://vk.com/".$screen_name);
    $newItem->setDescription($description);
    $newItem->setDate($start_date);
    //$newItem->addElement('photo', $photo);
    $newItem->addElement('guid', $gid);
    $feed->addItem($newItem);
}

// show RSS
$feed->genarateFeed();

