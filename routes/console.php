<?php

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

use App\Services\InvestService;
use App\Models\Invests\InvestMatrix;
use App\Models\Types\Matrix;
use App\Models\Types\Mining;
use App\Models\Types\Passive;
use App\Exceptions\MyException;
use App\Models\User;
use \Carbon\Carbon;

Artisan::command('init {--production}', function ($production) {
    if (config('app.debug')) {
        $this->info('Running on dev server...');
    } else {
        $this->info("Running on PRODUCTION server...");

        if ($this->ask("Type secret code: ") !== '845673') {
            $this->error('Bad code, sorry!');
            return false;
        }
    }

    if (!$this->confirm("Are you sure? Everything can be erase!")) {
        $this->error("Abort by user.");
        return false;
    }

    if (!File::isDirectory(storage_path('app/avatars'))) {
        File::makeDirectory(storage_path('app/avatars/'));
        $this->comment('Created directory: ' . storage_path('app/avatars'));
    }

    $this->info('Migrating database...');
    $this->call('migrate:refresh');

    $users = [];

    $this->info('Creating First Administrator Account...');
    $users['admin'] = factory(\App\Models\User::class)->create([
        'id' => 1,
        'username' => 'admin',
        'name' => 'Rockwall',
        'surname' => 'Investments',
        'email' => 'admin@rockwall.dev',
        'password' => bcrypt('Qwerty12#'),
        'confirmation_code' => null,
        'level' => 10,
        'referral_id' => null,
    ]);

    if (!$production) {
        $this->info('Creating default users...');
        factory(App\Models\User::class, 3)->create();

        $this->info('Creating confirmed test account with password: Qwerty12#');
        if (!$production) {

            $users['test'] = factory(\App\Models\User::class)->create([
                'username' => 'test',
                'email' => 'test@test.dev',
                'password' => bcrypt('Qwerty12#'),
                'confirmation_code' => null,
                'level' => 6,
            ]);
        }
    }

    $this->call('init:shields');
    $this->call('init:mining');
    $this->call('init:passive');


//    $this->call('cache:clear');
//    $this->call('view:clear');

})
    ->describe('Run default seeds and everything else.');

Artisan::command('user:confirm {username} {--notify}', function ($username, $notify = false) {
    $user = User::where('username', $username)
        ->firstOrFail();

    if ($notify) {
        $user->sendConfirmationCode();
    }

    $user->confirmation_code = null;
    $user->save();

    $this->comment('User ' . $user->display_name . ' has been confirmed.');
})
    ->describe('Confirm user\'s emails from console without sending any messages');

Artisan::command('init:shields', function () {
    $this->comment('Creating default shields...');

    Matrix::create(['f_title' => 'ZIRCON', 'f_amount' => 10, 'f_direct_referral_profit' => 8, 'f_active' => true]);
    Matrix::create(['f_title' => 'GARNET', 'f_amount' => 25, 'f_direct_referral_profit' => 8, 'f_active' => true]);
    Matrix::create(['f_title' => 'OPAL', 'f_amount' => 50, 'f_direct_referral_profit' => 8, 'f_active' => true]);
    Matrix::create(['f_title' => 'TOPAZ', 'f_amount' => 75, 'f_direct_referral_profit' => 8, 'f_active' => true]);
    Matrix::create(['f_title' => 'AMBER', 'f_amount' => 100, 'f_direct_referral_profit' => 8, 'f_active' => false]);
    Matrix::create(['f_title' => 'SILVER', 'f_amount' => 150, 'f_direct_referral_profit' => 8, 'f_active' => false]);
    Matrix::create(['f_title' => 'GOLD', 'f_amount' => 200, 'f_direct_referral_profit' => 8, 'f_active' => false]);
    Matrix::create(['f_title' => 'SAPPHIRE', 'f_amount' => 500, 'f_direct_referral_profit' => 8, 'f_active' => false]);
    Matrix::create(['f_title' => 'RUBY', 'f_amount' => 1500, 'f_direct_referral_profit' => 8, 'f_active' => false]);
    Matrix::create(['f_title' => 'EMERALD', 'f_amount' => 6000, 'f_direct_referral_profit' => 8, 'f_active' => false]);
    Matrix::create(['f_title' => 'DIAMOND', 'f_amount' => 12000, 'f_direct_referral_profit' => 8, 'f_active' => false]);
});

Artisan::command('init:mining', function () {
    $this->comment('Creating default mining invests...');

    Mining::create(['f_title' => "MINI", 'f_currency' => 'eth', 'f_price_per_mhs' => 23, 'f_min' => 1, 'f_max' => 99, 'f_duration' => 500, 'f_activation_duration' => 30]);
    Mining::create(['f_title' => "LARGE", 'f_currency' => 'eth', 'f_price_per_mhs' => 21.5, 'f_min' => 100, 'f_max' => 599, 'f_duration' => 500, 'f_activation_duration' => 30]);
    Mining::create(['f_title' => "PREMIUM ", 'f_currency' => 'eth', 'f_price_per_mhs' => 20, 'f_min' => 600, 'f_max' => 4000, 'f_duration' => 500, 'f_activation_duration' => 30]);

    Mining::create(['deleted_at' => \Carbon\Carbon::now(), 'f_title' => "MINI", 'f_currency' => 'eth', 'f_price_per_mhs' => 25, 'f_min' => 1, 'f_max' => 99, 'f_duration' => 500, 'f_activation_duration' => 30]);
    Mining::create(['deleted_at' => \Carbon\Carbon::now(), 'f_title' => "LARGE", 'f_currency' => 'eth', 'f_price_per_mhs' => 24, 'f_min' => 100, 'f_max' => 599, 'f_duration' => 500, 'f_activation_duration' => 30]);
    Mining::create(['deleted_at' => \Carbon\Carbon::now(), 'f_title' => "PREMIUM ", 'f_currency' => 'eth', 'f_price_per_mhs' => 20, 'f_min' => 600, 'f_max' => 4000, 'f_duration' => 500, 'f_activation_duration' => 30]);
});

Artisan::command('init:passive', function () {
    $this->comment('Creating default passive projects...');

    Passive::create([
        'f_title' => "FUSION",
        'f_min' => 50,
        'f_max' => 100000,
        'f_profit_per_day' => 1.5,
        'f_duration' => 150,
        'f_activation_duration' => 5,
    ]);
});

Artisan::command('user:payment {username} {amount}', function ($username, $amount) {
    if (!env('sudo_mode')) {
        $this->error('Can\'t add $ in this environment!');
        return;
    }
    $user = User::where('username', $username)
        ->firstOrFail();

    $user->load(['wallets']);

    $now = \Carbon\Carbon::now();
    $operation_id = \DB::table('payments')->insertGetId([
        'e_user_id' => $user->id,
        'f_payment_method' => 'cash',
        'f_amount_usd' => $amount,
        'f_amount_usd_needed' => $amount,
        'f_status' => 3,
        'f_calculated' => $now,
        'h_created_at' => $now,
        'f_address' => '',
    ]);

//    $wallets = $user->wallets->keyBy('f_title');
//    $wallets['available']->f_amount = $wallets['available']->f_amount + $amount;
//
//    \DB::table('wallets_operations')->insert([
//        'e_user_id' => $user->id,
//        'e_wallet_id' => $wallets['available']->id,
//        'f_currency' => 'usd',
//        'f_operation_amount' => $amount,
//        'f_amount_before' => $wallets['available']->f_amount,
//        'f_operation_type' => "cash",
//        'f_operation_id' => $operation_id,
//    ]);
//
//    $wallets['available']->save();

});

Artisan::command('user:payout {username} {amount}', function ($username, $amount) {
    if (!env('sudo_mode')) {
        return;
    }
    $user = User::where('username', $username)
        ->firstOrFail();

    $user->load(['wallets']);

    $wallets = $user->wallets->keyBy('f_title');
    $wallets['available']->f_amount = $wallets['available']->f_amount - $amount;

    $operation_id = \DB::table('payouts')->insertGetId([
        'e_user_id' => $user->id,
        'f_amount_satoshi_paid' => 1000,
        'f_amount_usd' => $amount,
        'f_amount_final' => $wallets['available']->f_amount,
        'f_amount_satoshi_needed' => 1000,
        'f_fee' => 0,
        'f_uuid' => 0,
        'f_bitcoin' => 0,
        'f_address' => '',
    ]);


    \DB::table('wallets_operations')->insert([
        'e_user_id' => $user->id,
        'e_wallet_id' => $wallets['available']->id,
        'f_currency' => 'usd',
        'f_operation_amount' => (0 - $amount),
        'f_amount_before' => $wallets['available']->f_amount,
        'f_operation_type' => "buy contract",
        'f_operation_id' => $operation_id,
    ]);

    $wallets['available']->save();

});

Artisan::command('payment:accept {h_id}', function ($h_id) {
    $payment = \App\Models\Payment::find($h_id);

    $payment->f_status = 3;
    $payment->f_payment_method = 3;
    $payment->save();
    $user = \App\Models\User::find($payment->e_user_id);
    $user->load(['wallets']);
    $wallets = $user->wallets->keyBy('f_title');

    $wallets['available']->f_amount = $wallets['available']->f_amount + $payment->f_amount_usd;
    $wallets['available']->save();

    \DB::table('wallets_operations')->insert([
        'e_user_id' => $user->id,
        'e_wallet_id' => $wallets['available']->id,
        'f_currency' => 'usd',
        'f_operation_amount' => $payment->f_amount_usd,
        'f_amount_before' => $wallets['available']->f_amount,
        'f_operation_type' => "buy contract",
        'f_operation_id' => 0,
    ]);

    $this->comment('Payment for ' . $payment->f_amount_usd_needed . '$ accepted.');
});

Artisan::command('payment:confirm {address} {amount_usd}', function ($address, $amount_usd) {
    if (!env('sudo_mode')) {
        return;
    }

    $payments = \App\Models\Payment::where('f_address', $address)->where('f_amount_usd', $amount_usd)->get();

    if ($payments->count() <= 0) {
        $this->error('payment not found');
        return;
    }

    if ($payments->count() !== 1) {
        $this->error('not working sorry');
        return;
    }

    \App\Models\Payment::where('f_address', $address)->where('f_amount_usd', $amount_usd)->update(['f_status' => 3, 'f_payment_method' => 'console']);

    $this->comment('Success!');
});

Artisan::command('bot:fix_invests {username?}', function ($username = null) {
    $this->comment('Running bot...');
    $builder = new InvestMatrix;

    if ($username) {
        $user = User::where('username', $username)->first();
        if (!$user) {
            $this->error('User not found!');
            return;
        }
        $builder = $builder->where('e_user_id', $user->id);
    }

    $invests_service = app(InvestService::class);
    $artisan = $this;

    $builder->orderBy('e_user_id', 'desc')->orderBy('created_at', 'asc')->chunk(1, function ($invest) use ($artisan) {
        $invest = $invest->first();
        $invests = InvestMatrix::where('e_user_id', $invest->e_user_id)->where('e_types_matrix_id', $invest->e_types_matrix_id)->get();
        for ($i = 0; $i < $invests->max('f_is_subaccount'); $i++) {
            if (!$invests->where('f_is_subaccount', $i)->first()) {
                $artisan->comment('Change ' . $invest->f_is_subaccount . ' to ' . $i);
                $invest->update(['f_is_subaccount' => $i]);
                break;
            }
            $artisan->comment('No changes: ' . $invest->id);
        }
    });
});
Artisan::command('bot:create_matrixes', function () {

    /*
     * 
     * zapisuej aby nie zapomniec - uwaga do dominika - chyba f_is_subaccount zwieksza sie nawet gdy platnosc sie nie powiedzie
     */

    $matrixes = array(10, 25, 50, 75);

    $matrixNum = "(10,25)";


    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where  invests_matrix.f_status=1 and f_amount_usd in $matrixNum and users.id in (1,3,68, 32,542) and users.id!=2 and invests_matrix.id not in 
        (select e_invests_matrix from matrix_tree ) order by 
         FIELD(users.id,1,3,68, 32,542), users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) {
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);

    }

    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where  invests_matrix.f_status=1 and f_amount_usd in $matrixNum and  users.id<=524 and users.id!=2 and invests_matrix.id not in (select e_invests_matrix from matrix_tree ) order by users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) //32
    {
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);

    }


    /*
     * pierwsze matryce w kolejności rejestracji.
     */
    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where  invests_matrix.f_status=1 and f_amount_usd in $matrixNum and  invests_matrix.id not in (select e_invests_matrix from matrix_tree ) order by users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) {
        //echo($matrix->id."\r\n");
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);
        $i++;
        //if($i==10)
        //    die();

    }


    //----

    $matrixNum = "(50)";


    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where  invests_matrix.f_status=1 and f_amount_usd in $matrixNum and users.id in (1,3,68, 32,19,542) and users.id!=2 and invests_matrix.id not in 
        (select e_invests_matrix from matrix_tree ) order by 
         FIELD(users.id,1,3,68, 32,19,542), users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) {
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);

    }

    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where  invests_matrix.f_status=1 and f_amount_usd in $matrixNum and  users.id<=524 and users.id!=2 and invests_matrix.id not in (select e_invests_matrix from matrix_tree ) order by users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) //32
    {
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);

    }


    /*
     * pierwsze matryce w kolejności rejestracji.
     */
    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where   invests_matrix.f_status=1 and f_amount_usd in $matrixNum and  invests_matrix.id not in (select e_invests_matrix from matrix_tree ) order by users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) {
        //echo($matrix->id."\r\n");
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);
        $i++;
        //if($i==10)
        //    die();

    }


    $matrixNum = "(75)";


    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where  invests_matrix.f_status=1 and f_amount_usd in $matrixNum and users.id in (1,3,68, 32,19,29,33,542) and users.id!=2 and invests_matrix.id not in 
        (select e_invests_matrix from matrix_tree ) order by 
         FIELD(users.id,1,3,68, 32,19,29,33,542), users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) {
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);

    }

    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where  invests_matrix.f_status=1 and f_amount_usd in $matrixNum and  users.id<=524 and users.id!=2 and invests_matrix.id not in (select e_invests_matrix from matrix_tree ) order by users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) //32
    {
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);

    }


    /*
     * pierwsze matryce w kolejności rejestracji.
     */
    $res = \DB::select("select invests_matrix.*,users.referral_id from invests_matrix 
        left join users on (invests_matrix.e_user_id=users.id)
        where   invests_matrix.f_status=1 and f_amount_usd in $matrixNum and  invests_matrix.id not in (select e_invests_matrix from matrix_tree ) order by users.id asc, f_amount_usd asc, f_is_subaccount asc ");
    $i = 1;
    foreach ($res as $matrix) {
        //echo($matrix->id."\r\n");
        \App\Models\Matrix\Tree::addNode($matrix->id, $matrix->f_is_subaccount, $matrix->f_amount_usd, $matrix->e_user_id, $matrix->referral_id);
        $i++;
        //if($i==10)
        //    die();

    }

});

Artisan::command('bot:fix_commissions {username?}', function ($username = null) {
    $this->comment('Running bot...');
    $builder = new InvestMatrix;

    if ($username) {
        $user = User::where('username', $username)->first();
        if (!$user) {
            $this->error('User not found!');
            return;
        }
        $builder = $builder->where('e_user_id', $user->id);
    }

    $invests_service = app(InvestService::class);
    $artisan = $this;

//    echo $builder->where('f_is_subaccount', 0)->where('f_status', 1)->with('author')->toSql();
    $builder->where('f_status', '>', 0)->with('author')->chunk(10000, function ($invests) use ($artisan, $invests_service) {
        foreach ($invests as $invest) {
            if ($invest->author->id === 1) {
                $artisan->comment("Admin invest, continue....");
                continue;
            }
            try {
                $artisan->comment('Try to confirm ' . $invest->id . ' by user ' . $invest->author->display_name);
                $invests_service->matrixCommission($invest->author->sponsor, $invest->author, $invest);
            } catch (MyException $e) {
                $artisan->error($e->getMsg());
            }
        }
    });

});

Artisan::command('bot:fix_profision', function () {
    $this->comment('Running bot...');

    $invests_mining = \App\Models\Invests\InvestMining::all();
    $invests_passive = \App\Models\Invests\InvestPassive::all();

    /*
     * TODO - nie można mergować!!!! Nadpisuje jedne drugimi na podstawie powtarzającego się ID!!!!
     */
    $invests = $invests_mining;//$invests_mining->merge($invests_passive);

    $invests = $invests->sortBy(function ($q) {
        return $q->created_at;
    });
    $i = 0;
    foreach ($invests as $invest) {
        $this->comment($i++ . ') ' . get_class($invest) . ' created_at: ' . $invest->created_at . ' [ID: ' . $invest->id . ']');
        try {
            $invest_service = app(\App\Services\InvestService::class);
            $invest_service->commissionUpline($invest, $invest->created_at);
        } catch (MyException $e) {
            $this->error('Sth\'s wrong in ' . get_class($invest) . ':' . $invest->id);
//            ErrorLog::saveError($e);
        }
    }
});


//Artisan::command('calculations:submit', function () {
//    $this->comment('Running bot...');
//
//    $passive = app(\App\Services\PassiveService::class);
//    $calculations = \App\Models\CalculationPassive::where('f_status', 1)->where('f_calculated_at', '<=', Carbon::now())->get();
//
//    $this->comment('I have found ' . $calculations->count() . ' calculations to give accrual.');
//    if (!$this->confirm('Is this acceptable value?')) {
//        $this->error('Okay, exiting.');
//        return;
//    }
//
//    foreach ($calculations as $calculation) {
//        $this->comment('Calculation from: ' . $calculation->f_calculated_at . '[ID:' . $calculation->id . ']');
//        $calculation = $passive->submitCalculation($calculation);
//        $this->comment('Total company earned amount USD: ' . $calculation->f_company_amount_usd . '$');
//    }
//
//});


//Artisan::command('calculation:refresh', function () {
//    $passive = app(\App\Services\PassiveService::class);
//    $calculations = \App\Models\CalculationPassive::where('f_status', 1)->get();
//
//    $this->comment('I have found ' . $calculations->count() . ' calculations to give accrual.');
//    if (!$this->confirm('Is this acceptable value?')) {
//        $this->error('Okay, exiting.');
//        return;
//    }
//
//    foreach($calculations as $calculation) {
//        $this->comment('Calculation from: '.$calculation->f_calculated_at . '[ID:'. $calculation->id.']');
//        $calculation = $passive->refreshCalculation($calculation);
//        $calculation->save();
//    }
//
//    $calculations = \App\Models\CalculationPassive::where('f_status', 1)->get();
//});
//
//Artisan::command('calculation:create {passive_id?}', function ($passive_id = 1) {
//    $passive_service = app(\App\Services\PassiveService::class);
//    $passive = Passive::find($passive_id);
//    $calculation = $passive_service->createCalculation($passive);
//
//    dd($calculation);
//});
//
//Artisan::command('calculation:delete', function () {
//    $passive_service = app(\App\Services\PassiveService::class);
//    $calculations = \App\Models\CalculationPassive::all();
//
//
//    $calculations_ids = $calculations->keyBy('id')->keys();
//    $accruals = \App\Models\AccrualPassive::whereIn('e_calculations_passive_id', $calculations_ids)->get();
//
//    $accrual_ids= $accruals->keyBy('id')->keys();
//
//    \App\Models\Returns\ReturnPassive::where('e_operation_type', \App\Models\AccrualPassive::class)->whereIn('e_operation_id', $accrual_ids)->delete();
//    \App\Models\Reinvests\ReinvestPassive::where('e_operation_type', \App\Models\AccrualPassive::class)->whereIn('e_operation_id', $accrual_ids)->delete();
//    \App\Models\AccrualPassive::whereIn('id', $accrual_ids)->delete();
//
//    foreach ($calculations as $calculation) {
//        $calculation->f_status = 1;
//        $calculation->save();
//    }
//});


Artisan::command('bot:fix_hps_count', function () {
    $this->comment("Starting bot...");

    $invests = \App\Models\Invests\InvestMining::all();
    $invests->load(['type']);

    foreach ($invests as $invest) {
        $hps = floor($invest->f_amount_usd / $invest->type->f_usd_per_hps);
        $invest->f_amount_unit = $hps / $invest->type->getUnitMultiplyAttribute();
        $this->comment('For price ' . $invest->f_amount_usd . '$ calculated ' . $invest->f_amount_unit . ' ' . $invest->type->f_unit);
        $invest->save();
    }

    $this->comment($invests->count() . ' was recalculated.');
});

Artisan::command('bot:update_accrual_name', function () {
    $this->comment("Bot fixing commissions started...");

    $a = new \App\Models\Accruals\AccrualPassive();
    $this->comment(get_class($a));
    $this->comment(\App\Models\Reinvests\ReinvestPassive::where('e_operation_type', 'LIKE', '%AccrualPassive')->update(['e_operation_type' => get_class($a)]));
    $this->comment(\App\Models\Returns\ReturnPassive::where('e_operation_type', 'LIKE', '%AccrualPassive')->update(['e_operation_type' => get_class($a)]));
    $this->comment(\App\Models\Shields\ShieldPassive::where('e_operation_type', 'LIKE', '%AccrualPassive')->update(['e_operation_type' => get_class($a)]));

    $a = new \App\Models\Accruals\AccrualMining();
    $this->comment(get_class($a));
    $this->comment(\App\Models\Reinvests\ReinvestMining::where('e_operation_type', 'LIKE', '%AccrualMining')->update(['e_operation_type' => get_class($a)]));
    $this->comment(\App\Models\Returns\ReturnMining::where('e_operation_type', 'LIKE', '%AccrualMining')->update(['e_operation_type' => get_class($a)]));
    $this->comment(\App\Models\Shields\ShieldMining::where('e_operation_type', 'LIKE', '%AccrualMining')->update(['e_operation_type' => get_class($a)]));

    $a = new \App\Models\Accruals\AccrualMatrix();
    $this->comment(get_class($a));
    $this->comment(\App\Models\Reinvests\ReinvestMatrix::where('e_operation_type', 'LIKE', '%AccrualMatrix')->update(['e_operation_type' => get_class($a)]));
    $this->comment(\App\Models\Returns\ReturnMatrix::where('e_operation_type', 'LIKE', '%AccrualMatrix')->update(['e_operation_type' => get_class($a)]));
    $this->comment(\App\Models\Shields\ShieldMatrix::where('e_operation_type', 'LIKE', '%AccrualMatrix')->update(['e_operation_type' => get_class($a)]));
});

Artisan::command("bot:fix_commissions", function () {
    $this->comment("Bot fixing commissions started...");

    $commissions = \App\Models\Commissions\CommissionMining::all();
    foreach ($commissions as $c) {
        $r = \App\Models\Reinvests\ReinvestMining::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Returns\ReturnMining::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Shields\ShieldMining::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
    }

    $commissions = \App\Models\Commissions\CommissionPassive::all();
    foreach ($commissions as $c) {
        $r = \App\Models\Reinvests\ReinvestPassive::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Returns\ReturnPassive::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Shields\ShieldPassive::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
    }

    $commissions = \App\Models\Commissions\CommissionMatrix::all();
    foreach ($commissions as $c) {
        $r = \App\Models\Reinvests\ReinvestMatrix::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Returns\ReturnMatrix::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Shields\ShieldMatrix::where('e_operation_id', $c->id)->where('e_operation_type', get_class($c))->where('e_user_id', $c->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
    }

    $accruals = \App\Models\Accruals\AccrualPassive::all();
    foreach ($accruals as $a) {
        $r = \App\Models\Reinvests\ReinvestPassive::where('e_operation_id', $a->id)->where('e_operation_type', get_class($a))->where('e_user_id', $a->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Returns\ReturnPassive::where('e_operation_id', $a->id)->where('e_operation_type', get_class($a))->where('e_user_id', $a->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
        $r = \App\Models\Shields\ShieldPassive::where('e_operation_id', $a->id)->where('e_operation_type', get_class($a))->where('e_user_id', $a->e_user_id)->get();
        var_dump(implode(", ", $r->pluck('created_at')->toArray()));
    }
});

Artisan::command('mining:reload_status', function () {
    $this->comment('Reloading statuses...');

    $types = Mining::all();

    foreach ($types as $type) {
        $now = \Carbon\Carbon::now();
        $updated_invests_count = \App\Models\Invests\InvestMining::where('e_types_mining_id', $type->id)
            ->where('created_at', '<=', $now->subDays($type->f_activation_duration))
            ->update(['f_status' => 2]);
        $this->comment('For ' . $type->f_title . ' - ' . $type->f_currency . ' was updated ' . $updated_invests_count . ' contracts for contracts before ' . $now->subDays($type->f_activation_duration) . '.');
    }
});

Artisan::command('bot:fix_matrix_commission', function () {
    $this->comment('Bot started...');

    $invests = InvestMatrix::all();
    $service = app(\App\Services\MatrixService::class);

    foreach ($invests as $invest) {
        try {
            $this->comment('Invest [' . $invest->id . ']');
            $this->comment(json_encode($service->commissionFromInvest($invest)));
        } catch (MyException $e) {
            $this->error($e->getMessage());
        }
    }
});

Artisan::command('bot:fix_user_foreign',function (){
    $accruals = \App\Models\Accruals\AccrualPassive::with('invest')->get();
    foreach($accruals as $accrual)  {
        $accrual->e_user_id = $accrual->invest->e_user_id;
        $accrual->save();
        $this->comment('Accrual '. $accrual->id.' is for '. $accrual->e_user_id);
    }
});

Artisan::command('invests_mining:update', function() {
    $invests = App\Models\Invests\InvestMining::with('type')->get();
    $this->comment('I got '.$invests->count().' to process...');
    foreach ($invests as $invest) {
        $invest->x_activated_at = $invest->created_at->addDays($invest->type->f_activation_duration);
        $invest->x_currency = $invest->type->f_currency;
        $invest->save();
        $this->comment("Invest [ID:".$invest->id."] is currency: ".$invest->x_currency);
    }
});