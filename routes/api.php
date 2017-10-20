<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/login/validate', 'AuthController@validateLogin')->name('login.validate');
Route::get('/register/validate', 'AuthController@validateRegister')->name('register.validate');

Route::group(['middleware' => 'auth'], function () {
    Route::post('/payments/store', 'PaymentController@store')->name('api.payments.store');
    Route::get('/payments/status', 'PaymentController@status')->name('api.payments.status');

    Route::post('/payout/store', 'PayoutController@store')->name('api.payouts.store');
    Route::post('/payout/status', 'PayoutController@status')->name('api.payouts.status');

    Route::get('/mining/current_rate/{base}/{target}/{amount?}', 'ExchangeController@currentRate')->name('mining.current_rate');
    Route::post('/mining/exchange', 'ExchangeController@store')->name('mining.transfer');
    Route::post('/mining/payout', 'Api\Payouts\PayoutsCryptoController@store')->name('mininig.payout');
    Route::post('/mining/transfer', 'Api\Payouts\PayoutsCryptoController@store');

    Route::post('/transfer/store', 'TransfersController@store')->name('transfer.store');

    Route::get('/matrix/show', 'MatrixController@treeValues')->name('tree.values');
    Route::get('/matrix/loadNode', 'MatrixController@loadNode')->name('tree.load_node');
    Route::get('/matrix/info', 'MatrixController@info')->name('tree.info');

    Route::post('/users/settings', function () {
//        $this->validate(request(), [
//            'reinvest_value' => ['required','numeric', 'min:'.config('rockwall.reinvest_limit'), 'max:100'],
//            'shields_value' => ['required','numeric','min:'.config('rockwall.shields_limit'), 'max:100'],
//        ]);

        Auth::user()->reinvest = request('reinvest_value');
        Auth::user()->shields = request('shields_value');
        Auth::user()->save();

        return response()->json(['code' => 0, 'message' => trans('rockwall.success')]);
    })->name('users.settings');


    Route::group(['prefix' => 'invests'], function () {
        Route::group(['prefix' => 'mining'], function () {
            Route::post('store', 'ContractsController@miningStore');
        });
        Route::group(['prefix' => 'passive'], function () {
            Route::post('store', 'ContractsController@passiveStore');
        });
        Route::group(['prefix' => 'matrix'], function () {
            Route::post('store', 'MatrixController@shieldBought');
            Route::get('callback', 'MatrixController@callback')->name('tree.callback');
        });
    });
});

Route::group(['middleware' => 'admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {

    /**
     * This is used to get all new withdrawing requests from mining
     */
    Route::get('/mining/awaiting', function () {
        $payouts = \App\Models\Payout::where('f_status', 1)->where('f_currency', 'eth')->orderBy('h_id', 'desc')->get();
        $payouts->load(['author']);
        $payouts->each(function ($q) {
            $q->author->makeVisible(['id']);
        });

        return response()->json(['payouts' => $payouts]);
    })->name('payouts.awaiting');

    /**
     * This is used to get all payouts request
     */
    Route::get('/payouts/awaiting', function () {
        $payouts = \App\Models\Payout::where('f_status', '>', 0)->where('f_currency', 'BTC')->orderBy('h_id', 'desc')->get();
        $payouts->load(['author']);
        $payouts->each(function ($q) {
            $q->author->makeVisible(['id']);
        });

        return response()->json(['payouts' => $payouts]);
    })->name('payouts.awaiting');

    /**
     * Payouts
     */
    Route::group(['prefix' => 'payouts', 'as' => 'api.payouts.'], function () {
        Route::post('/accept', 'Api\PayoutsController@accept')->name('accept');
        Route::post('/delay', 'Api\PayoutsController@delay')->name('delay');
        Route::post('/cancel', 'Api\PayoutsController@cancel')->name('cancel');
    });

    Route::group(['prefix' => 'charts', 'as' => 'charts.'], function () {
        Route::get('passive', function () {
            $values = [];
            $values['total'] = [];
            $values['total']['invest'] = floor_precission(\App\Models\Invests\InvestPassive::where('f_status', '>', 0)->sum('f_amount_usd'), 2);
            $values['total']['daily_invest'] = floor_precission(\App\Models\CalculationPassive::where('f_status', '>', 0)->sum('f_daily_invest_amount_usd'), 2);
            $values['total']['users_earned'] = \floor_precission(App\Models\Accruals\AccrualPassive::where('f_status', '>', 0)->sum('f_accrual_amount'), 2);
            $values['total']['company_earned'] = floor_precission(\App\Models\CalculationPassive::where('f_status', '>', 0)->sum('f_company_amount_usd'), 2);

            $values['values'] = [];
            $calculations = \App\Models\CalculationPassive::all();
            foreach ($calculations as $calculation) {
                $values['values'][] = [
                    'day' => $calculation->f_calculated_at->toDatetimeString(),
                    'invest' => $calculation->f_total_invested_amount_usd,
                    'daily_invest' => $calculation->f_daily_invest_amount_usd,
                    'daily_income' => $calculation->f_daily_income_amount_usd,
                    'company_earned' => $calculation->f_company_amount_usd,
                    'users_earned' => \App\Models\Accruals\AccrualPassive::where('e_calculations_passive_id', $calculation->id)->sum('f_accrual_amount'),
                ];
            }

            return response(json_encode($values, false));
        })->name('passive');

        Route::get('mining/{currency}', function ($currency) {

            $mining = \App\Models\Types\Mining::where('f_currency', $currency)->get();
            $mining_ids = $mining->pluck('id');

            $invests = \App\Models\Invests\InvestMining::whereIn('e_types_mining_id', $mining_ids)->where('f_status', '>', 0)->get();

            $values = [];
            $last_total = 0;
            $current_date = \Carbon\Carbon::createFromDate('2000', 1, 1)->toDateString();
            foreach ($invests as $invest) {
                if ($current_date < $invest->created_at->toDateString()) {
                    $last_total = isset($values[$current_date])?$values[$current_date]['total']:0;
                    $current_date = $invest->created_at->toDateString();
                }
                if (!isset($values[$current_date]['total'])) {
                    $values[$current_date]['total'] = $last_total;
                }
                $values[$current_date]['total'] += $invest->f_amount_unit;
            }

            $response = [];
            $response['values'] = [];
            foreach($values as $date => $total) {
                $response['values'][] = [
                    'date' => $date,
                    'total' => $total['total'],
                ];
            }

            return response(json_encode($response, false));
        })->name('passive');
    });
});

Route::post('/bitcoinapi/callback', 'PaymentController@paymentStatus'); // TODO: Should be deleted in future but actually this is hard set in panel.bitcoinapi.co
Route::post('/bitcoinapi/payment/callback', 'PaymentController@paymentStatus');
Route::post('/bitcoinapi/payout/callback', 'PayoutController@payoutStatus');

Route::post('/perfectmoney/status', 'PerfectMoneyController@status');
Route::post('/perfectmoney/ok', 'PerfectMoneyController@ok');
Route::post('/perfectmoney/failed', 'PerfectMoneyController@failed');

Route::post('/advcash/status', 'AdvCashController@status');
Route::post('/advcash/ok', 'AdvCashController@ok');
Route::post('/advcash/failed', 'AdvCashController@failed');
