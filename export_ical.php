<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$plan_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$plan_id) {
    die("Missing id parameter");
}

// Get plan
$query = "SELECT * FROM plans WHERE id = :id LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $plan_id);
$stmt->execute();
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    die("Plan not found");
}

// Get days
$queryDays = "SELECT * FROM plan_days WHERE plan_id = :plan_id ORDER BY date ASC";
$stmtDays = $db->prepare($queryDays);
$stmtDays->bindParam(":plan_id", $plan_id);
$stmtDays->execute();
$days = $stmtDays->fetchAll(PDO::FETCH_ASSOC);

// Generate ICS
$eol = "\r\n";
$ics = "BEGIN:VCALENDAR" . $eol;
$ics .= "VERSION:2.0" . $eol;
$ics .= "PRODID:-//Word Tracker//NONSGML v1.0//EN" . $eol;
$ics .= "CALSCALE:GREGORIAN" . $eol;

foreach ($days as $day) {
    $dtstart = date('Ymd', strtotime($day['date']));
    $dtend = date('Ymd', strtotime($day['date'] . ' + 1 day'));

    $ics .= "BEGIN:VEVENT" . $eol;
    $ics .= "UID:" . uniqid() . "@wordtracker.com" . $eol;
    $ics .= "DTSTAMP:" . date('Ymd\THis\Z') . $eol;
    $ics .= "DTSTART;VALUE=DATE:" . $dtstart . $eol;
    $ics .= "DTEND;VALUE=DATE:" . $dtend . $eol;
    $ics .= "SUMMARY:Write " . $day['target_count'] . " words" . $eol;
    $ics .= "DESCRIPTION:Daily target for: " . $plan['title'] . $eol;
    $ics .= "END:VEVENT" . $eol;
}

$ics .= "END:VCALENDAR";

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=plan-' . $plan_id . '.ics');
echo $ics;
?>