<?php

namespace App\Services;

use App\Models\Bracket;
use App\Models\Rider;
use Carbon\Carbon;

class RegistrationEligibilityService
{
    /**
     * Check if a rider is eligible for a bracket (age/year + gender).
     * Returns array with 'eligible' (bool) and 'message' (string).
     */
    public function checkEligibility(Rider $rider, Bracket $bracket): array
    {
        if ($bracket->rule_type === Bracket::RULE_TYPE_AGE) {
            return $this->checkAgeEligibility($rider, $bracket);
        }
        if ($bracket->rule_type === Bracket::RULE_TYPE_BIRTH_YEAR) {
            return $this->checkBirthYearEligibility($rider, $bracket);
        }
        return ['eligible' => true, 'message' => __('No age/year rule for this bracket.')];
    }

    protected function checkAgeEligibility(Rider $rider, Bracket $bracket): array
    {
        if (! $rider->dob) {
            return ['eligible' => false, 'message' => __('Date of birth is required for age-based brackets.')];
        }

        $refDate = $bracket->age_ref_date ? Carbon::parse($bracket->age_ref_date) : $bracket->event?->start_at ?? now();
        $age = $rider->ageOn($refDate);

        if ($age === null) {
            return ['eligible' => false, 'message' => __('Could not calculate age.')];
        }

        $min = $bracket->age_min;
        $max = $bracket->age_max;

        if ($min !== null && $age < $min) {
            return ['eligible' => false, 'message' => __('Minimum age for this bracket is :min. Your age on reference date: :age.', ['min' => $min, 'age' => $age])];
        }
        if ($max !== null && $age > $max) {
            return ['eligible' => false, 'message' => __('Maximum age for this bracket is :max. Your age on reference date: :age.', ['max' => $max, 'age' => $age])];
        }

        $genderCheck = $this->checkGender($rider, $bracket);
        if (! $genderCheck['eligible']) {
            return $genderCheck;
        }

        return ['eligible' => true, 'message' => __('Eligible.')];
    }

    protected function checkBirthYearEligibility(Rider $rider, Bracket $bracket): array
    {
        if (! $rider->dob) {
            return ['eligible' => false, 'message' => __('Date of birth is required for birth-year brackets.')];
        }

        $year = $rider->birthYear();
        $start = $bracket->birth_year_start;
        $end = $bracket->birth_year_end;

        if ($start !== null && $year < $start) {
            return ['eligible' => false, 'message' => __('Birth year must be :start or later. Your birth year: :year.', ['start' => $start, 'year' => $year])];
        }
        if ($end !== null && $year > $end) {
            return ['eligible' => false, 'message' => __('Birth year must be :end or earlier. Your birth year: :year.', ['end' => $end, 'year' => $year])];
        }

        $genderCheck = $this->checkGender($rider, $bracket);
        if (! $genderCheck['eligible']) {
            return $genderCheck;
        }

        return ['eligible' => true, 'message' => __('Eligible.')];
    }

    protected function checkGender(Rider $rider, Bracket $bracket): array
    {
        if (! $bracket->gender_rule || $bracket->gender_rule === 'mixed') {
            return ['eligible' => true, 'message' => ''];
        }
        if (! $rider->gender) {
            return ['eligible' => false, 'message' => __('Gender is required for this bracket.')];
        }
        $riderGender = strtolower($rider->gender);
        $rule = strtolower($bracket->gender_rule);
        if ($rule === Bracket::GENDER_BOYS && $riderGender !== 'boys') {
            return ['eligible' => false, 'message' => __('This bracket is for boys only.')];
        }
        if ($rule === Bracket::GENDER_GIRLS && $riderGender !== 'girls') {
            return ['eligible' => false, 'message' => __('This bracket is for girls only.')];
        }
        return ['eligible' => true, 'message' => ''];
    }
}
