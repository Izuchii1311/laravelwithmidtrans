<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CounterTransaction;
use Illuminate\Support\Facades\Http;

class CounterTransactionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $transaction = CounterTransaction::find($request->transaction_id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->withBasicAuth(config('midtrans.key'), '')
            ->post(config('midtrans.base_url') . '/v2/charge', [
                "payment_type"          => "cstore",
                "transaction_details"   => [
                    "order_id"              => $transaction->id,
                    "gross_amount"          => $transaction->amount,
                ],
                "cstore"                => [
                    "store"                 => "alfamart",
                    "message"               => "PT Izuchii Studio",
                ],
                "customer_details" => [
                    "first_name"            => "Customer",
                    "last_name"             => $transaction->name,
                ]
            ]);

        return response()->json(['payment_code' => $response->json('payment_code'), 'alfa_code' => $response->body()]);
    }
}
