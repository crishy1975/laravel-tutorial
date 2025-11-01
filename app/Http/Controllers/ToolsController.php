<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\AddressParser;
use SoapClient;
use SoapFault;

class ToolsController extends Controller
{
    /**
     * POST /tools/vies-lookup
     * Erwartet: country=IT|DE|..., vat=<USt/MwSt-Nr>
     * Gibt Session-Keys vies_* zurück (für Prefill im Formular).
     */
    public function viesLookup(Request $request)
    {
        $data = $request->validate([
            'country' => ['required', 'string', 'size:2'],
            'vat'     => ['required', 'string', 'max:20'],
        ]);

        $country = strtoupper($data['country']);
        $vatRaw  = (string) $data['vat'];
        $vat     = preg_replace('/\D+/', '', $vatRaw); // nur Ziffern

        try {
            $client = new SoapClient(
                'https://ec.europa.eu/taxation_customs/vies/services/checkVatService.wsdl',
                [
                    'exceptions'          => true,
                    'cache_wsdl'          => WSDL_CACHE_MEMORY,
                    'connection_timeout'  => 8,
                ]
            );

            $res = $client->checkVat([
                'countryCode' => $country,
                'vatNumber'   => $vat,
            ]);

            $valid   = (bool)($res->valid ?? false);
            $name    = trim((string)($res->name ?? ''));
            $address = trim((string)($res->address ?? ''));

            // Normalisieren & zerlegen
            $namePretty = AddressParser::titleCaseIt($name);
            $parsed     = AddressParser::parseViesAddress($address);
            $countryName= AddressParser::countryName($country);

            return back()->with([
                'vies_valid'    => $valid,
                'vies_name'     => $namePretty,
                'vies_address'  => $address,                   // Rohtext (mehrzeilig)
                'vies_strasse'  => $parsed['strasse'] ?? '',
                'vies_hausnr'   => $parsed['hausnr']  ?? '',
                'vies_plz'      => $parsed['plz']     ?? '',
                'vies_wohnort'  => $parsed['wohnort'] ?? '',
                'vies_provinz'  => $parsed['provinz'] ?? '',
                'vies_mwst'     => $vat,                       // gereinigt
                'vies_land'     => $countryName,               // z. B. "Italia"
            ]);

        } catch (SoapFault $e) {
            // Häufig: Wartung/Timeout des VIES-Dienstes
            return back()->withErrors(['vies' => 'VIES nicht erreichbar: '.$e->getMessage()]);
        }
    }
}
