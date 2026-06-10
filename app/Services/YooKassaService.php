<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use YooKassa\Client;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Model\Payment\PaymentStatus;
use YooKassa\Request\Payments\CreatePaymentRequest;

class YooKassaService
{
    private ?Client $client = null;

    public function isConfigured(): bool
    {
        if (! config('services.yookassa.enabled', true)) {
            return false;
        }

        $shopId = trim((string) config('services.yookassa.shop_id'));
        $secret = trim((string) config('services.yookassa.secret_key'));

        return $shopId !== '' && $secret !== '';
    }

    public function createPaymentForOrder(Order $order): string
    {
        $order->loadMissing('items');

        $amount = number_format($order->total_amount, 2, '.', '');

        $request = CreatePaymentRequest::builder()
            ->setAmount($amount, 'RUB')
            ->setCapture(true)
            ->setDescription('Заказ #'.$order->id.' — Plantform')
            ->setMetadata([
                'order_id' => (string) $order->id,
            ])
            ->setConfirmation([
                'type' => 'redirect',
                'return_url' => route('payment.success', ['order' => $order->id]),
            ])
            ->build();

        $payment = $this->client()->createPayment($request, 'order-'.$order->id.'-'.uniqid());

        $order->update(['yookassa_payment_id' => $payment->getId()]);

        $confirmation = $payment->getConfirmation();
        $url = $confirmation?->getConfirmationUrl();

        if (! $url) {
            throw new \RuntimeException('ЮKassa не вернула ссылку для оплаты');
        }

        return $url;
    }

    public function fetchPayment(string $paymentId): ?PaymentInterface
    {
        try {
            return $this->client()->getPaymentInfo($paymentId);
        } catch (\Throwable $e) {
            Log::warning('YooKassa getPaymentInfo failed', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function syncOrderFromPayment(Order $order, PaymentInterface $payment): void
    {
        $metadataOrderId = $payment->getMetadata()?->offsetGet('order_id');
        if ($metadataOrderId !== null && (string) $metadataOrderId !== (string) $order->id) {
            return;
        }

        $status = $payment->getStatus();

        if ($status === PaymentStatus::SUCCEEDED) {
            app(CheckoutService::class)->completeYooKassaPayment($order, $payment->getId());

            return;
        }

        if (in_array($status, [PaymentStatus::CANCELED], true)) {
            app(CheckoutService::class)->failYooKassaPayment($order, 'canceled');
        }
    }

    private function client(): Client
    {
        if ($this->client === null) {
            $curlClient = new YooKassaCurlClient;
            $curlClient->configureSsl(
                config('services.yookassa.cafile'),
                (bool) config('services.yookassa.ssl_verify', true)
            );

            $this->client = new Client($curlClient);
            $this->client->setAuth(
                (string) config('services.yookassa.shop_id'),
                (string) config('services.yookassa.secret_key')
            );
        }

        return $this->client;
    }

}
