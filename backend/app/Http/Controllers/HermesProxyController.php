<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class HermesProxyController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        abort_unless((bool) config('hermes.enabled'), 404);

        $this->hermes = $hermes;
    }

    /**
     * Proxy ping request.
     */
    public function ping()
    {
        $success = $this->hermes->testConnection();
        return Response::json(['connected' => $success]);
    }

    /**
     * Proxy search request.
     */
    public function search(Request $request)
    {
        $pattern = $request->input('pattern');
        $result = $this->hermes->searchGraph($pattern);
        return Response::json($result);
    }

    /**
     * Proxy trace request.
     */
    public function trace(Request $request)
    {
        $function = $request->input('function');
        $direction = $request->input('direction', 'inbound');
        $result = $this->hermes->tracePath($function, $direction);
        return Response::json($result);
    }

    /**
     * Proxy snippet request.
     */
    public function snippet(Request $request)
    {
        $qualified = $request->input('qualified_name');
        $result = $this->hermes->getCodeSnippet($qualified);
        return Response::json($result);
    }
}
