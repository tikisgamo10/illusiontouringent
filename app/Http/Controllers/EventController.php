<?php

namespace App\Http\Controllers;

use App\Contracts\TextMessage\TextMessager;
use App\Contracts\UrlShortener\UrlShortener;
use App\Models\City;
use App\Models\Event;
use App\Models\Folder;
use App\Models\Photo;
use App\Models\Price;
use App\Models\TicketSeller;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\TextMessageFailure;
use App\Services\TextMessage\Exceptions\TextMessageException;
use App\Services\Url\Exceptions\UrlGetClicksException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
    	$sort = $request->query('sort') ?? 'date';
		return view('admin.events.index', compact('sort'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    	$cities = City::orderBy('name')
	                    ->pluck('name', 'id')
		                ->all();
	    $venues_raw = Venue::orderBy('name')
		    ->get();
	    $venues = array();
	    foreach($venues_raw as $venue){
		    $venues[$venue->id] = "{$venue->name} en {$venue->city->name}";
	    }
    	return view('admin.events.create', compact('cities', 'venues'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
        	'venue_id' => 'bail|required|numeric|exists:venues,id',
	        'name' => 'required|string',
	        'date' => 'required|string',
	        'description' => 'required|string',
	        'reminder_description' => 'required|string',
	        'prices' => 'required|string',
	        'illusion' => 'nullable|boolean',
        ]);

        $illusion = $request->illusion ?? 0;

	    try{
		    $date = Carbon::parse($request->date)->toDateTimeString();
	    }catch(\Exception $e){
		    return view('errors.400', ['message' => "No se pudo procesar la fecha. Asegúrese de que esté en el formato indicado"]);
	    }

        $event = Event::create([
        	'name' => $request->name,
	        'date' => $date,
	        'description' => $request->description,
	        'reminder_description' => $request->reminder_description,
	        'venue_id' => $request->venue_id,
	        'illusion' => $illusion
        ]);

        $prices = explode(', ', $request->prices);

        foreach ($prices as $price){
        	$event->prices()->create(['price' => $price]);
        }

	    return redirect('/admin/photos/create');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    	$event = Event::findOrFail($id);
	    $date = $event->date->formatLocalized('%b %e, %Y %l:%M%p');
        return view('admin.events.edit', compact('event', 'date'));
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

	    $request->validate([
		    'venue_id' => 'bail|required|numeric|exists:venues,id',
		    'name' => 'required|string',
		    'date' => 'required|date',
		    'description' => 'required|string',
		    'reminder_description' => 'required|string',
		    'illusion' => 'nullable|boolean',
		    'prices' => 'required|string',
	    ]);

        $event = Event::findOrFail($id);

	    /**
	     * Updates the event info
	     */
	    try{
		    $date = Carbon::parse($request->date)->toDateTimeString();
	    }catch(\Exception $e){
		    return view('errors.400', ['message' => "No se pudo procesar la fecha. Asegúrese de que esté en el formato indicado"]);
	    }
	    $event->update([
		    'name' => $request->name,
		    'date' => $date,
		    'description' => $request->description,
		    'venue_id' => $request->venue_id,
		    'reminder_description' => $request->reminder_description,
		    'illusion' => $request->illusion,
	    ]);

	    /**
	     * Updates the event's prices
	     */
	    $prices = collect(explode(', ', $request->prices));
	    $event->updatePrices($prices);



	    return redirect('/admin/events');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Event::findOrFail($id)->delete();
        return redirect('/admin/events');
    }


	/**
	 *  Attach a ticket seller to an event
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param  int $id
	 * @param UrlShortener $urlShortener
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function eventTicketSeller(Request $request, $id, UrlShortener $urlShortener){
	    if($request->isMethod('get')){
	    	if($urlShortener->isAccessTokenExpired()){
	    		session(['url' => 'admin/eventTicketSeller/'. $id]);
	    		return redirect('admin/oauth');
		    }
			$event = Event::findOrFail($id);
	    	$ticket_sellers = TicketSeller::orderBy('name')->pluck('name', 'id')->all();
	    	return view('admin.events.ticket_seller_create', compact('ticket_sellers', 'event'));

	    }elseif($request->isMethod('post')){
		    $request->validate([
			    'ticket_seller_id' => 'required|numeric|exists:ticket_sellers,id',
			    'website' => 'nullable|url',
		    ]);
			$event = Event::findOrFail($id);

			$website = !is_null($request->website) ? $urlShortener->shortenUrlWithOauth($request->website) : null;

			$event->ticket_sellers()->attach($request->ticket_seller_id, ['website' => $website]);


			return redirect('/admin/events');

	    }
    }

	/**
	 * @return string
	 */
	public function api_index(){
		return Event::upcoming()->orderBy('date')->get()->toJson();
	}

	public function api_last_created(){
	    $date = Event::orderBy('created_at', 'desc')->pluck('created_at')->first();
	    return json_encode(['lastCreated' => $date->diffForHumans()]);
    }

    public function apiGetByAttribute($attribute = 'id', $value){
		$event = Event::where($attribute, $value)->get()->last();
		if(!$event){
			abort(404);
		}

		return $event;
    }

    public function toggleEventStatus($id){
		$event = Event::findOrFail($id);
		$event->active = !$event->active;
		$event->save();
		return response('Success', 200);
    }
	/**
	 * @param UrlShortener $urlShortener
	 */
	public function RefreshUrlClicks(UrlShortener $urlShortener){
		$events = Event::upcoming()->get();
		foreach($events as $event){
			if($ticketSeller = $event->ticketSellersWithShortUrl->first()){
				try{
					$event->urlClicks = (int)$urlShortener->getClicks($ticketSeller->pivot->website);
				}catch(UrlGetClicksException $e){
				/**
				 * Getting url stats has been unreliable due to Google's servers, no logging will be done for now
				 */
				}
				$event->save();
			}
		}
    }

    public function sendTextReminders(TextMessager $textMessager){
	    $two_weeks = Carbon::now()->addWeeks(2)->toDateString();
	    $two_weeks_comp = Carbon::now()->addWeeks(2)->subDay()->toDateString();
	    $one_week = Carbon::now()->addWeek()->toDateString();
        $one_week_comp = Carbon::now()->addWeek()->subDay()->toDateString();

	    $events_two_weeks = Event::public()->whereDate('date', '<=', $two_weeks)->whereDate('date', '>', $two_weeks_comp)->where('two_week_reminder_sent', 0)->get();
	    $events_one_week = Event::public()->whereDate('date', '<=', $one_week)->whereDate('date', '>', $one_week_comp)->where('one_week_reminder_sent', 0)->get();

	    foreach($events_two_weeks as $event){

	        $subscribers = SuscriberController::phoneSubscribersInCity($event->city->id);

            if(empty($event->text_message)){
                $event->two_week_reminder_sent = 2;
                $event->save();
                report(new TextMessageException('Automated texts failed for event: ' . $event->id . ' No message in event, no texts sent.'));
                continue;
            }

            $event->two_week_reminder_sent = 1;
            $event->save();

            foreach($subscribers as $subscriber){
                try{
                    $textMessager->text($subscriber, $event->text_message);
                }catch(TextMessageException $e){
                    $event->two_week_reminder_sent = 2;
                    $event->save();
                    report(new TextMessageException('Automated messaging failed for event: ' . $event->id . ' At subscriber: ' . $subscriber->id));
                    break;
                }

            }


        }

        foreach($events_one_week as $event){

            $subscribers = SuscriberController::phoneSubscribersInCity($event->city->id);

            if(empty($event->text_message)){
                $event->one_week_reminder_sent = 2;
                $event->save();
                report(new TextMessageException('Automated texts failed for event: ' . $event->id . ' No message in event, no texts sent.'));
                continue;
            }

            $event->one_week_reminder_sent = 1;
            $event->save();

            foreach($subscribers as $subscriber){
                try{
                    $textMessager->text($subscriber, $event->text_message);
                }catch(TextMessageException $e){
                    $event->one_week_reminder_sent = 2;
                    $event->save();
                    report(new TextMessageException('Automated messaging failed for event: ' . $event->id . ' At subscriber: ' . $subscriber->id));
                    break;
                }

            }


        }
    }
}
