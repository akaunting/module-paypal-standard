<?php

namespace Modules\PaypalStandard\Http\Controllers;

use App\Abstracts\Http\PaymentController;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;
use App\Models\Document\Document;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

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

    /**
     * Handle the PayPal return redirect.
     *
     * Security (CWE-345): The return route is a user-facing redirect URL. It
     * must NOT mutate financial state (create transactions, mark invoices as
     * paid) because it trusts client-supplied POST data. Laravel's signed URL
     * validation only covers the URL path and query string, not the request
     * body — an attacker can POST `payment_status=Completed` to the signed
     * return URL without making a real PayPal payment.
     *
     * This method now only displays a "Thank You / Processing" screen by
     * redirecting to the finish page. The actual payment is processed
     * exclusively via the server-to-server `confirm` (IPN) callback, where
     * the transaction is cryptographically verified with PayPal.
     *
     * @param  Document  $invoice
     * @param  Request   $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function return(Document $invoice, Request $request)
    {
        $message = trans('paypal-standard::general.payment.processing');

        flash($message)->success();

        return redirect($this->getFinishUrl($invoice));
    }

    /**
     * Handle the PayPal IPN (Instant Payment Notification) callback.
     *
     * This is a server-to-server callback from PayPal. The notification is
     * verified by re-posting the data to PayPal and checking for a `VERIFIED`
     * response. Only after verification (and matching receiver email + amount)
     * is the `PaymentReceived` event dispatched, which creates the transaction
     * and marks the invoice as paid.
     *
     * @param  Document  $invoice
     * @param  Request   $request
     * @return void
     */
    public function confirm(Document $invoice, Request $request)
    {
        $setting = $this->setting;

        $paypal_log = $this->logger;

        if (!$invoice) {
            return;
        }

        $url = ($setting['mode'] == 'live') ? 'https://ipnpb.paypal.com/cgi-bin/webscr' : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

        $client = new Client(['verify' => false]);

        $paypal_request['cmd'] = '_notify-validate';

        foreach ($request->toArray() as $key => $value) {
            $paypal_request[$key] = urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
        }

        try {
            $response = $client->post($url, $paypal_request);
        } catch (\Exception $e) {
            $paypal_log->info('PAYPAL_STANDARD :: CURL failed ' . $e->getMessage());

            return;
        }

        if ($response->getStatusCode() != 200) {
            $paypal_log->info('PAYPAL_STANDARD :: CURL failed ' . $response->getBody()->getContents());

            return;
        }

        $response_body = $response->getBody()->getContents();

        if (!empty($setting['debug'])) {
            $paypal_log->info('PAYPAL_STANDARD :: IPN REQUEST: ', $request->toArray());
            $paypal_log->info('PAYPAL_STANDARD :: IPN RESPONSE: ' . $response_body);
        }

        // Security: only accept PayPal's cryptographically verified response.
        // The previous condition used `||` which was always true (any string is
        // either not 'VERIFIED' OR not 'UNVERIFIED'), causing the method to
        // return early and never process any payment — defeating IPN entirely.
        if (strcmp($response_body, 'VERIFIED') !== 0) {
            $paypal_log->info('PAYPAL_STANDARD :: IPN NOT VERIFIED: ' . $response_body);

            return;
        }

        switch ($request['payment_status']) {
            case 'Completed':
                $receiver_match = (strtolower($request['receiver_email']) == strtolower($setting['email']));

                $total_paid_match = ((double) $request['mc_gross'] == $invoice->amount);

                if ($receiver_match && $total_paid_match) {
                    // Use PayPal's transaction ID as the reference.
                    $this->setReference($invoice, $request['txn_id']);

                    $this->dispatchPaidEvent($invoice, $request->merge(['type' => 'income']));

                    $this->forgetReference($invoice);

                    $paypal_log->info('PAYPAL_STANDARD :: Payment Received for Invoice: ' . $invoice->id . ' - Txn ID: ' . $request['txn_id']);
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
                $paypal_log->info('PAYPAL_STANDARD :: NOT COMPLETED: ' . $request['payment_status']);
                break;
        }
    }
}
