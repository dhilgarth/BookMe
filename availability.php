<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

$tz = new DateTimeZone(date_default_timezone_get());

class DayAvailability
{
    public $StartTime;

    public $EndTime;

    public function __construct($startTime, $endTime) {
        $this->StartTime = new DateInterval($startTime);
        $this->EndTime = new DateInterval($endTime);
    }
}

class WeekAvailability
{
    public $ApplicableStartDate;

    public $ApplicableEndDate;

    public $Availabilities;

    public function __construct($applicableStartDate, $applicableEndDate, $availabilities) {
        $this->ApplicableStartDate = $applicableStartDate;
        $this->ApplicableEndDate = $applicableEndDate;
        $this->Availabilities = $availabilities;
    }

}

$availability = array();
$availability[] = new WeekAvailability(new DateTime('2014-06-23', $tz), new DateTime('2014-06-29', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT18H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-06-30', $tz), new DateTime('2014-07-06', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT18H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-07-07', $tz), new DateTime('2014-07-13', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT17H'),
    new DayAvailability('P0DT08H', 'P0DT17H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-06-14', $tz), new DateTime('2014-07-20', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT18H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-07-21', $tz), new DateTime('2014-07-27', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT19H'),
    new DayAvailability('P0DT08H', 'P0DT18H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-07-28', $tz), new DateTime('2014-08-03', $tz), array(
    null,
    null,
    new DayAvailability('P0DT19H', 'P0DT21H'),
    new DayAvailability('P0DT19H', 'P0DT21H'),
    new DayAvailability('P0DT19H', 'P0DT21H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-08-04', $tz), new DateTime('2014-08-10', $tz), array(
    null,
    new DayAvailability('P0DT20H', 'P0DT22H'),
    null,
    new DayAvailability('P0DT20H', 'P0DT22H'),
    new DayAvailability('P0DT20H', 'P0DT22H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-08-11', $tz), new DateTime('2014-08-17', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT18H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-08-18', $tz), new DateTime('2014-08-24', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT18H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-08-25', $tz), new DateTime('2014-08-31', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT18H'),
    null,
    null));
$availability[] = new WeekAvailability(new DateTime('2014-09-01', $tz), new DateTime('2014-09-07', $tz), array(
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT19H'),
    new DayAvailability('P0DT09H', 'P0DT18H'),
    null,
    null));
?>