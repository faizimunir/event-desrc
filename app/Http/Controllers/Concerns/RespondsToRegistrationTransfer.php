<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

trait RespondsToRegistrationTransfer
{
    /**
     * @param  array{moved: int, failed: list<array{registration_id: int, rider: string, reason: string}>, price_changed: int}  $result
     */
    protected function transferResponse(RedirectResponse $redirect, array $result, string $targetName): RedirectResponse
    {
        if ($result['failed'] !== []) {
            $redirect->with('transfer_failures', $result['failed']);
        }

        if ($result['failed'] !== [] && $result['moved'] === 0) {
            return $redirect->with('error', __('No registrants could be moved to :target. See the details below.', ['target' => $targetName]));
        }

        $message = __(':moved registrants moved to :target.', ['moved' => $result['moved'], 'target' => $targetName]);
        if ($result['price_changed'] > 0) {
            $message .= ' '.__(':count of them now use a package with a different price; existing payment records were not changed.', ['count' => $result['price_changed']]);
        }
        if ($result['failed'] !== []) {
            $message .= ' '.__(':failed could not be moved. See the details below.', ['failed' => count($result['failed'])]);
        }

        return $redirect->with('status', $message);
    }
}
