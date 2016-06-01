<?php

include 'functions.php';

$config = parse_ini_file('xmlstats.ini');

$time_zone = $config['time_zone'];
date_default_timezone_set($time_zone);

$user_agent = sprintf('xmlstats-exphp/%s (%s)', $config['version'], $config['user_agent_contact']);

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

if (isset($_GET['d'])) {
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Results for <?php showDate($date); ?></title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<header>
<h1><?php showDate($date); ?></h1>
</header>

<nav>
<a href="?<?php dateAdd($date, -1); ?>">Previous</a> | <a href="/">Today</a> | <a href="?<?php dateAdd($date, 1); ?>">Next</a>
</nav>

<section id="main">
<?php foreach ($events->event as $evt): ?>
<div class="event">
 <table>
  <thead>
   <tr>
    <th colspan="2"><?php showStatus($evt); ?></th>
   </tr>
  </thead>
  <?php if ($evt->event_status == 'completed'):
     // for completed events, test for winning team
     // we will apply a css "win" class to the winning team's name
     $homewin = ($evt->home_points_scored > $evt->away_points_scored) ? 1 : 0;
  ?>
  <tbody>
   <tr>
   <?php if (!$homewin): ?>
    <td class="win"><?= $evt->away_team->full_name ?></td>
   <?php else: ?>
    <td><?= $evt->away_team->full_name ?></td>
   <?php endif; ?>
    <td><?= $evt->away_points_scored ?></td>
   </tr>
   <tr>
   <?php if ($homewin): ?>
    <td class="win"><?= $evt->home_team->full_name ?></td>
   <?php else: ?>
    <td><?= $evt->home_team->full_name ?></td>
   <?php endif; ?>
    <td><?= $evt->home_points_scored ?></td>
   </tr>
  </tbody>
  <?php else:
     // For events that are not complete, show the start time instead
     // Create DateTime object from start_date_time and set the desired time zone
     $time = DateTime::createFromFormat(DateTime::W3C, $evt->start_date_time);
     $time->setTimeZone(new DateTimeZone($time_zone));
  ?>
  <tbody>
   <tr>
    <td><?= $evt->away_team->full_name ?></td>
    <td rowspan="2"><?= $time->format('g:i A') ?></td>
   </tr>
   <tr>
    <td><?= $evt->home_team->full_name ?></td>
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
</div>
<?php endforeach; ?>
</section>

<footer>
 <div>
  <p><a href="https://erikberg.com/api">xmlstats</a></p>
 </div>
</footer>

</body>
</html>
