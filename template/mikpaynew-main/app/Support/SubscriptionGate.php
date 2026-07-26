<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

/**
 * Akses fitur utama aplikasi (tenant) berdasarkan status & tanggal langganan per user.
 */
final class SubscriptionGate
{
    /**
     * True jika user boleh membuka dashboard, sessions, hotspot, dll.
     * Super admin selalu lolos. Status selain trial/active ditolak.
     * Jika ada subscription_end, berlaku sampai akhir hari tanggal tersebut (inklusif).
     */
    public static function allowsAppAccess(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $status = strtolower(trim((string) ($user->subscription_status ?? '')));

        if (in_array($status, ['pending_payment', 'expired'], true)) {
            return false;
        }

        if (in_array($status, ['trial', 'active'], true)) {
            if ($user->subscription_end === null) {
                return true;
            }
            try {
                $end = Carbon::parse($user->subscription_end)->endOfDay();

                return now()->lessThanOrEqualTo($end);
            } catch (\Throwable $e) {
                return true;
            }
        }

        // Data lama: kolom langganan belum pernah diisi → jangan kunci tenant yang sudah jalan.
        if ($status === '' && $user->subscription_end === null) {
            return true;
        }

        if ($status === '' && $user->subscription_end !== null) {
            try {
                $end = Carbon::parse($user->subscription_end)->endOfDay();

                return now()->lessThanOrEqualTo($end);
            } catch (\Throwable $e) {
                return true;
            }
        }

        return false;
    }
}
