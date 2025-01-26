<?php

namespace App\Console\Commands;

use App\Services\OpenAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestOpenAI extends Command
{
    protected $signature = 'openai:test';
    protected $description = 'Test OpenAI integration';

    private $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        parent::__construct();
        $this->openAiService = $openAiService;
    }

    public function handle()
    {
        $this->info('Testing OpenAI integration...');
        
        try {
            $response = $this->openAiService->generateResponse('Hello, this is a test message.');
            
            $this->info('Successfully received response from OpenAI:');
            $this->info($response);
            
            Log::info('OpenAI test successful', [
                'response' => $response
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error testing OpenAI:');
            $this->error($e->getMessage());
            
            Log::error('OpenAI test failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
