<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class ClientPaymentController extends Controller
{
    /**
     * List all payments for a client
     */
    public function index($clientId): JsonResponse
    {
        $payments = ClientPayment::where('client_id', $clientId)->get();

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    }

    /**
     * Store a payment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'title'          => 'required|string|max:255',
            'value'          => 'required|numeric|min:0',
            'payment_date'   => 'required|date',
            'file'           => 'nullable|file|mimes:pdf,png,jpg,jpeg,doc,docx|max:10240'
        ]);

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('payment_comprovements', 'public');
        }

        $payment = ClientPayment::create([
            'client_id' => $validated['client_id'],
            'title'     => $validated['title'],
            'value'     => $validated['value'],
            'payment_date' => $validated['payment_date'],
            'transaction_file' => $filePath
        ]);

        return response()->json([
            'success' => true,
            'payment' => $payment
        ], 201);
    }

    /**
     * Update a payment
     */
    public function update(Request $request, $id): JsonResponse
    {
        $payment = ClientPayment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.'
            ], 404);
        }

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'value'        => 'sometimes|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'file'         => 'nullable|file|mimes:pdf,png,jpg,jpeg,doc,docx|max:10240'
        ]);

        if ($request->hasFile('file')) {
            // Delete old file
            if ($payment->transaction_file && Storage::exists($payment->transaction_file)) {
                Storage::delete($payment->transaction_file);
            }

            $payment->transaction_file = $request->file('file')->store('payment_comprovements', 'public');
        }

        $payment->update($validated);

        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    /**
     * Delete a payment
     */
    public function destroy($id): JsonResponse
    {
        $payment = ClientPayment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.'
            ], 404);
        }

        if ($payment->transaction_file && Storage::exists($payment->transaction_file)) {
            Storage::delete($payment->transaction_file);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully.'
        ]);
    }
}
