<?php

include 'functions.php';

$config = parse_ini_file('xmlstats.ini');

$time_zone = $config['time_zone'];
date_default_timezone_set($time_zone);

$user_agent = sprintf('xmlstats-phpex/%s (%s)', $config['version'], $config['user_agent_contact']);

$auth_header = sprintf('Authorization: Bearer %s', $config['access_token']);

$cache_host = $config['memcache_host'];

$cache_port = $config['memcache_port'];

$memcache = new Memcache;
$memcache->connect($cache_host, $cache_port) or die ('Couldn\'t connect to memcache server. Is it running?');

// Set the API sport, endpoint, id, format, and any parameters
$host = 'erikberg.com';
$sport = '';
$endpoint = 'events';
$id = '';
$format = 'json';
$parameters = [
    'sport' => 'nba',
    'date' => date('Ymd')
];

if ($_GET['d']) {
    $parameters['date'] = $_GET['d'];
}

// Pass endpoint, format, and parameters to build request url
$url = buildUrl($host, $sport, $endpoint, $id, $format, $parameters);

$response = $memcache->get($url);
if (!$response) {
    $response = httpGet($user_agent, $auth_header, $url);
    if ($response != null) {
        save_cache($memcache, $url, $response);
    }
}

$events = json_decode($response);

// Create DateTime object using the ISO 8601 formatted events_date
$date = DateTime::createFromFormat(DateTime::W3C, $events->events_date);

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Results for <?php showDate($date); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
 font-family: sans-serif;
 color: #222;
}
table {
 width: 100%;
 max-width: 300px;
 border: solid 1px #ccc;
 float: left;
 margin: 1em;
 border-collapse: collapse;
}
tbody {
 border: solid 1px #bbb;
}
tfoot {
 font-size: smaller;
 background-color: #fbfbfb;
}
thead th {
 padding: 3px;
 text-align: left;
 font-size: smaller;
 background-color: #d2dcf1;
 color: #333;
}
tfoot td {
 padding-left: 6px;
}
tfoot tr:first-child td {
 padding-top: 6px;
}
tfoot tr:last-child td {
 padding-bottom:6px;
}
tbody td:first-child {
 max-width: 90%;
 padding: 6px 0px 6px 6px;
}
tbody td:nth-child(2) {
 padding-right: 6px;
 text-align: right;
}
td.win {
 font-weight: bold;
}
#main {
 max-width: 1200px;
}
footer {
 clear: both;
 padding-top: 3em;
}
</style>
</head>
<body>

<header>
<h1><?php showDate($date); ?></h1>
</header>

<nav>
<a href="?<?php dateAdd($date, -1); ?>">Previous</a> | <a href="?<?php dateAdd($date, 1); ?>">Next</a>
</nav>

<section id="main">
<?php

foreach ($events->event as $evt) :
    // Create DateTime object from start_date_time and set the desired time zone
    $time = DateTime::createFromFormat(DateTime::W3C, $evt->start_date_time);
    $time->setTimeZone(new DateTimeZone($time_zone));

    // Get team objects (https://erikberg.com/api/objects/team)
    $away_team = $evt->away_team;
    $home_team = $evt->home_team;
    $homewin = ($evt->home_points_scored > $evt->away_points_scored) ? 1 : 0;
?>
<table>
 <thead>
 <tr>
 <th colspan="2"><?php showStatus($evt); ?></th>
 </tr>
 </thead>
 <?php if ($evt->event_status == 'completed'): ?>
  <tbody>
  <tr>
  <?php if (!$homewin): ?>
   <td class="win"><?= $away_team->full_name ?></td>
  <?php else: ?>
   <td><?= $away_team->full_name ?></td>
  <?php endif; ?>
   <td>
    <?php if ($evt->event_status == 'completed'): ?>
     <?= $evt->away_points_scored ?>
    <?php endif; ?>
   </td>
  </tr>
  <tr>
  <?php if ($homewin): ?>
   <td class="win"><?= $home_team->full_name ?></td>
  <?php else: ?>
   <td><?= $home_team->full_name ?></td>
  <?php endif; ?>
   <td>
    <?php if ($evt->event_status == 'completed'): ?>
     <?= $evt->home_points_scored ?>
    <?php endif; ?>
   </td>
  </tr>
  </tbody>
 <?php else: ?>
  <tbody>
  <tr>
   <td><?= $away_team->full_name ?></td>
   <td rowspan="2"><?= $time->format('g:i A') ?></td>
  </tr>
  <tr>
   <td><?= $home_team->full_name ?></td>
  </tr>
  </tbody>
 <?php endif; ?>
 <tfoot>
 <tr>
  <td colspan="2"><?= $evt->site->name ?></td>
 </tr>
 <tr>
  <td colspan="2"><?= $evt->site->city, ', ', $evt->site->state ?></td>
 </tr>
 </tfoot>
</table>
<?php endforeach; ?>
</section>

<footer></footer>

</body>
</html>
