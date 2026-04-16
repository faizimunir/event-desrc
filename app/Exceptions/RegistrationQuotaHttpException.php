<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response HTTP dari dalam transaksi kuota; rider baru boleh dihapus hanya jika flag ini true.
 */
class RegistrationQuotaHttpException extends HttpResponseException
{
    public function __construct(Response $response, public bool $deleteNewRider = false)
    {
        parent::__construct($response);
    }
}
