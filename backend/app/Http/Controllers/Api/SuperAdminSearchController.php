<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\Request;

class SuperAdminSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json(['success' => true, 'data' => []]);
        }

        $enterprises = Enterprise::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('registration_number', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'email', 'registration_number', 'status']);

        $users = User::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'email', 'username', 'user_type']);

        $offers = Offer::query()
            ->where('name', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'price', 'duration_months']);

        $subscriptions = EnterpriseSubscription::query()
            ->with(['site.enterprise', 'offer'])
            ->whereHas('site.enterprise', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $results = [
            'enterprises' => $enterprises->map(fn ($item) => [
                'type' => 'enterprise',
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => $item->email,
                'meta' => $item->registration_number,
                'route' => "/superadmin/enterprises/{$item->id}",
            ]),
            'users' => $users->map(fn ($item) => [
                'type' => 'user',
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => $item->email,
                'meta' => $item->user_type,
                'route' => "/superadmin/users?search=" . urlencode((string) ($item->email ?: $item->name)),
            ]),
            'offers' => $offers->map(fn ($item) => [
                'type' => 'offer',
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => $item->price . ' FCFA',
                'meta' => $item->duration_months . ' mois',
                'route' => "/superadmin/offers/{$item->id}",
            ]),
            'subscriptions' => $subscriptions->map(fn ($item) => [
                'type' => 'subscription',
                'id' => $item->id,
                'title' => $item->site?->enterprise?->name ?? 'Abonnement',
                'subtitle' => $item->offer?->name ?? '',
                'meta' => $item->is_active ? 'Actif' : 'Inactif',
                'route' => "/superadmin/subscriptions/{$item->id}",
            ]),
        ];

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
