<?php

namespace App\Services;

use YooKassa\Client\CurlClient;

/**
 * SSL-настройки для Windows (cacert.pem), вызываются после initCurl в SDK.
 */
class YooKassaCurlClient extends CurlClient
{
    private ?string $cafile = null;

    private bool $sslVerify = true;

    public function configureSsl(?string $cafile, bool $sslVerify): void
    {
        $this->cafile = $cafile;
        $this->sslVerify = $sslVerify;
    }

    public function setAdvancedCurlOptions(): void
    {
        if (is_string($this->cafile) && $this->cafile !== '') {
            $this->setCurlOption(CURLOPT_CAINFO, $this->cafile);

            return;
        }

        if (! $this->sslVerify) {
            $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 0);
        }
    }
}
