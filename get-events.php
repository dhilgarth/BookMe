<?php

//--------------------------------------------------------------------------------------------------
// This script reads event data from a JSON file and outputs those events which are within the range
// supplied by the "start" and "end" GET parameters.
//
// An optional "timezone" GET parameter will force all ISO8601 date stings to a given timezone.
//
// Requires PHP 5.2.0 or higher.
//--------------------------------------------------------------------------------------------------

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Require our Event class and datetime utilities
require dirname(__FILE__) . '/utils.php';
require_once 'ews.php';
require_once 'availability.php';

// Short-circuit if the client did not give us a date range.
if (!isset($_GET['start']) || !isset($_GET['end'])) {
	die("Please provide a date range.");
}

// Parse the timezone parameter if it is present.
$timezone = null;
if (isset($_GET['timezone'])) {
	$timezone = new DateTimeZone($_GET['timezone']);
}

// Parse the start/end parameters.
// These are assumed to be ISO8601 strings with no time nor timezone, like "2013-12-29".
// Since no timezone will be present, they will parsed as UTC.
$range_start = parseDateTime($_GET['start'], $timezone);
$range_end = parseDateTime($_GET['end'], $timezone);

$emailAddress = $_GET['emailAddress'];
$name = $_GET['name'];

// $request = new EWSType_FindItemType();
// // Use this to search only the items in the parent directory in question or use ::SOFT_DELETED
// // to identify "soft deleted" items, i.e. not visible and not in the trash can.
// $request->Traversal = EWSType_ItemQueryTraversalType::SHALLOW;
// // This identifies the set of properties to return in an item or folder response
// $request->ItemShape = new EWSType_ItemResponseShapeType();
// $request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::DEFAULT_PROPERTIES;

// // Define the timeframe to load calendar items
// $request->CalendarView = new EWSType_CalendarViewType();
// $request->CalendarView->StartDate = $range_start->format('c'); // an ISO8601 date e.g. 2012-06-12T15:18:34+03:00
// $request->CalendarView->EndDate = $range_end->format('c'); // an ISO8601 date later than the above

// $request->ParentFolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
// $request->ParentFolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
// $request->ParentFolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::CALENDAR;

// $response = $ews->FindItem($request);

$response = $ews->listCalendarEvent(null, $range_start, $range_end);
// Accumulate an output array of event data arrays.
$output_arrays = array();

// Loop through each item if event(s) were found in the timeframe specified
if ($response->ResponseMessages->FindItemResponseMessage->RootFolder->TotalItemsInView > 0) {
    $calendarItems = $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->CalendarItem;
    if(gettype($calendarItems) == 'object') {
    	$calendarItem = $calendarItems;
    	$calendarItems = array();
    	$calendarItems[] = $calendarItem;
   	}

	foreach ($calendarItems as $calendarItem) {
		$output_arrays[] = createEventFromCalendarItem($calendarItem, $timezone, $name);
	}

    foreach(createDefaultAvailabilityEvents($range_start, $range_end, $timezone, $availability) as $generalAvailability)
        $output_arrays[] = $generalAvailability;
    
    $output_arrays = mergeEvents($output_arrays);
}

sendDataToClient($output_arrays);

function createDefaultAvailabilityEvents($range_start, $range_end, $timezone, $availability) {
    $result = array();
    $nextStartDate = $range_start;
    foreach ($availability as $weekAvailability) {

        for ($i = dayOfWeek($weekAvailability->ApplicableStartDate); $i < daysSpan($weekAvailability); $i++) {
            $dayAvailability = $weekAvailability->Availabilities[$i % 7];

            if($dayAvailability == null)
                continue;

            $daysInterval = new DateInterval('P0D');
            $daysInterval->d = $i;
            $day = clone $weekAvailability->ApplicableStartDate;
            $day->add($daysInterval);
            $endDate = clone $day;
            $time = $dayAvailability->StartTime;
            $endDate->setTime($time->h, $time->m);
            $endDate->setTimezone($timezone);
            $result[] = createEvent($nextStartDate, $endDate);
            $nextStartDate = clone $day;
            $time = $dayAvailability->EndTime;
            $nextStartDate->setTime($time->h, $time->m);
            $nextStartDate->setTimezone($timezone);
        }
    }

    if($nextStartDate < $range_end) {
        $result[] = createEvent($nextStartDate, $range_end);
    }

    return $result;
}

function dayOfWeek($dateTime) {
    return $dateTime->format("N") - 1;
}

function daysSpan($weekAvailability) {
    return $weekAvailability->ApplicableStartDate->diff($weekAvailability->ApplicableEndDate)->format('%a');
}

function createEvent($start, $end, $editable = false, $title = 'Not available', $backgroundColor = '#8B0000') {
    $result = array();
    $result['title'] = $title;
    $result['start'] = $start;
    $result['end'] = $end;
    $result['editable'] = $editable;
    $result['backgroundColor'] = $backgroundColor;
    $result['isEventOfCurrentUser'] = false;
    return $result;
}

function createEventFromCalendarItem($calendarItem, $timezone, $name) {

	$id = $calendarItem->ItemId->Id;
    $change_key = $calendarItem->ItemId->ChangeKey;
    $start = parseDateTime($calendarItem->Start, $timezone);
    $end = parseDateTime($calendarItem->End, $timezone);
    $subject = $calendarItem->Subject;
    
	$array = createEvent($start, $end);

    if(property_exists($calendarItem, 'Categories')) {
        $categories = $calendarItem->Categories->String;
        $isWebCalendarEvent = false;
        if(is_array($categories))
            $isWebCalendarEvent = in_array('Web calendar', $categories);
        else
            $isWebCalendarEvent = 'Web calendar' == $categories;

        if($isWebCalendarEvent && strpos($subject, $name) !== false) {
            $array['isEventOfCurrentUser'] = true;
            $array['title'] = $subject;
            $array['backgroundColor'] = '#C6EFCE';
            $array['textColor'] = '#000000';
        }
    }

    // Convert the input array into a useful Event object
	return $array;
}

function mergeEvents($events) {

    $result = array();

    foreach ($events as $event) {
        $minStart = $event['start'];
        $maxEnd = $event['end'];
        if(!$event['isEventOfCurrentUser']) {
            foreach ($result as &$timespan) {
                if($timespan['isEventOfCurrentUser'])
                    continue;
                if($timespan['start'] < $minStart && $timespan['end'] >= $minStart)
                    $minStart = $timespan['start'];
                if($timespan['end'] > $maxEnd && $timespan['start'] <= $maxEnd)
                    $maxEnd = $timespan['end'];
            }

            foreach ($result as &$timespan) {
                if($timespan['isEventOfCurrentUser'])
                    continue;

                if($minStart <= $timespan['start'] && $maxEnd >= $timespan['end'])
                    deleteItem($timespan, $result);
            }
        }

        $event['start'] = $minStart;
        $event['end'] = $maxEnd;
        $result[] = $event;
    }

    return array_values($result);
}


function deleteItem($needle, &$array) {
    $key = array_search($needle,$array);
    if($key!==false) {
        unset($array[$key]);
    }
}

function sendDataToClient($events) {
    foreach ($events as &$event) {
        $event['start'] = $event['start']->format('c');
        $event['end'] = $event['end']->format('c');
    }

    echo json_encode($events);
}

?>