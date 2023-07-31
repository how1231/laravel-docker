<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

class TransactionController extends Controller
{
     /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getBalanceFromWallet($param=[])
    {
        $userId = $param['userId'];

        $balanceAfter = DB::table('transactions')->where('user_id', $userId)->orderBy('id', 'DESC')->value('balance_after');
        
        return ['status'=>1, 'message'=>'success', 'balance_after'=>$balanceAfter];
    }

     /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deductFromWallet($param=[])
    {
        $betAmount = $param['balance'];
        $userId = $param['userId'];

        $balanceBefore = DB::table('transactions')->where('user_id', $userId)->orderBy('id', 'DESC')->value('balance_after');

        if ($balanceBefore < $betAmount) return ['status'=>0, 'message'=>'insufficient_fund'];

        $balanceAfter = $balanceBefore - $betAmount;

        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $userId,
            'balance_before' => $balanceBefore,
            'amount' => $betAmount,
            'balance_after' => $balanceAfter,
        ]);

        return ['status'=>1, 'message'=>'success', 'balance_before'=>$balanceBefore, 'balance_after'=>$balanceAfter, 'pis_transaction_id'=>$transactionId];
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addToWallet($param=[])
    {
        $settleAmount = $param['balance'];
        $userId = $param['userId'];

        $balanceBefore = DB::table('transactions')->where('user_id', $userId)->orderBy('id', 'DESC')->value('balance_after');

        $balanceAfter = $balanceBefore + $settleAmount;

        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $userId,
            'balance_before' => $balanceBefore,
            'amount' => $settleAmount,
            'balance_after' => $balanceAfter,
        ]);

        return ['status'=>1, 'message'=>'success', 'balance_before'=>$balanceBefore, 'balance_after'=>$balanceAfter, 'pis_transaction_id'=>$transactionId];
    }
}
