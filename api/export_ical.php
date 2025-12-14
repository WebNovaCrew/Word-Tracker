<?php
// backend-php/api/export_ical.php

include_once 'config/database.php';
include_once 'models/Plan.php';

$database = new Database();
$db = $database->getConnection();
$plan = new Plan($db);

$plan_id = isset($_GET['id']) ? $_GET['id'] : die("Missing ID");

$plan_details = $plan->getPlanDetails($plan_id);

if (!$plan_details) {
    die("Plan not found");
}

// Generate ICS content
$eol = "\r\n";
$ics_content = "BEGIN:VCALENDAR" . $eol;
$ics_content .= "VERSION:2.0" . $eol;
$ics_content .= "PRODID:-//Word Tracker//NONSGML v1.0//EN" . $eol;
$ics_content .= "CALSCALE:GREGORIAN" . $eol;

foreach ($plan_details['schedule'] as $day) {
    $date = $day['date'];
    $target = $day['target_count'];

    // Format date for iCal (YYYYMMDD)
    $dtstart = date('Ymd', strtotime($date));
    $dtend = date('Ymd', strtotime($date . ' + 1 day')); // All day event

    $ics_content .= "BEGIN:VEVENT" . $eol;
    $ics_content .= "UID:" . uniqid() . "@wordtracker.com" . $eol;
    $ics_content .= "DTSTAMP:" . date('Ymd\THis\Z') . $eol;
    $ics_content .= "DTSTART;VALUE=DATE:" . $dtstart . $eol;
    $ics_content .= "DTEND;VALUE=DATE:" . $dtend . $eol;
    $ics_content .= "SUMMARY:Write " . $target . " words" . $eol;
    $ics_content .= "DESCRIPTION:Daily writing target for plan: " . $plan_details['title'] . $eol;
    $ics_content .= "END:VEVENT" . $eol;
}

$ics_content .= "END:VCALENDAR";

// Headers for download
header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=plan-' . $plan_id . '.ics');

echo $ics_content;
?>