<?php
declare(strict_types=1);

namespace Attlaz\Project\Helper;

class DateTimeHelper
{
    public static function getNow(): \DateTime
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public static function fromTimestamp(int $timeStamp): \DateTime
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($timeStamp);

        return $dateTime;
    }
//    public static function serialize(\DateTime $dateTime)
//    {
//        return json_encode($dateTime);
//    }
    public static function deserialize(array $dateTime): \DateTime
    {
        $date = $dateTime['date'];
//        $timezone_type = $dateTime['timezone_type'];
        $timezone = $dateTime['timezone'];

        return new \DateTime($date, new \DateTimeZone($timezone));
    }
}
