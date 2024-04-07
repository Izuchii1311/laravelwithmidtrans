<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CounterTransaction;
use Illuminate\Support\Facades\Log;

class HandlePaymentCounterNotificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;

        Log::info('incoming-midtrans', $request->all());
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.key'));

        if ($signature != $request->signature_key) {
            $response = [
                'status' => false,
                'message' => 'Invalid Signature'
            ];
            Log::info('handlePayment response', $response);
            return response()->json($response, 401);
        }

        // Temukan transaksi berdasarkan order_id
        $transaction = CounterTransaction::find($orderId);

        if (!$transaction) {
            $response = [
                'status' => false,
                'message' => 'Transaction not found'
            ];
            Log::info('handlePayment response', $response);
            return response()->json($response, 404);
        }

        // Pastikan status pembayaran adalah settlement
        if ($request->transaction_status == 'settlement') {
            // Ubah status transaksi menjadi PAID
            $transaction->status = 'PAID';
            $transaction->save();
        }

        $response = [
            'status' => true,
            'message' => 'Success',
        ];
        Log::info('handlePayment response', $response);
        return response()->json($response, 200);
    }
}
