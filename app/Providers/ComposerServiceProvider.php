<?php


	namespace App\Providers;


	use App\Models\City;
	use Illuminate\Support\Facades\Route;
	use Illuminate\Support\Facades\View;
	use Illuminate\Support\ServiceProvider;
	use Jenssegers\Agent\Agent;

	class ComposerServiceProvider extends ServiceProvider
	{
		public function boot(){

			View::composer(['public.navigation'], function($view){
				$uri = Route::current()->uri();
				$needsRegister = ($uri === "/" or $uri === "city/{city}");
				$agent = new Agent();
				$isMobile = $agent->isMobile();
				$cities = City::whereHas('events', function ($query){
				$query->upcoming();
				})->orderBy('name')->pluck('name');
				if($cities->count() <= 9){
					$view->with('cities_prox', $cities)->with('isMobile', $isMobile)->with('needsRegister', $needsRegister);
				}else{
					$view->with('isMobile', $isMobile)->with('needsRegister', $needsRegister);
				}



			});

			View::composer(['public.index'], function($view){
				$agent = new Agent();
				$isMobile = $agent->isMobile();
				$view->with('isMobile', $isMobile)->with('agent', $agent);
			});
		}
	}