<?php
// a simple Google API example using PHP
error_reporting(-1);

//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php and define your unique credentials for this service
require "credentials.php";  //$client_id, $client_secret, $redirect_uri

// API details
$urlAuthorize = 'https://accounts.google.com/o/oauth2/v2/auth';
$urlAccessToken = 'https://oauth2.googleapis.com/token';
$urlResourceOwnerDetails = 'https://openidconnect.googleapis.com/v1/userinfo';
$urlCalendarBase = 'https://www.googleapis.com/calendar/v3';

$scopes =  ['openid','email','profile',
			'https://www.googleapis.com/auth/calendar.readonly',
			'https://www.googleapis.com/auth/calendar.calendarlist.readonly',
			'https://www.googleapis.com/auth/calendar.calendars.readonly',
			'https://www.googleapis.com/auth/calendar.settings.readonly',
			'https://www.googleapis.com/auth/calendar.events.public.readonly',
			'https://www.googleapis.com/auth/calendar.events.owned.readonly',
			'https://www.googleapis.com/auth/calendar.events.readonly'];

// service-specific strings
$title = "Google Calendar";
$connect = "Connect to Google";
$cookie = "movingwifi-gCal";

if (isset($_GET['state']) && isset($_COOKIE['oauth2state']))
{
	if ($_GET['state'] == $_COOKIE['oauth2state'])
	{
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$cdata['access_token_expiry'] = time() + $token->expires_in;
			// we'll just keep what we need to minimise the size of the cookie
			$cdata['access_token'] = $token->access_token;
			// get user info
			$data = apiRequest($urlResourceOwnerDetails, $token->access_token);
			if ($data['code'] == 200)
			{
				$cdata['user'] = $data['response'];
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected - click to continue", $cdata['user']->name);
				print footer("Disconnect", "");
			}
			else
			{
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected - click to continue", "but failed to retrieve user info");
				print footer("Disconnect", "");
			}
		}
		else
		{
			print head($title, "Error - not connected");
			print '<pre>';
			print_r($response);
			print '</pre>';
		}
	}
	else
	{
		setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie

		print head($title, "Error - invalid state");
		print '<pre>';
		print_r($_GET);
		print_r($_COOKIE);
		print '</pre>';
	}
}

// If we have a cookie, get the connection details
elseif (isset($_COOKIE[$cookie]))
{
	if (isset($_REQUEST['operation']))
	{
		if($_REQUEST['operation'] == 'cookie')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			print head("$title | cookie contents", "Home");
			print '<pre>';
			print_r($cdata);
			print '</pre>';
			print footer("Disconnect", "");
		}
		elseif($_REQUEST['operation'] == 'revoke')
		{
			setcookie($cookie,"", time() - 3600, "/");  //delete cookie
			print head($title, "Disconnected");
		}
		elseif($_REQUEST['operation'] == 'calendarList')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$url = "$urlCalendarBase/users/me/calendarList";
			$data = apiRequest($url, $cdata['access_token']);
			if ($data['code'] == 200)
			{
				print head("$title | my calendars", "Home", $cdata['user']->name);
				$table = calendarlist_summary($data['response']);
				print table_html_calendarlist($table);
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | my calendars", "Error", $cdata['user']->name);
				print '<pre>';
				print_r($data);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
		elseif($_REQUEST['operation'] == 'events')
		{
			if (isset($_REQUEST['calendarId']))
			{
				$vars = [];
				$query = "";
				$cdata = unserialize($_COOKIE[$cookie]);
				// get the events filter parameters, if any
				if (isset($_REQUEST['timeMin']))
				{
					$vars['timeMin'] = $_REQUEST['timeMin'];
				}
				if (isset($_REQUEST['timeMax']))
				{
					$vars['timeMax'] = $_REQUEST['timeMax'];
				}
				if (isset($_REQUEST['orderBy']))
				{
					$vars['orderBy'] = $_REQUEST['orderBy'];
				}
				if (count($vars))
				{
					$query = "?" . http_build_query($vars);
				}
				$url = "$urlCalendarBase/calendars/" . urlencode($_REQUEST['calendarId']) . "/events$query";
				$data = apiRequest($url, $cdata['access_token']);
				if ($data['code'] == 200)
				{
					print head("$title | calendar events", "Home", $cdata['user']->name);
					$table = events_summary($data['response']);
					print table_html($table);
					print footer("Disconnect", "");
				}
				else
				{
					print head("$title | calendar events", "Error", $cdata['user']->name);
					print '<pre>';
					print "$url\n";
					print_r($data);
					print '</pre>';
					print footer("Disconnect", "");
				}
			}
			else
			{
				print head($title, "Missing calendar id - click to continue");
				print footer("Disconnect", "");
			}
		}
		
	}
	else
	{
		$now = time();
		$cdata = unserialize($_COOKIE[$cookie]);
		if ($now <  $cdata['access_token_expiry'])
		{
			print head($title, "Home", $cdata['user']->name);
			print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
			print generic_button("Get my list of calendars",['operation'=>'calendarList'], "tertiary", "GET", "./");
		}
		else
		{
			setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
			setcookie($cookie,"", time() - 3600, "/");  //delete cookie
			print head($title, "Disconnected - click to continue", $cdata['user']->name);
		}
		print footer("Disconnect", "");
	}
}
// If we don't have an authorization code then get one
elseif (!isset($_GET['code'])) {
	if (isset($_COOKIE['oauth2state']))
	{
		$state = $_COOKIE['oauth2state'];
	}
	else
	{
		$state = getRandomState();
	}

    // store state in cookie
	setcookie('oauth2state', $state, time() + 600, '/');

    // display Connect to button
	print head($title);
	print generic_button($connect,['client_id'=>$client_id,
												'response_type'=>'code',
												'redirect_uri'=>$redirect_uri,
												'scope'=>implode(' ', $scopes),
												'state'=>$state],
												"tertiary", "GET", $urlAuthorize);
}

function calendarlist_summary($response)
{
	$i = 0;
	// field names in first row
	$table[$i] = ['summary','description','id - click to see events','etag','backgroundColor','foregroundColor'];
	foreach ($response->items as $calendar)
	{
		$i++;
		$table[$i][] =(property_exists($calendar, 'summary')) ? $calendar->summary : "";
		$table[$i][] =(property_exists($calendar, 'description')) ? $calendar->description : "";
		$table[$i][] =(property_exists($calendar, 'id')) ? $calendar->id : "";
		$table[$i][] =(property_exists($calendar, 'etag')) ? $calendar->etag : "";
		$table[$i][] =(property_exists($calendar, 'backgroundColor')) ? $calendar->backgroundColor : "";
		$table[$i][] =(property_exists($calendar, 'foregroundColor')) ? $calendar->foregroundColor : "";
	}
	return $table;
}

function events_summary($response)
{
	$i = 0;
	// field names in first row
	$table[$i] = ['summary','location','id','start','end','created','updated','status'];
	foreach ($response->items as $event)
	{
		$i++;
		$table[$i][] =(property_exists($event, 'summary')) ? $event->summary : "";
		$table[$i][] =(property_exists($event, 'location')) ? $event->location : "";
		$table[$i][] =(property_exists($event, 'id')) ? $event->id : "";
		if (property_exists($event,'start'))
		{
			if (property_exists($event->start, 'dateTime'))
			{
				$table[$i][] = $event->start->dateTime;
			}
			elseif(property_exists($event->start, 'date'))
			{
				$table[$i][] = $event->start->date;
			}
			else
			{
				$table[$i][] = "";
			}
		}
		else
		{
			$table[$i][] = "";
		}
		if (property_exists($event,'end'))
		{
			if (property_exists($event->end, 'dateTime'))
			{
				$table[$i][] = $event->end->dateTime;
			}
			elseif(property_exists($event->end, 'date'))
			{
				$table[$i][] = $event->end->date;
			}
			else
			{
				$table[$i][] = "";
			}
		}
		$table[$i][] =(property_exists($event, 'created')) ? $event->created : "";
		$table[$i][] =(property_exists($event, 'updated')) ? $event->updated : "";
		$table[$i][] =(property_exists($event, 'status')) ? $event->status : "";
	}
	return $table;
}


// specialised function (for calendarlist) to return a HTML table for a two-dimensional array, where row 0 contains the fieldnames
// values for backgroundColor and foregroundColor are used to color each row appropriately
function table_html_calendarlist($data)
{
	$html = "<table class=\"tight\"><thead><tr>\n";
	$headings = array_shift($data);
	for ($i = 0; $i < count($headings); $i++)
	{
		
		if ($headings[$i] == "backgroundColor")			
		{
			$backindex = $i;
		}
		elseif($headings[$i] =="foregroundColor")
		{
			$foreindex = $i;
		}
		else
		{
			$html .= '<th data-label="' .$headings[$i] . '">' . $headings[$i] . '</th>' . "\n";
		}
	}
	$html .= "</tr></thead><tbody>\n";
	
	foreach ($data as $row)
	{
		$i = 0;
		$html .= "<tr style=\"color: ". $row[$foreindex] . "; background-color: " . $row[$backindex] . "; \">\n";
		foreach ($row as $cell)
		{
			$label = $headings[$i++];
			if ($label <> "backgroundColor" && $label <> "foregroundColor" && $label <> "id - click to see events")
			{
				$html .= "<td style=\"color: inherit; background-color: inherit;\" data-label=\"$label\">$cell</td>\n";
			}
			elseif ($label == "id - click to see events")
			{
				// insert a link to display events from the calendar
				$query = http_build_query(['operation'=>'events','calendarId'=>$cell,'orderBy'=>'updated','timeMin'=>date('c')]);
				$html .= "<td style=\"background-color: inherit;\" data-label=\"$label\"><a class=\"button\" href=\"./?$query\">$cell</a></td>\n";
			}
		
		}
		$html .= "</tr>\n";
	}
	$html .= "</tbody></table>\n";
	return $html;
}

?>