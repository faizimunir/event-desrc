<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventCheckin;
use App\Models\Registration;
use App\Models\Ticket;

class EventTicketCheckinScanService
{
    /**
     * @return array{type: string, message: string, checkin: ?EventCheckin}
     */
    public function process(Event $event, string $scannedCode, ?int $checkedInByUserId = null): array
    {
        $ticketCode = $this->extractTicketCode($scannedCode);
        if ($ticketCode === '') {
            return $this->result('error', __('Scan code is empty or invalid.'));
        }

        $ticket = Ticket::query()->where('ticket_code', $ticketCode)->first();
        if (! $ticket) {
            return $this->result('error', __('Ticket not found for code :code.', ['code' => $ticketCode]));
        }

        $registration = $ticket->registration()->with(['rider', 'bracket'])->first();
        if (! $registration) {
            return $this->result('error', __('Registration not found for this ticket.'));
        }

        if ($registration->event_id !== $event->id) {
            $riderName = $registration->rider?->name ?? __('Rider');

            return $this->result('error', __(':name is registered for a different event.', ['name' => $riderName]));
        }

        if ($registration->status !== Registration::STATUS_APPROVED) {
            $riderName = $registration->rider?->name ?? __('Rider');

            return $this->result('error', __(':name registration is not approved yet.', ['name' => $riderName]));
        }

        if ($registration->checkin()->exists()) {
            $riderName = $registration->rider?->name ?? __('Rider');

            return $this->result('info', __(':name is already checked in.', ['name' => $riderName]), $registration->checkin);
        }

        $checkin = $event->checkins()->create([
            'registration_id' => $registration->id,
            'checked_in_by' => $checkedInByUserId,
        ]);

        $riderName = $registration->rider?->name ?? __('Rider');

        return $this->result(
            'success',
            __(':name checked in.', ['name' => $riderName]),
            $checkin->load(['registration.rider', 'registration.bracket', 'checkedInByUser']),
            $registration->checkinSummary(),
        );
    }

    protected function extractTicketCode(string $scanned): string
    {
        $scanned = trim($scanned);
        if ($scanned === '') {
            return '';
        }

        if (filter_var($scanned, FILTER_VALIDATE_URL)) {
            $path = parse_url($scanned, PHP_URL_PATH) ?? '';
            if (preg_match('#/tickets/(?:verify/)?(TKT-[A-Z0-9-]+)#i', $path, $matches)) {
                return strtoupper($matches[1]);
            }
        }

        if (preg_match('/^(TKT-[A-Z0-9-]+)$/i', $scanned, $matches)) {
            return strtoupper($matches[1]);
        }

        return '';
    }

    /**
     * @return array{type: string, message: string, checkin: ?EventCheckin, summary: ?array}
     */
    protected function result(string $type, string $message, ?EventCheckin $checkin = null, ?array $summary = null): array
    {
        return [
            'type' => $type,
            'message' => $message,
            'checkin' => $checkin,
            'summary' => $summary,
        ];
    }
}
