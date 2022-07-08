<?php

namespace Modules\PaypalStandard\Http\Controllers;

use App\Abstracts\Http\PaymentController;
use App\Events\Document\PaymentReceived;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;
use App\Models\Document\Document;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Payment extends PaymentController
{
    public $alias = 'paypal-standard';

    public $type = 'redirect';

    public function show(Document $invoice, PaymentRequest $request, $cards = [])
    {
        $setting = $this->setting;

        $this->setContactFirstLastName($invoice);

        $setting['action'] = 'https://www.paypal.com/cgi-bin/webscr';

        if (isset($setting['mode']) && $setting['mode'] == 'sandbox') {
            $setting['action'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        }

        $invoice_url = $this->getInvoiceUrl($invoice);
        $return_url = $this->getReturnUrl($invoice);
        $confirm_url = $this->getConfirmUrl($invoice);

        return response()->json([
            'code' => $setting['code'],
            'name' => $setting['name'],
            'description' => trans('paypal-standard::general.description'),
            'redirect' => false,
            'html' => view('paypal-standard::show', compact('setting', 'invoice', 'invoice_url', 'return_url', 'confirm_url'))->render(),
        ]);
    }

    public function return(Document $invoice, Request $request)
    {
        if ($request['payment_status'] === 'Completed') {
            $this->setReference($invoice, $request->id);

            $this->finish($invoice, $request);

            return redirect($this->getFinishUrl($invoice));
        }

        if (in_array($request['payment_status'], ['Pending', 'Canceled_Reversal', 'Denied', 'Expired', 'Failed', 'Processed', 'Refunded', 'Reversed', 'Voided'])) {
            $message = $request['payment_status'] === 'Pending' ? trans('paypal-standard::general.payment.pending') : trans('paypal-standard::general.payment.not_added');

            flash($message)->warning();

            return redirect($this->getInvoiceUrl($invoice));
        }
    }

    public function confirm(Document $invoice, Request $request)
    {
        $setting = $this->setting;

        $paypal_log = new Logger('Paypal');

        $paypal_log->pushHandler(new StreamHandler(storage_path('logs/paypal.log')), Logger::INFO);

        if (!$invoice) {
            return;
        }

        $url = ($setting['mode'] == 'live') ? 'https://ipnpb.paypal.com/cgi-bin/webscr' : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

        $client = new Client(['verify' => false]);

        $paypal_request['cmd'] = '_notify-validate';

        foreach ($request->toArray() as $key => $value) {
            $paypal_request[$key] = urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
        }

        $response = $client->post($url, $paypal_request);

        if ($response->getStatusCode() != 200) {
            $paypal_log->info('PAYPAL_STANDARD :: CURL failed ' . $response->getBody()->getContents());
        } else {
            $response = $response->getBody()->getContents();
        }

        if ($setting['debug']) {
            $paypal_log->info('PAYPAL_STANDARD :: IPN REQUEST: ', $request->toArray());
        }

        if ((strcmp($response, 'VERIFIED') != 0 || strcmp($response, 'UNVERIFIED') != 0)) {
            $paypal_log->info('PAYPAL_STANDARD :: VERIFIED != 0 || UNVERIFIED != 0 ' . $request->toArray());

            return;
        }

        switch ($request['payment_status']) {
            case 'Completed':
                $receiver_match = (strtolower($request['receiver_email']) == strtolower($setting['email']));

                $total_paid_match = ((double) $request['mc_gross'] == $invoice->amount);

                if ($receiver_match && $total_paid_match) {
                    event(new PaymentReceived($invoice, $request->merge(['type' => 'income'])));
                }

                if (!$receiver_match) {
                    $paypal_log->info('PAYPAL_STANDARD :: RECEIVER EMAIL MISMATCH! ' . strtolower($request['receiver_email']));
                }

                if (!$total_paid_match) {
                    $paypal_log->info('PAYPAL_STANDARD :: TOTAL PAID MISMATCH! ' . $request['mc_gross']);
                }
                break;
            case 'Canceled_Reversal':
            case 'Denied':
            case 'Expired':
            case 'Failed':
            case 'Pending':
            case 'Processed':
            case 'Refunded':
            case 'Reversed':
            case 'Voided':
                $paypal_log->info('PAYPAL_STANDARD :: NOT COMPLETED ' . $request->toArray());
                break;
        }
    }
}
