<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Shelter extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'city',
        'region',
        'address',
        'lat',
        'lon',
        'balance_holder',
        'responsible_person',
        'capacity',
        'description',
        "status",
        "quality",
        "type",
        "facility"
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'quality' => 'integer',
        'type' => 'integer',
        'lat' => 'double',
        'lon' => 'double',
    ];

    public static function getNearestQuestPoints($latitude, $longitude, $dist)
    {

        $lon1 = $longitude - $dist / abs(cos(rad2deg($latitude)) * 111.0); # 1 градус широты = 111 км
        $lon2 = $longitude + $dist / abs(cos(rad2deg($latitude)) * 111.0);
        $lat1 = $latitude - ($dist / 111.0);
        $lat2 = $latitude + ($dist / 111.0);

        return Shelter::whereBetween('lat', [$lat1, $lat2])
            ->whereBetween('lon', [$lon1, $lon2])
            ->get();


    }


    public static function dist($fLat1, $lLon1, $fLat2, $lLon2)
    {
        $earth_radius = 6372795;

        // перевести координаты в радианы
        $lat1 = $fLat1 * M_PI / 180;
        $lat2 = $fLat2 * M_PI / 180;
        $long1 = $lLon1 * M_PI / 180;
        $long2 = $lLon2 * M_PI / 180;

// косинусы и синусы широт и разницы долгот
        $cl1 = cos($lat1);
        $cl2 = cos($lat2);
        $sl1 = sin($lat1);
        $sl2 = sin($lat2);
        $delta = $long2 - $long1;
        $cdelta = cos($delta);
        $sdelta = sin($delta);

// вычисления длины большого круга
        $y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
        $x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;

//
        $ad = atan2($y, $x);
        $dist = $ad * $earth_radius;

        return $dist;

    }

    public function inRange($dist = 0.1)
    {
        $longitude = $this->lon ?? 0;
        $latitude = $this->lat ?? 0;

        $lon1 = $longitude - $dist / abs(cos(rad2deg($latitude)) * 111.0); # 1 градус широты = 111 км
        $lon2 = $longitude + $dist / abs(cos(rad2deg($latitude)) * 111.0);
        $lat1 = $latitude - ($dist / 111.0);
        $lat2 = $latitude + ($dist / 111.0);

        $position = Shelter::whereBetween('latitude', [$lat1, $lat2])
            ->whereBetween('longitude', [$lon1, $lon2])
            ->where('id', $this->id)
            ->first();

        return !is_null($position);

        /// $profiles = UserProfile.objects.filter(lat__range=(lat1, lat2)).filter(lon__range=(lon1, lon2))

        /*
        SET @lat = 51.526613503445766; # дано в условии
SET @lng = 46.02093849218558;
SET @half= [10 км в радианах] / 2 ;


SELECT id
FROM points
WHERE lat BETWEEN @lat - @half AND @lat + @half
        AND lng BETWEEN @lng - @half AND @lng + @half;*/
    }
}
