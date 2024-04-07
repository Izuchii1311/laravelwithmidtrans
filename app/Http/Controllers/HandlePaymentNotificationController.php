<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\MidtransHistory;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class HandlePaymentNotificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Get request parameters
        $payload = $request->all();

        // Logging
        Log::info('incoming-midtrans', [
            'payload'   => $payload
        ]);

        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'];
        $grossAmount = $payload['gross_amount'];
        // Signature Midtrans
        $reqSignature = $payload['signature_key'];

        // Validasi request
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.key'));

        // check signature midtrans and request signature
        if ($signature != $reqSignature) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Signature'
            ], 401);
        }

        // check status response dari midtrans
        $transactionStatus = $payload['transaction_status'];

        // Save transaction to database
        MidtransHistory::create([
            'order_id'  => $orderId,
            'status'    => $transactionStatus,
            'payload'   => json_encode($payload)
        ]);

        $order = Transaction::find($orderId);
        // check id
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid order id'
            ], 400);
        }

        #transaction status: https://docs.midtrans.com/docs/https-notification-webhooks
        if ($transactionStatus == 'settlement') {
            $order->status = 'PAID';
            $order->save();
        } else if ($transactionStatus == 'expired') {
            $order->status = 'EXPIRED';
            $order->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
        ]);
    }
}