<?php

namespace App\Services;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AccessScope
{
    public static function officers(User $user): Builder
    {
        $query = Officer::query();
        if ($user->isOfficer()) {
            return $query->where('user_id', $user->id);
        }
        if ($user->isDivisionalAdmin()) {
            return $query->where('current_ds_division_id', $user->ds_division_id ?? -1);
        }
        if ($user->isDistrictAdmin()) {
            return $query->where('current_district_id', $user->district_id ?? -1);
        }

        return $query;
    }

    public static function officer(User $user, int $id): Officer
    {
        return self::officers($user)->findOrFail($id);
    }

    public static function batches(Builder $query, User $user): Builder
    {
        return $user->isMainAdmin() ? $query : $query->where('created_by', $user->id);
    }
}
