<?php

// See https://erikberg.com/api/endpoints Request URL Convention for
// an explanation
function buildUrl($host, $sport, $endpoint, $id, $format, $parameters)
{
    $ary  = array($sport, $endpoint, $id);
    $path = join('/', preg_grep('/^$/', $ary, PREG_GREP_INVERT));
    $url  = 'https://' . $host . '/' . $path . '.' . $format;

    // Check for parameters and create parameter string
    if (!empty($parameters)) {
        $paramlist = array();
        foreach ($parameters as $key => $value) {
            array_push($paramlist, rawurlencode($key) . '=' . rawurlencode($value));
        }
        $paramstring = join('&', $paramlist);
        if (!empty($paramlist)) { $url .= '?' . $paramstring; }
    }
    return $url;
}

function httpGet($ua, $auth, $url)
{
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_HTTPHEADER => [ $auth ],
        CURLOPT_FAILONERROR => 1,
        CURLOPT_RETURNTRANSFER => true
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo '<pre>Curl error ', curl_errno($ch), '. ', curl_error($ch),'</pre>';
        curl_close($ch);
        exit();
    }

    curl_close($ch);
    return $response;
}

function showDate($d)
{
    if (isset($d)) {
        echo $d->format('l, F j, Y');
    }
}

function dateAdd($date, $diff)
{
    if (isset($date)) {
        $d = new DateTime($date->format('c'));
        if ($diff > 0) {
            $d->add(new DateInterval('P1D'));
        } else {
            $d->sub(new DateInterval('P1D'));
        }
        echo 'd=',$d->format('Ymd');
    }
}

function showStatus($evt) {
    if ($evt->event_status == 'completed') {
        echo 'Final';
    } else {
        echo ucfirst($evt->event_status);
    }
}

function save_cache($cache, $key, $data) {
   $cache->add($key, $data, false, 600);
}

?>
