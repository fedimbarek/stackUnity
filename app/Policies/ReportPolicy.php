<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'gestionnaire']);
    }

    public function view(User $user, Report $report): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $report->neighborhood_id === null || $report->neighborhood_id === $user->neighborhood_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'gestionnaire']);
    }

    public function update(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->hasRole('admin') || $report->generated_by === $user->id;
    }
}