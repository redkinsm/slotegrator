<?php

namespace App\Http\Controllers;

use App\Mail\LoyaltyPointsReceived;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyPointsTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoyaltyPointsController extends Controller
{
    public function deposit()
    {
        $data = $_POST;

        Log::info('Deposit transaction input: ' . print_r($data, true));

        $type = $data['account_type'];
        $id = $data['account_id'];
        if (LoyaltyAccount::isValid($type, $id)) {
            if ($account = LoyaltyAccount::getLoyaltyAccountByTypeAndId($type, $id)) {
                if ($account->isActive()) {
                    $transaction =  LoyaltyPointsTransaction::performPaymentLoyaltyPoints($account->id, $data['loyalty_points_rule'], $data['description'], $data['payment_id'], $data['payment_amount'], $data['payment_time']);
                    Log::info($transaction);
                    $account->sendNotif($transaction);
                    return $transaction;
                } else {
                    Log::info('Account is not active');
                    return response()->json(['message' => 'Account is not active'], 400);
                }
            } else {
                Log::info('Account is not found');
                return response()->json(['message' => 'Account is not found'], 400);
            }
        } else {
            Log::info('Wrong account parameters');
            throw new \InvalidArgumentException('Wrong account parameters');
        }
    }

    public function cancel()
    {
        $data = $_POST;

        $reason = $data['cancellation_reason'];

        if ($reason == '') {
            return response()->json(['message' => 'Cancellation reason is not specified'], 400);
        }

        if ($transaction = LoyaltyPointsTransaction::where('id', '=', $data['transaction_id'])->where('canceled', '=', 0)->first()) {
            $transaction->cancel($reason);
        } else {
            return response()->json(['message' => 'Transaction is not found'], 400);
        }
    }

    public function withdraw()
    {
        $data = $_POST;

        Log::info('Withdraw loyalty points transaction input: ' . print_r($data, true));

        $type = $data['account_type'];
        $id = $data['account_id'];
        if (LoyaltyAccount::isValid($type, $id)) {
            if ($account = LoyaltyAccount::getLoyaltyAccountByTypeAndId($type, $id)) {
                if ($account->active) {
                    if ($data['points_amount'] <= 0) {
                        Log::info('Wrong loyalty points amount: ' . $data['points_amount']);
                        return response()->json(['message' => 'Wrong loyalty points amount'], 400);
                    }
                    if ($account->getBalance() < $data['points_amount']) {
                        Log::info('Insufficient funds: ' . $data['points_amount']);
                        return response()->json(['message' => 'Insufficient funds'], 400);
                    }

                    $transaction = LoyaltyPointsTransaction::withdrawLoyaltyPoints($account->id, $data['points_amount'], $data['description']);
                    Log::info($transaction);
                    return $transaction;
                } else {
                    Log::info('Account is not active: ' . $type . ' ' . $id);
                    return response()->json(['message' => 'Account is not active'], 400);
                }
            } else {
                Log::info('Account is not found:' . $type . ' ' . $id);
                return response()->json(['message' => 'Account is not found'], 400);
            }
        } else {
            Log::info('Wrong account parameters');
            throw new \InvalidArgumentException('Wrong account parameters');
        }
    }
}
