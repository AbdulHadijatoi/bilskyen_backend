<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DmrTestController extends Controller
{
    /**
     * Source 1: Montago/dmr-webservice
     * Assumption: Service exposes a plate lookup endpoint
     */
    public function dmrWebservice(Request $request)
    {
        $plate = $request->get('plate', 'AB12345');

        $response = Http::get(
            'https://dmrws.montago.dk/api/vehicle',
            ['plate' => $plate]
        );

        return response()->json([
            'source' => 'dmr-webservice',
            'plate' => $plate,
            'data' => $response->json(),
        ]);
    }

    /**
     * Source 2: MotorRegisterData (XML / dataset based)
     * Assumption: Public XML dataset is available
     */
    public function motorRegisterData()
    {
        $response = Http::get(
            'https://motorregisterdata.dk/latest.xml'
        );

        return response()->json([
            'source' => 'MotorRegisterData',
            'raw_xml_preview' => substr($response->body(), 0, 2000),
        ]);
    }

    /**
     * Source 3: js-dk-car-scraper
     * Assumption: Scraping Motorregister public lookup endpoint
     */
    public function jsDkCarScraper(Request $request)
    {
        $plate = $request->get('plate', 'AB12345');

        $response = Http::get(
            'https://motorregister.skat.dk/dmr-kerne/koeretoejdetaljer/visKoeretoej',
            ['nummerplade' => $plate]
        );

        return response()->json([
            'source' => 'js-dk-car-scraper',
            'plate' => $plate,
            'html_preview' => substr($response->body(), 0, 2000),
        ]);
    }

    /**
     * Source 4: motorregister_xmlstream
     * Assumption: Streaming large XML dump
     */
    public function motorregisterXmlStream()
    {
        $response = Http::get(
            'https://motorregisterdata.dk/stream/latest'
        );

        return response()->json([
            'source' => 'motorregister_xmlstream',
            'raw_preview' => substr($response->body(), 0, 2000),
        ]);
    }
}