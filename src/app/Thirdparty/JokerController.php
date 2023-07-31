<?php

namespace App\Thirdparty;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\Joker;
use App\Http\Controllers\TransactionController;

class JokerController extends Controller
{
    private static $secretKey = "youCantSeeMe";

     /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function getHash(Request $request)
    {
      $param = $request->all();

      $providerHash = $param['hash'] ?? '';
      unset($param['hash']);

      ksort($param);
      $rawDataString = '';
      foreach ($param as $key => $value) {
        $rawDataString .= $key . "=" . $value . "&";
      }

      $cleanString = rtrim($rawDataString, "&");

      return md5($cleanString.self::$secretKey);
    }

     /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function auth(Request $request)
    {
      $param = $request->all();

      if (!$this->checkHash($param)) {
        return response()->json([
            'Status' => 5,
            'Message' => 'Invalid Signature',
            'Balance' => 0.0
        ]);
      }

      $user = DB::table('users')->where('username', $param['username'])->first();
      if (!$user) return response()->json(['Status' => 7,'Message' => 'Invalid Username or Password','Balance' => 0.0 ]);
      if (!Hash::check($param['password'], $user->password)) return response()->json(['Status' => 7,'Message' => 'Invalid Username or Password','Balance' => 0.0 ]);

      $transaction = new TransactionController();
      $transResult = $transaction->getBalanceFromWallet(['userId'=>$user->id]);

      if (!$transResult['status']) return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);
      
      return response()->json([
        "Status" => 0,
        "Message" => "Success",
        "Balance" => $this->setDecimal($transResult['balance_after'], 2),
        "Token" => $user->remember_token ?? null
      ]);
    }

     /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function getBalance(Request $request)
    {
      $param = $request->all();

      if (!$this->checkHash($param)) {
        return response()->json([
            'Status' => 5,
            'Message' => 'Invalid Signature',
            'Balance' => 0.0
        ]);
      }

      $userId = DB::table('users')->where('username', $param['username'])->value('id');
      if (!$userId) return response()->json(['Status' => 7,'Message' => 'Invalid Username or Password','Balance' => 0.0 ]);

      $transaction = new TransactionController();
      $transResult = $transaction->getBalanceFromWallet(['userId'=>$userId]);

      if (!$transResult['status']) return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);
      
      return response()->json([
        "Status" => 0,
        "Message" => "Success",
        "Balance" => $this->setDecimal($transResult['balance_after'], 2)
      ]);
    }

     /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function bet(Request $request)
    {
      $param = $request->all();

      if (!$this->checkHash($param)) {
        return response()->json([
            'Status' => 5,
            'Message' => 'Invalid Signature',
            'Balance' => 0.0
        ]);
      }

      $userId = DB::table('users')->where('username', $param['username'])->value('id');
      $currency = DB::table('users')->where('username', $param['username'])->value('currency');
      if (!$userId || !$currency) return response()->json(['Status' => 7,'Message' => 'Invalid Username or Password','Balance' => 0.0 ]);

      // check for duplicate request
      $isDuplicateReq = DB::table('bet_history_joker')->where('bet_id', $param['id'])->value('id');
      if ($isDuplicateReq) return response()->json(['Status' => 201,'Message' => 'Duplicate request','Balance' => 0.0 ]);

      $transaction = new TransactionController();
      $transResult = $transaction->deductFromWallet(['userId'=>$userId,'balance'=>$param['amount']]);
      if (!$transResult['status']) {
        if ($transResult['message'] == 'insufficient_fund') return response()->json(['Status' => 100,'Message' => 'Insufficient fund','Balance' => 0.0 ]);

        return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);
      }

      // insert into bet history
      $param['action'] = 'bet';
      $param['user_id'] = $userId;
      $param['pis_transaction_id'] = $transResult['pis_transaction_id'];
      $param['currency'] = $currency;
      $insertResult = $this->insertBetHistory($param);

      if (!$insertResult) return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);
      
      return response()->json([
        "Status" => 0,
        "Message" => "Success",
        "Balance" => $this->setDecimal($transResult['balance_after'], 2)
      ]);
    }

     /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function cancelBet(Request $request)
    {
      $param = $request->all();

      if (!$this->checkHash($param)) {
        return response()->json([
            'Status' => 5,
            'Message' => 'Invalid Signature',
            'Balance' => 0.0
        ]);
      }

      $userId = DB::table('users')->where('username', $param['username'])->value('id');
      $currency = DB::table('users')->where('username', $param['username'])->value('currency');
      if (!$userId || !$currency) return response()->json(['Status' => 7,'Message' => 'Invalid Username or Password','Balance' => 0.0 ]);

      // check for duplicate request
      $isDuplicateReq = DB::table('bet_history_joker')->where('bet_id', $param['id'])->value('id');
      if ($isDuplicateReq) return response()->json(['Status' => 201,'Message' => 'Duplicate request','Balance' => 0.0 ]);

      // check if bet and round exist
      $isBetExist = DB::table('bet_history_joker')->where('bet_id', $param['betid'])->where('round_id', $param['roundid'])->where('user_id', $userId)->value('id');
      if (!$isBetExist) return response()->json(['Status' => 4,'Message' => 'Bet not found','Balance' => 0.0 ]);

      // check if round has ended
      $isRoundEnded = DB::table('bet_history_joker')->where('action', 'cancelBet')->where('round_id', $param['roundid'])->where('user_id', $userId)->value('id');
      if ($isRoundEnded) return response()->json(['Status' => 4,'Message' => 'Round has ended','Balance' => 0.0 ]);

      $transaction = new TransactionController();
      $transResult = $transaction->addToWallet(['userId'=>$userId,'balance'=>$param['amount']]);
      if (!$transResult['status']) return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);

      // insert into bet history
      $param['action'] = 'cancelBet';
      $param['user_id'] = $userId;
      $param['pis_transaction_id'] = $transResult['pis_transaction_id'];
      $param['currency'] = $currency;
      $insertResult = $this->insertBetHistory($param);

      if (!$insertResult) return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);
      
      return response()->json([
        "Status" => 0,
        "Message" => "Success",
        "Balance" => $this->setDecimal($transResult['balance_after'], 2)
      ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function settle(Request $request)
    {
      $param = $request->all();

      if (!$this->checkHash($param)) {
        return response()->json([
            'Status' => 5,
            'Message' => 'Invalid Signature',
            'Balance' => 0.0
        ]);
      }

      $userId = DB::table('users')->where('username', $param['username'])->value('id');
      $currency = DB::table('users')->where('username', $param['username'])->value('currency');
      if (!$userId || !$currency) return response()->json(['Status' => 7,'Message' => 'Invalid Username or Password','Balance' => 0.0 ]);

      // check for duplicate request
      $isDuplicateReq = DB::table('bet_history_joker')->where('bet_id', $param['id'])->value('id');
      if ($isDuplicateReq) return response()->json(['Status' => 201,'Message' => 'Duplicate request','Balance' => 0.0 ]);

      // check if bet and round exist
      $isBetExist = DB::table('bet_history_joker')->where('round_id', $param['roundid'])->where('user_id', $userId)->value('id');
      if (!$isBetExist) return response()->json(['Status' => 4,'Message' => 'Bet not found','Balance' => 0.0 ]);

      // check if round has ended
      $isRoundEnded = DB::table('bet_history_joker')->where('action', 'settle')->orWhere('action', 'cancelBet')->where('round_id', $param['roundid'])->where('user_id', $userId)->value('id');
      if ($isRoundEnded) return response()->json(['Status' => 4,'Message' => 'Round has ended','Balance' => 0.0 ]);

      $transaction = new TransactionController();
      $transResult = $transaction->addToWallet(['userId'=>$userId,'balance'=>$param['amount']]);
      if (!$transResult['status'] && $transResult['message'] == 'insufficient_fund') return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);

      // insert into bet history
      $param['action'] = 'settle';
      $param['user_id'] = $userId;
      $param['pis_transaction_id'] = $transResult['pis_transaction_id'];
      $param['currency'] = $currency;
      $insertResult = $this->insertBetHistory($param);

      if (!$insertResult) return response()->json(['Status' => 1000,'Message' => 'Other','Balance' => 0.0 ]);
      
      return response()->json([
        "Status" => 0,
        "Message" => "Success",
        "Balance" => $this->setDecimal($transResult['balance_after'], 2)
      ]);
    }

    private function insertBetHistory($param=[])
    {
      $betHistoryArray = [
        'user_id' => $param['user_id'],
        'currency' => $param['currency'],
        'action' => $param['action'],
        'transaction_id' => $param['pis_transaction_id'],
        'bet_id' => $param['id'],
        'round_id' => $param['roundid'],
        'provider_appid' => $param['appid'] ?? null,
        'provider_hash' => $param['hash'] ?? null,
        'provider_username' => $param['username'] ?? null,
        'provider_timestamp' => $param['timestamp'] ?? null,
        'provider_id' => $param['id'] ?? null,
        'provider_amount' => $param['amount'] ?? null,
        'provider_gamecode' => $param['gamecode'] ?? null,
        'provider_roundid' => $param['roundid'] ?? null,
        'provider_description' => $param['description'] ?? null,
        'provider_type' => $param['type'] ?? null,
        'provider_betid' => $param['betid'] ?? null,
      ];

      return DB::table('bet_history_joker')->insert($betHistoryArray);
    }

    private function checkHash($param=[])
    {
      $providerHash = $param['hash'];
      unset($param['hash']);

      ksort($param);
      $rawDataString = '';
      foreach ($param as $key => $value) {
        $rawDataString .= $key . "=" . $value . "&";
      }

      $cleanString = rtrim($rawDataString, "&");

      $generatedHash = md5($cleanString.self::$secretKey);

      if ($generatedHash != $providerHash) return false;

      return true;
    }

    private function setDecimal($amount, $decimal)
    {
      $floor = pow(10, $decimal); // floor for extra decimal
  
      $amount = number_format((floor(strval($amount * $floor)) / $floor), $decimal, '.', '');
  
      return (double) $amount;
    }
}
