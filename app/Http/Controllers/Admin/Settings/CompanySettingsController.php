<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Inertia\Inertia;

class CompanySettingsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('admin/settings/company/index', [
            'companies' => Client::query()
                ->select('id', 'company_name', 'trade', 'industry', 'website', 'company_address_state')
                ->when($search !== '', function ($query) use ($search) {
                    $searchValue = '%' . mb_strtolower($search) . '%';

                    $query->where(function ($query) use ($searchValue) {
                        $query
                            ->whereRaw('LOWER(company_name) LIKE ?', [$searchValue])
                            ->orWhereRaw('LOWER(trade) LIKE ?', [$searchValue])
                            ->orWhereRaw('LOWER(industry) LIKE ?', [$searchValue])
                            ->orWhereRaw('LOWER(company_address_state) LIKE ?', [$searchValue]);
                    });
                })
                ->orderBy('company_name')
                ->paginate(10)
                ->withQueryString(),

            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
