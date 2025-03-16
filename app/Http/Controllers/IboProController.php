<?php

namespace App\Http\Controllers;

use App\Services\IboProAddPlaylistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IboProController extends Controller
{
    protected $iboProService;

    public function __construct(IboProAddPlaylistService $iboProService)
    {
        $this->iboProService = $iboProService;
    }

    /**
     * Analyze an image from a given URL and return a description
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeImage(Request $request)
    {
        $imageUrl = $request->input('url');
        $customerId = $request->input('customer_id');

        // Validate input
        if (!$imageUrl || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return response()->json([
                'success' => false,
                'error' => 'A valid image URL is required.'
            ], 400);
        }

        // Get analysis from service
        $result = $this->iboProService->analyzeImage($imageUrl);

        // Log the analysis for customer support tracking
        Log::info('Customer IBO Pro Image Analysis', [
            'customer_id' => $customerId ?? 'Unknown',
            'image_url' => $imageUrl,
            'analysis_result' => $result,
            'timestamp' => now()
        ]);

        return response()->json($result);
    }

    /**
     * Run a test analysis using a predefined image URL
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeTestImage()
    {
        $imageUrl = 'https://scontent.xx.fbcdn.net/v/t1.15752-9/483494739_1675902083350698_6950371622197522366_n.jpg?_nc_cat=106&ccb=1-7&_nc_sid=fc17b8&_nc_ohc=8AufgBol_iEQ7kNvgH_K-QT&_nc_oc=Adg5jZzFllGd-LAlFrNT6zYP43k1lrPaZp14pKbAu7ahLjQWf8cx-0-xmwi6b1IE5bvhk0CCVU0HxHCIrtWYLSSK&_nc_ad=z-m&_nc_cid=0&_nc_zt=23&_nc_ht=scontent.xx&oh=03_Q7cD1wGFdq1JNQnTYutbwtQmrTr8R4OeoGLWws3sTiI0xVnd3Q&oe=67FDDA43';

        // Log test analysis
        Log::info('Testing IBO Pro Image Analysis', [
            'test_url' => $imageUrl,
            'timestamp' => now()
        ]);

        // Run the analysis
        $result = $this->iboProService->analyzeImage($imageUrl);

        return response()->json($result);
    }
}
