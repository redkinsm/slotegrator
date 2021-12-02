<?php

namespace App\Models;

use App\Mail\AccountActivated;
use App\Mail\AccountDeactivated;
use App\Mail\LoyaltyPointsReceived;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoyaltyAccount extends Model
{
    const TYPE_PHONE = 'phone';
    const TYPE_CARD = 'card';
    const TYPE_EMAIL = 'email';
    const TYPE_EMAIL_NOTIF = 'email_notification';
    const TYPE_PHONE_NOTIF = 'phone_notification';
    const TYPE_ACTIVE = 'active';

    protected $table = 'loyalty_account';

    protected $fillable = [
        self::TYPE_PHONE,
        self::TYPE_CARD,
        self::TYPE_EMAIL,
        self::TYPE_EMAIL_NOTIF,
        self::TYPE_PHONE_NOTIF,
        self::TYPE_ACTIVE,
    ];

    public function getBalance(): float
    {
        return LoyaltyPointsTransaction::where('canceled', '=', 0)->where('account_id', '=', $this->id)->sum('points_amount');
    }

    public function notify()
    {
        if ($this->email != '' && $this->email_notification) {
            if ($this->active) {
                Mail::to($this)->send(new AccountActivated($this->getBalance()));
            } else {
                Mail::to($this)->send(new AccountDeactivated());
            }
        }

        if ($this->phone != '' && $this->phone_notification) {
            // instead SMS component
            Log::info('Account: phone: ' . $this->phone . ' ' . ($this->active ? 'Activated' : 'Deactivated'));
        }
    }

    public static function isValid($type, $id)
    {
        return (in_array($type, [self::TYPE_PHONE, self::TYPE_CARD, self::TYPE_EMAIL]) && $id != '');
    }

    public function isActive()
    {
        return $this->active;
    }

    public static function getLoyaltyAccountByTypeAndId($type, $id)
    {
        if (self::isValid($type, $id)) {
            return LoyaltyAccount::where($type, '=', $id)->first();
        }
        return null;
    }

    public function activate()
    {
        if (!$this->isActive()) {
            $this->active = true;
            $this->save();
            $this->notify();
        }
    }

    public function deactivate()
    {
        if ($this->isActive()) {
            $this->active = false;
            $this->save();
            $this->notify();
        }
    }

    public function sendNotif($transaction)
    {
        if ($this->email != '' && $this->email_notification) {
            Mail::to($this)->send(new LoyaltyPointsReceived($transaction->points_amount, $this->getBalance()));
        }
        if ($this->phone != '' && $this->phone_notification) {
            // instead SMS component
            Log::info('You received' . $transaction->points_amount . 'Your balance' . $this->getBalance());
        }
    }
}
