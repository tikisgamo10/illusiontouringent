<?php

namespace App\Models;

use App\Contracts\UrlShortener\UrlShortener;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
		'name',
	    'date',
	    'description',
	    'city_id',
	    'venue_id',
	    'reminder_description',
    ];

	protected $casts = ['four_week_reminder_sent' => 'boolean', 'six_week_reminder_sent' => 'boolean'];
    protected $dates = ['date'];
    protected $appends = ['dateFormatted', 'cityName', 'venueName'];
    protected $hidden = ['created_at', 'updated_at', 'city_id', 'venue_id'];

    public function city(){
    	return $this->belongsTo('App\Models\City');
    }

	public function venue(){
		return $this->belongsTo('App\Models\Venue');
	}

	public function photo(){
    	return $this->hasOne('App\Models\Photo');
	}

	public function prices(){
    	return $this->hasMany('App\Models\Price');
	}

	public function ticket_sellers(){
    	return $this->belongsToMany('App\Models\TicketSeller')->withPivot('website');
	}

	public function ticketSellersWithShortUrl(){
		return $this->belongsToMany('App\Models\TicketSeller')->withPivot('website')->wherePivot('website', 'LIKE', 'https://goo.gl%');
	}

	public function getPrices(){
    	$count = $this->prices->count();
    	$counter = 0;
    	$prices_string = '';
    	foreach($this->prices as $price){
    		$prices_string .= $price->price;
    		if($counter !== $count - 1){
    			$prices_string .= ', ';
		    }
		    $counter++;
	    }

	    return $prices_string;
	}

	public function getDateFormatted(){
    	if($this->date->gt(Carbon::now('America/Chicago'))){
		    $date = $this->date->formatLocalized('%A, %b %e @ %l:%M');
		    $date .= 'PM';
		    return ucwords($date);
	    }else{
		    $date = $this->date->formatLocalized('%A, %b %e, %Y');
    		return ucwords($date);
	    }

    }

    public function getDateFormattedAttribute(){
	    $date = $this->date->formatLocalized('%A, %b %e, %Y');
	    return ucwords($date);
    }

    public function getCityNameAttribute(){
    	return $this->city->name;
    }

	public function getVenueNameAttribute(){
		return $this->venue->name;
	}
}