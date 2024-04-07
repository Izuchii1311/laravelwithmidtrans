<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function buy(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'name'          => 'required',
            'email'         => 'required|email',
            'total'         => 'required|int',
            'ticket_id'     => 'required',
            'bank'          => 'required|in:bca,bni',
        ]);

        if ($validatedData->fails()) {
            return response()->json(['message' => "Invalid!!", 'data' => $validatedData->errors()], 400);
        }

        $ticket = DB::table('tickets')
            ->where('id', $request->ticket_id)
            ->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found', 'data' => [
                'ticket_id' => ['ticket not in database'],
            ]], 422);
        }

        try {
            DB::beginTransaction();
            $serverKey = config('midtrans.key');

            $orderId = Str::uuid()->toString();
            $grossAmount = $ticket->price * $request->total + 5000;

            $response = Http::withBasicAuth($serverKey, '')
                ->post('https://api.sandbox.midtrans.com/v2/charge', [
                    'payment_type'          => 'bank_transfer',
                    'transaction_details'   => [
                        'order_id'              => $orderId,
                        'gross_amount'          => $grossAmount
                    ],
                    'bank_transfer'         => [
                        'bank'                  => $request->bank,
                        // 'va_number'          => 49635552682,
                    ],
                    'customer_details'      => [
                        'email'                 => $request->email,
                        'first_name'            => 'Customer',
                        'last_name'             => $request->name,
                        // 'phone'                 => 085722584409,
                    ],
                ]);

            if ($response->failed()) {
                return response()->json(['message' => 'Failed charge request'], 500);
            }

            $result = $response->json();

            if ($result['status_code'] != 201) {
                return response()->json(['message' => $result['status_message']], 500);
            }

            DB::table('transactions')->insert([
                'id'                => $orderId,
                'booking_code'      => Str::random(6),
                'name'              => $request->name,
                'email'             => $request->email,
                'ticket_id'         => $request->ticket_id,
                'total_ticket'      => $request->total,
                'total_amount'      => $grossAmount,
                'status'            => 'BOOKED',
                'created_at'        => now()
            ]);

            DB::table('tickets')->where('id', $ticket->id)->update([
                'stock' => $ticket->stock - $request->total,
            ]);

            DB::commit();

            return response()->json([
                'data' => [
                    'va'        => $result['va_numbers'][0]['va_number']
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}