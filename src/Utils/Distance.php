<?php

namespace ZhenMu\Support\Utils;

class Distance
{
  public static function getDistanceSql($sqlLongitude, $sqlLatitude, $longitude, $latitude, $alias = 'distance')
  {
      $sql = <<<SQL
  2 * ASIN(
        SQRT(
          POW(
            SIN(
              (
                  $latitude * PI() / 180 - $sqlLatitude * PI() / 180
              ) / 2
            ), 2
          ) + COS($latitude * PI() / 180) * COS($sqlLatitude * PI() / 180) * POW(
            SIN(
              (
                  $longitude * PI() / 180 - $sqlLongitude * PI() / 180
              ) / 2
            ), 2
          )
        )
      ) * 6378.137
  SQL;
      return sprintf('(%s) as %s', $sql, $alias);
  }
}
