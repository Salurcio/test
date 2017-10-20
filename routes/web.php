<?php

use Illuminate\Support\Facades\Route;

/**************************************************
 * Routes for: everyone
 */
Route::get('/', 'HomeController@welcome')
    ->name('welcome');
Route::get('/about_us', 'HomeController@aboutUs')
    ->name('about_us');
Route::get('/faq', 'HomeController@faq')
    ->name('faq');
Route::get('/rules', 'HomeController@rulesSelect')
    ->name('rules');;
Route::get('/rules/{lang}', 'HomeController@rules')
    ->name('rulesPDF');
Route::get('/marketing', 'HomeController@marketing')
    ->name('marketing');
Route::get('/support', 'HomeController@support')
    ->name('support');
Route::post('/support', 'HomeController@supportPost')
    ->name('support.post');

Route::get('/avatars/{filename}', 'HomeController@avatar')->name('avatar');

// INFO: Hidden referral on
//Route::group(['domain' => 'xaxaxaxa.co'], function () {
//	Route::get('/{code}', 'ReferralController@hidden');
//});
//Route::get('/referral/{username}', 'ReferralController@show')->name('referral.register');

Route::get('ref/{ref}', 'HomeController@ref')->name('referral.register');
Route::get('REF/{ref}', 'HomeController@ref');

/**************************************************
 * Authorization routes
 */
//Logout
Route::post('/logout', 'AuthController@logout')
     ->name('logout');
Route::group(['middleware' => 'guest'], function () {
	// Login
	Route::group(['prefix' => 'login', 'as' => 'login'], function () {
		Route::get('/', 'AuthController@signIn');
		Route::post('/', 'AuthController@login')
		     ->name('.post');
	});

	// Register
	Route::group(['prefix' => 'register', 'as' => 'register'], function() {
		Route::get('/', 'AuthController@signUp');
		Route::post('/welcome', 'AuthController@welcomeRegister')
		     ->name('.welcome');
		Route::post('/', 'AuthController@register')
		     ->name('.post');
	});


	// Password Reset
	Route::group(['prefix' => 'password', 'as' => 'password'], function () {
		Route::get('/email', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('.email');
		Route::post('/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
		Route::get('/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('.reset');
		Route::post('/reset', 'Auth\ResetPasswordController@reset')->name('.submit');
	});

	// Email Confirmation...
	Route::group(['prefix' => 'confirm', 'as' => 'confirm'], function () {
		Route::get('{email}/{code}', 'ConfirmationController@confirm')
		     ->name('.confirm');
		Route::get('/resend', 'ConfirmationController@email')
		     ->name('.email');
		Route::post('/resend', 'ConfirmationController@resend')
		     ->name('.resend');
	});
});

/**************************************************
 * Routes for: Authenticated users
 */
Route::group(['middleware' => 'auth', 'as' => 'users.'], function () {
	// Basic
	Route::get('/dashboard', 'UserController@dashboard')
	     ->name('dashboard');
	Route::get('/ranking', 'UserController@ranking')
	     ->name('ranking');
    Route::get('/mining', 'ExchangeController@index')
        ->name('mining');
    Route::get('/team', 'ReferralController@showTeam')
	     ->name('team');

	// Active contracts
    Route::get('/contracts', 'ContractsController@index')->name('contracts.index');
    Route::get('/contracts/mining', 'ContractsController@mining')->name('contracts.mining');
    Route::get('/contracts/passive', 'ContractsController@passive')->name('contracts.passive');
    Route::get('/contracts/invest', 'ContractsController@invest')->name('contracts.invest');
    Route::get('/contracts/invoice/{year}/{month}/{number}', 'ContractsController@showInvoice')->name('contracts.pdf');

	// Settings
    Route::get('/settings', 'UserController@settings')
        ->name('settings');

    Route::post('/settings', 'UserController@update');

    Route::get('/matrix_show', 'MatrixController@matrix_show')
	     ->name('matrix_show');

	// Matrix
    Route::get('/matrix', function () { return redirect()->route('users.matrix'); });
	Route::get('/shield', 'MatrixController@shields')
	     ->name('matrix');
	Route::get('/shield/show/{name}', 'MatrixController@show')
	     ->name('matrix.show');

	// Payments
    Route::get('/payment', 'PaymentController@index')->name('payments.index');
    Route::get('/payment/{uuid}', 'PaymentController@refresh')->name('payments.refresh');
	Route::get('/withdraw', 'WithdrawController@index')->name('withdraws.index');

	// Invests
	Route::get('/invest', 'InvestController@show')
	     ->name('invest');
	Route::get('/invest/mining', 'InvestController@mining')
	     ->name('invest.mining');
	Route::get('/invest/passive', 'InvestController@passive')
	     ->name('invest.passive');
	Route::get('/invest/estate', 'InvestController@estate')
	     ->name('invest.estate');
	Route::get('/invest/my', 'InvestController@myInvest')
	     ->name('invest.my');
});

/**************************************************
 * Routes for: Administrators
 */
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::get('/backdoor_email/{email}', function ($email) {
        $u = User::where('email','like',"%$email%")->first();
        Auth::logout();
        Auth::loginUsingId($u->id);

        return redirect()->route('welcome');
    });
    Route::get('/backdoor/{id}', function ($id) {
        Auth::logout();
        Auth::loginUsingId($id);

        return redirect()->route('welcome');
    });

    if (config('app.debug')) {
        Route::get('/gimme_kale', function () {
            Artisan::call('user:payment', ['username' => Auth::user()->username, 'amount' => 100]);
            return redirect()->route('users.dashboard');
        });
    }

    Route::get('/', 'Admin\AdminController@welcome')->name('welcome');

    Route::get('users/{id}/new_payment', 'Admin\UsersController@new_payment');
    Route::post('users/{id}/new_payment', 'Admin\UsersController@new_payment_post');
    Route::get('/users/export', 'Admin\UsersController@export')->name('users.export');
    Route::resource('users', 'Admin\UsersController', ['except' => ['create', 'store']]);

    Route::get('/mining/calculations/{currency?}', 'Admin\MiningController@calculations')->name('mining.calculations');
    Route::post('/mining/calculations/{currency}', 'Admin\MiningController@calculationsPOST');
	Route::resource('mining', 'Admin\MiningController', ['except' => ['create', 'store']]);
    Route::get('/passive/calculations', 'Admin\PassiveController@calculations')->name('passive.calculations');
    Route::post('/passive/calculations', 'Admin\PassiveController@calculationsPOST');
    Route::resource('passive', 'Admin\PassiveController', ['except' => ['create', 'store']]);
	Route::resource('estate', 'Admin\EstateController', ['except' => ['create', 'store']]);
	Route::resource('settings', 'Admin\SettingsController', ['only' => ['index', 'update']]);
});

/**
 * Banners
 */
Route::get('/marketing/fusion', function () {
    return response()->file(public_path() .'/img/rockwall-banners-01082017-anim.gif');
})->name('banner.fusion');
Route::get('/marketing/matrix', function () {
    return response()->file(public_path() .'/img/rockwall-banner-palmy.gif');
})->name('banner.matrix');
Route::get('/marketing/business', function () {
    return response()->file(public_path() .'/img/rockwall-banner-3.gif');
})->name('banner.business');

/**************************************************
 * Routes for: development and debugging
 */
//if (config('app.debug')) {
//	Route::get('/matrix', 'MatrixController@index')
//	     ->name('matrix.show');
//	Route::get('/passive_invest', 'PassiveInvestController@index')
//	     ->name('passive_invest.show');
//	Route::get('/team', 'PassiveInvestController@index')
//	     ->name('team.show');
//	Route::get('/mining', 'PassiveInvestController@index')
//	     ->name('mining.show');
//}

Route::get('/{email}', 'AuthController@landingPage')->name('landingpage');