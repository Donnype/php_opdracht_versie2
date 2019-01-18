<?php

namespace App\Http\Controllers;

use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use App\availabilities;
use App\prices;
use Illuminate\Support\Facades\DB;

class LOS_Controller extends Controller
{

    /* Old index function
     *

    public function index()
    {
        $LOS_array = self::make_LOS_array();
        return view('LOS', [
            'LOS_array' => $LOS_array
        ]);
    }
    */

    /*
     * Old make_LOS_array() function

    public static function make_LOS_array()
    {
        $availabilities = availabilities::all();

        foreach ($availabilities as $availabilities_row){

            $date = $availabilities_row->date;

            $pricing_rows = DB::table('prices')->where([
                ['period_from', '<=', $date],
                ['period_till', '>=', $date]
            ])->get();

            for ($persons = 1; $persons <= 6; $persons++) {

                if ($availabilities_row->quantity == 0){

                    $LOS_array[] = [$date, $persons, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
                }

                else {

                    $LOS_row = [$date, $persons];

                    for ($night = 3; $night <= 23; $night++) {

                        $LOS_row[$night] = self::calculate_price($date,$persons,$night - 2, $pricing_rows)/100;

                    }
                    $LOS_array[] = $LOS_row;
                }
            }
        }
        return $LOS_array;
    }
    */

    /*
     * Old calculate_price function

    public static function calculate_price($date, $persons, $night, $pricing_rows)
    {

            // Return 0 if there is no pricing data available for this date

        if(count($pricing_rows) == 0){
            return 0;
        }

        else{

                // Finding the pricing row for the right number of people and the highest minimum stay

            $right_row = (object) array('minimum_stay' => 0);

            foreach ($pricing_rows as $pricing_row) {
                if (in_array("$persons", explode("|", $pricing_row->persons))
                    and $pricing_row->minimum_stay <= $night
                    and $right_row->minimum_stay <= $pricing_row->minimum_stay) {

                    $right_row = $pricing_row;
                }
            }

                // Calculating the actual price for the number of nights left in the current period

            $persons_array = explode("|", $right_row->persons);
            $extra_persons = array_search("$persons", $persons_array);

            $period = 1 + (strtotime($right_row->period_till) - strtotime($date))/86400;

            $nights_for_price = min($period, $night);

            $price = $nights_for_price*( $right_row->amount/$right_row->duration + $extra_persons*$right_row->extra_person_price);

                // If there are nights left, calculate the price of the remaining nights recursively with this function

            if($night > $nights_for_price) {



                $pricing_rows = DB::table('prices')->where([
                    ['period_from', '<=', date("Y-m-d", strtotime($date." + $nights_for_price days"))],
                    ['period_till', '>=', date("Y-m-d", strtotime($date." + $nights_for_price days"))]
                ])->get();

                $rest = self::calculate_price(date("Y-m-d", strtotime($date." + $nights_for_price days")), $persons, $night - $nights_for_price, $pricing_rows);

                    // If there is no pricing data of any of the nights, the function returns 0 as well

                $price = $rest == 0 ? 0 : $price + $rest;
            }
            return $price;
        }

    }
    */

    public function index()
    {
        $LOS_array = self::make_LOS_array();
        return view('LOS2', [
            'LOS_array' => $LOS_array
        ]);
    }

    /**
     * @return array
     */
    public static function make_LOS_array()
    {
        $prices = DB::table('prices')->where([['persons', '=', '1']])->get();

        $dates = ['2017-05-18','2017-07-04','2017-07-05','2017-07-06','2017-07-07','2017-07-08','2017-07-09','2017-07-10'];

        for ($persons = 1; $persons <= 6; $persons++) {
            ${"LOS_"."$persons"}[0] = $dates;
            ${"LOS_"."$persons"}[1] = array_fill(0,8,$persons);
        }

        for ($nights = 1; $nights <= 21; $nights++) {

            foreach ($prices as $pricing_row) {

                $period = (strtotime($pricing_row->period_till) - strtotime($pricing_row->period_from)) / 86400;

                for ($night = 0; $night <= $period; $night++) {

                    if ($nights >= $pricing_row->minimum_stay and $nights <= $pricing_row->maximum_stay) {

                        $date = date("Y-m-d", strtotime($pricing_row->period_from . "+ $night days"));
                        $price = 0;

                        if ($nights == $pricing_row->duration and $nights == $pricing_row->minimum_stay) {
                            $price = $pricing_row->amount / $pricing_row->duration * $nights;
                        }

                        elseif ($nights > $pricing_row->minimum_stay) {

                            $new_date = date("Y-m-d", strtotime($date . " + 1 day"));

                            if (array_key_exists($new_date, $LOS_1[3])
                                and $LOS_1[$nights + 1][$new_date] != 0
                            ) {

                                $price = round($pricing_row->amount + 100 * ($LOS_1[$nights - $pricing_row->duration + 2][$new_date]));
                            }
                        } elseif (strtotime($date . "+ $pricing_row->minimum_stay days - 1 day") <= strtotime($pricing_row->period_till)) {
                            $price = $pricing_row->amount * $nights;
                        }

                        $night_row[$date] = $price / 100;
                    }
                }
            }
            $LOS_1[$nights + 2] = $LOS_2[$nights + 2] = $night_row;

            for ($persons = 3; $persons <= 6; $persons++) {

                $extra_person_night_row = [];

                foreach ($night_row as $value) {
                    $extra_person_night_row[] = $value == 0 ? 0 : $value + 15 * ($persons - 2) * $nights;
                }

                ${"LOS_" . "$persons"}[$nights + 2] = $extra_person_night_row;
            }
        }
        for ($persons = 1; $persons <= 6; $persons++) {
            ${"LOS_"."$persons"} = self::transpose(${"LOS_"."$persons"});

            $LOS_array[] = ${"LOS_"."$persons"};
        }

        return $LOS_array;
    }

    /**
     * @param $array
     * @return array
     */
    public static function transpose($array) {
        return array_map(null, ...$array);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
