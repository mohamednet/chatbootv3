<?php

namespace App\Http\Controllers;

use App\Mail\TrialInstructions;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    public function sendTrial(Request $request, Customer $customer)
    {
        if (!$customer->email) {
            return response()->json(['error' => 'Customer email is required'], 400);
        }

        try {
            // Prepare email data
            $emailData = [
                'device' => $customer->device,
                'app' => $customer->app,
                'email' => $customer->email
            ];

            // Send email
            Log::info('Attempting to send trial email', ['customer' => $customer->email]);
            Mail::to($customer->email)->send(new TrialInstructions($emailData));
            Log::info('Trial email sent successfully', ['customer' => $customer->email]);

            // Send Facebook message
            Log::info('Attempting to send Facebook message', ['customer_id' => $customer->facebook_id]);
            $confirmationMessage = "🎉 Great news! We've sent your IPTV trial instructions to your email address.\n\n📧 Please check your inbox (and spam folder) for detailed setup instructions.\n\n❓ If you need any help, just reply here and our support team will assist you!";
            
            // Save confirmation message to conversation
            $conversation = Conversation::where('facebook_user_id', $customer->facebook_id)->first();
            if ($conversation) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'content' => $confirmationMessage,
                    'type' => 'outgoing',
                    'sender_type' => 'admin',
                    'facebook_message_id' => null
                ]);
            }

            $response = Http::post('https://graph.facebook.com/v18.0/me/messages', [
                'recipient' => ['id' => $customer->facebook_id],
                'message' => [
                    'text' => $confirmationMessage
                ],
                'access_token' => config('services.facebook.page_access_token')
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Facebook message', [
                    'customer_id' => $customer->facebook_id,
                    'error' => $response->body(),
                    'status' => $response->status()
                ]);
                return response()->json(['error' => 'Failed to send Facebook message'], 500);
            }

            // Update trial status
            $customer->update(['trial_status' => 'Sent']);
            Log::info('Trial status updated successfully', ['customer' => $customer->email]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to send trial email or Facebook message', [
                'customer_id' => $customer->facebook_id,
                'email' => $customer->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to send trial information',
                'message' => $e->getMessage()
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
                'conversations.response_mode',
                'customers.trial_status',
                'conversations.last_message_at',
                'customers.conversation_id'
            )
            ->orderBy('conversations.last_message_at', 'desc')
            ->get();
    }
}
