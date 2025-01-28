<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = $this->getCustomersList();
        return view('customers.index', compact('customers'));
    }

    public function getUpdates(Request $request)
    {
        try {
            // Get cached data hash
            $oldHash = $request->header('X-Data-Hash');
            
            // Get current data
            $customers = $this->getCustomersList();
            $html = view('customers.table', compact('customers'))->render();
            $newHash = md5($html);

            // Only send new data if it's different
            if ($oldHash !== $newHash) {
                Log::info('Customer list updated, sending new data');
                return response()->json([
                    'success' => true,
                    'html' => $html,
                    'hash' => $newHash,
                    'count' => $customers->count(),
                    'timestamp' => now()->toIso8601String()
                ]);
            }

            return response()->json([
                'success' => true,
                'changed' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getUpdates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch customer updates'
            ], 500);
        }
    }

    private function getCustomersList()
    {
        return Customer::join('conversations', 'customers.conversation_id', '=', 'conversations.id')
            ->select(
                'customers.facebook_id',
                'customers.email',
                'customers.device',
                'customers.app',
                'customers.conversation_id',
                'conversations.facebook_user_id',
                'conversations.response_mode',
                'conversations.last_message_at'
            )
            ->orderBy('conversations.last_message_at', 'desc')
            ->get();
    }
}
