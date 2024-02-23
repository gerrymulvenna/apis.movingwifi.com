<?php
// a simple Google API example using PHP
error_reporting(-1);
session_start();
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
require "credentials.php";  //api_key, client_id, client_secret, redirect_uri

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
$title = "Google Calendar API example";
$connect = "Connect to Google";
$cookie = "movingwifi-gCal";

if (isset($_GET['state']) && isset($_SESSION['oauth2state']))
{
	if ($_GET['state'] == $_SESSION['oauth2state'])
	{
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$token->access_token_expiry = time() + $token->expires_in;
			// get user info
			$data = apiRequest($urlResourceOwnerDetails, $token->access_token);
			if ($data['code'] == 200)
			{
				$token->user = $data['response'];
				print head($title, "Connected - click to continue", $token->user->name);
				$_SESSION[$cookie] = serialize($token);
				print footer("Disconnect", "");
			}
			else
			{
				print head($title, "Connected - click to continue", "but failed to retrieve user info");
				$_SESSION[$cookie] = serialize($token);
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
		unset($_SESSION['oauth2state']); 

		print head($title, "Error - invalid state");
		print '<pre>';
		print_r($_REQUEST);
		print_r($_SESSION);
		print '</pre>';
	}
}

// If we have a cookie, get the connection details
elseif (isset($_SESSION[$cookie]))
{
	if (isset($_REQUEST['operation']))
	{
		if($_REQUEST['operation'] == 'cookie')
		{
			$token = unserialize($_SESSION[$cookie]);
			print head($title, "Home");
			print '<pre>';
			print_r($token);
			print '</pre>';
			print footer("Disconnect", "");
		}
		elseif($_REQUEST['operation'] == 'revoke')
		{
			print head($title, "Disconnected");
			unset($_SESSION[$cookie]);
		}
		elseif($_REQUEST['operation'] == 'calendarList')
		{
			$token = unserialize($_SESSION[$cookie]);
			$url = "$urlCalendarBase/users/me/calendarList";
			$data = apiRequest($url, $token->access_token);
			if ($data['code'] == 200)
			{
				print head($title, "Displaying calendarlist - click to continue", $token->user->name);
				$table = calendarlist_summary($data['response']);
				print table_html_calendarlist($table);
				print footer("Disconnect", "");
			}
			else
			{
				print head($title, "Error");
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
				$token = unserialize($_SESSION[$cookie]);
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
				$data = apiRequest($url, $token->access_token);
				if ($data['code'] == 200)
				{
					print head($title, "Displaying events - click to continue", $token->user->name);
					$table = events_summary($data['response']);
					print table_html($table);
					print footer("Disconnect", "");
				}
				else
				{
					print head($title, "Error");
					print '<pre>';
					print "$url\n";
					print_r($data);
					print '</pre>';
					print footer("Disconnect", "");
				}
			}
			else
			{
				print head($title, "No calendarId suppplied - click to continue");
				print footer("Disconnect", "");
			}
		}
		
	}
	else
	{
		$now = time();
		$token = unserialize($_SESSION[$cookie]);
		if ($now <  $token->access_token_expiry)
		{
			print head($title, "Home", $token->user->name);
			print generic_button("cookie", "Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
			print generic_button("user", "Get my list of calendars",['operation'=>'calendarList'], "tertiary", "GET", "./");
		}
		else
		{
			print head($title, "Disconnected");
			unset($_SESSION['oauth2state']); 
			unset($_SESSION[$cookie]);
		}
		print footer("Disconnect", "");
	}
}
// If we don't have an authorization code then get one
elseif (!isset($_GET['code'])) {
	$state = getRandomState();

    // store state in the session.
    $_SESSION['oauth2state'] = $state;

    // display Connect to button
	print head($title);
	print generic_button("connect", $connect,['client_id'=>$client_id,
	                                                    'response_type'=>'code',
														'redirect_uri'=>$redirect_uri,
														'scope'=>implode(' ', $scopes)
														,'state'=>$state], "tertiary", "GET", $urlAuthorize);
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
		$table[$i][] =(property_exists($event, 'start')) ? $event->start : "";
		$table[$i][] =(property_exists($event, 'end')) ? $event->end : "";
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