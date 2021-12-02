<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyAccount;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function create(Request $request)
    {
        return LoyaltyAccount::create($request->all());
    }

    public function activate($type, $id)
    {
        $account = $this->findModel($type, $id);

        if ($account instanceof LoyaltyAccount) {
            $account->activate();
        } else {
            return $this->responseAccountNotFound();
        }

        return response()->json(['success' => true]);
    }

    public function deactivate($type, $id)
    {
        $account = $this->findModel($type, $id);

        if ($account) {
            $account->deactivate();
        } else {
            return $this->responseAccountNotFound();
        }

        return response()->json(['success' => true]);
    }

    public function balance($type, $id)
    {
        $account = $this->findModel($type, $id);
        if ($account) {
            return response()->json(['balance' => $account->getBalance()], 400);
        } else {
            return $this->responseAccountNotFound();
        }
    }

    protected function findModel($type, $id)
    {
        if (LoyaltyAccount::isValid($type, $id)) {
            if ($account = LoyaltyAccount::getLoyaltyAccountByTypeAndId($type, $id)) {
                return $account;
            } else {
                return null;
            }
        }

        throw new \InvalidArgumentException('Wrong parameters');
    }

    protected function responseAccountNotFound()
    {
        return response()->json(['message' => 'Account is not found'], 400);
    }
}
