<?php

namespace App\Helpers;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Lang;

class DateHelper
{
    /**
     * Calculates the age from the given date
     *
     * @param string $date
     *
     * @return int
     *
     * @throws Exception
     */
    public static function calculateAge(string $date): int
    {
        $dateTime = new DateTime($date);
        return $dateTime->diff(new DateTime())->y;
    }

    public static function formatTime(string $time): string
    {
        return date(config('app.time_format'), strtotime($time));
    }

    /**
     * Formats a date to our default date format
     *
     * @param string $date
     *
     * @return string
     */
    public static function formatDate(string $date): string
    {
        return date(config('app.date_format'), strtotime($date));
    }

    public static function formatDateTime(string $dateTime): string
    {
        return date(config('app.datetime_format'), strtotime($dateTime));
    }

    public static function formatDateRange(string|null $start_date, string|null $end_date): string
    {
        if ($start_date === null && $end_date === null) {
            return '-';
        }
        if ($start_date === null) {
            return Lang::get('general.to') . ' ' . self::formatDate($end_date);
        }
        if ($end_date === null) {
            return Lang::get('general.from') . ' ' . self::formatDate($start_date);
        }
        return self::formatDate($start_date) . ' - ' . self::formatDate($end_date);
    }

    public static function textualRepresentationOfDateUntilToday($date)
    {
        $dateTimestamp = strtotime(date(config('app.date_format'), strtotime($date)));
        $today = today()->getTimestamp();

        if ($date->getTimestamp() > now()->getTimestamp() - 120) {
            return __('activity_log.just_now');
        }
        if ($dateTimestamp === $today) {
            return __('general.today');
        }
        if ($dateTimestamp === strtotime('-1 day', $today)) {
            return __('general.yesterday');
        }
        if ($dateTimestamp > strtotime('-1 week', $today)) {
            return __('activity_log.less_week');
        }
        if ($dateTimestamp > strtotime('-2 weeks', $today)) {
            return __('activity_log.week');
        }
        if ($dateTimestamp > strtotime('-3 weeks', $today)) {
            return __('activity_log.two_weeks');
        }
        if ($dateTimestamp > strtotime('-1 month', $today)) {
            return __('activity_log.three_weeks');
        }
        if ($dateTimestamp > strtotime('-2 months', $today)) {
            return __('activity_log.month');
        }
        if ($dateTimestamp > strtotime('-1 year', $today)) {
            return __('activity_log.over_month');
        }
        return __('activity_log.over_year');
    }

    public static function minuteAndSecondFormatFromSeconds($seconds): string {
        return gmdate(config("app.time_m_s_format"), $seconds);
    }

    public static function getSecondsFromMinutesAndSeconds(string $minutesAndSeconds): false|int
    {
        return strtotime('1970-01-01 00:' . $minutesAndSeconds . " UTC");
    }
}
