<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $bookedDates = $this->getBookedDates();

        return view('reservations.create', [
            'rooms' => Room::select('id', 'name', 'price_night as price')->get(),
            'bookedDates' => $bookedDates
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = request(['start_date', 'end_date']);
        $attributes['user_id'] = Auth::user()->id;

        // validate request

        // Get total price
        $nights = date_diff(
            date_create($request['start_date']),
            date_create($request['end_date'])
        )->format('%a');

        $priceNight = Room::findOrFail($request['room_id'])->value('price_night');
        $attributes['price_total'] = $nights * $priceNight;

        $attributes['is_payed'] = 0;

        $reservation = new Reservation($attributes);
        $reservation->save();

        $reservation->room()->attach($request['room_id'], ['unit_price' => $priceNight, 'vat' => 0, 'quantity' => $nights]);

        return redirect(route('reservations.show', ['reservation' => $reservation]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function show(Reservation $reservation)
    {
        return view('admin.reservations-show', ['reservation' => Reservation::findOrFail($reservation->id)]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function edit(Reservation $reservation)
    {
        return view('admin.reservations-edit', ['reservation' => Reservation::findOrFail($reservation->id)]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reservation $reservation)
    {
        // validate request
        // save update to db
        // return message
        return 'Reservering informatie is aangepast';
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reservation $reservation)
    {
        Reservation::destroy($reservation);
        return redirect('admin.dashboard');
    }

    protected function getBookedDates()
    {
        $reservations = Reservation::where('end_date', '>', date('Y-m-d'))->get();

        $bookedDates = [];

        foreach ($reservations as $reservation) {
            $begin = new DateTime($reservation->start_date);
            $end = new DateTime($reservation->end_date);

            // $end = $end->modify('+1 day');

            $interval = new DateInterval('P1D');
            $daterange = new DatePeriod($begin, $interval, $end);

            foreach ($daterange as $date) {
                array_push($bookedDates, $date->format('Y-m-d'));
            }
        }

        return $bookedDates;
    }
}
