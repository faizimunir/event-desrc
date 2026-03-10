<?php

namespace App\Services;

use App\Models\Rider;
use App\Models\User;
use Illuminate\Support\Collection;

class RiderSimilarityService
{
    private const SIMILARITY_THRESHOLD = 70;

    /**
     * Bobot field untuk skor kemiripan (total 100).
     */
    private const WEIGHT_NAME = 25;

    private const WEIGHT_NICKNAME = 15;

    private const WEIGHT_POB = 15;

    private const WEIGHT_DOB = 20;

    private const WEIGHT_GENDER = 15;

    private const WEIGHT_NUMBER_PLATE = 10;

    /**
     * Cari rider yang mungkin sama: milik user dengan WA ini, dengan skor kemiripan >= 70%
     * dari name, nickname, pob, dob, gender, number_plate (menangani typo).
     */
    public function findSimilarRiders(
        string $whatsapp,
        string $name,
        ?string $nickname,
        ?string $pob,
        string $dob,
        string $gender,
        ?string $number_plate
    ): Collection {
        $normalized = WhacenterService::normalizeWhatsApp($whatsapp);
        $user = User::where('whatsapp', $normalized)->first();
        if (! $user) {
            return collect();
        }

        $riders = Rider::where('user_id', $user->id)->get();
        $result = collect();

        foreach ($riders as $rider) {
            $score = $this->computeSimilarityScore(
                $name,
                $nickname,
                $pob,
                $dob,
                $gender,
                $number_plate,
                $rider
            );
            if ($score >= self::SIMILARITY_THRESHOLD) {
                $result->push(['rider' => $rider, 'score' => round($score, 1)]);
            }
        }

        return $result;
    }

    /**
     * Hitung skor kemiripan 0–100 (weighted average semua field).
     */
    private function computeSimilarityScore(
        string $name,
        ?string $nickname,
        ?string $pob,
        string $dob,
        string $gender,
        ?string $number_plate,
        Rider $rider
    ): float {
        $sName = $this->textSimilarity($name, $rider->name ?? '');
        $sNickname = $this->textSimilarityOptional($nickname, $rider->nickname);
        $sPob = $this->textSimilarityOptional($pob, $rider->pob);
        $sDob = ($dob === ($rider->dob?->format('Y-m-d') ?? '')) ? 100.0 : 0.0;
        $sGender = ($gender === ($rider->gender ?? '')) ? 100.0 : 0.0;
        $sNumberPlate = $this->textSimilarityOptional($number_plate, $rider->number_plate);

        $totalWeight = self::WEIGHT_NAME + self::WEIGHT_NICKNAME + self::WEIGHT_POB
            + self::WEIGHT_DOB + self::WEIGHT_GENDER + self::WEIGHT_NUMBER_PLATE;

        return (
            $sName * (self::WEIGHT_NAME / $totalWeight) +
            $sNickname * (self::WEIGHT_NICKNAME / $totalWeight) +
            $sPob * (self::WEIGHT_POB / $totalWeight) +
            $sDob * (self::WEIGHT_DOB / $totalWeight) +
            $sGender * (self::WEIGHT_GENDER / $totalWeight) +
            $sNumberPlate * (self::WEIGHT_NUMBER_PLATE / $totalWeight)
        );
    }

    /**
     * Similarity 0–100 untuk teks wajib (nama). Tidak boleh keduanya kosong.
     */
    private function textSimilarity(string $a, string $b): float
    {
        $a = $this->normalizeText($a);
        $b = $this->normalizeText($b);
        if ($a === '' && $b === '') {
            return 100.0;
        }
        if ($a === '' || $b === '') {
            return 0.0;
        }
        similar_text($a, $b, $pct);

        return (float) $pct;
    }

    /**
     * Similarity 0–100 untuk field opsional: keduanya kosong = 100%, salah satu kosong = 0%, else similar_text.
     */
    private function textSimilarityOptional(?string $a, ?string $b): float
    {
        $a = $this->normalizeText($a ?? '');
        $b = $this->normalizeText($b ?? '');
        if ($a === '' && $b === '') {
            return 100.0;
        }
        if ($a === '' || $b === '') {
            return 0.0;
        }
        similar_text($a, $b, $pct);

        return (float) $pct;
    }

    private function normalizeText(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }
}
