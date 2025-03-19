<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardApiController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        // Time period calculations
        $now = Carbon::now();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = $startOfWeek->copy()->subSecond();

        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = $startOfMonth->copy()->subSecond();

        // Calculate statistics
        $totalContacts = Contact::count();

        // Today's contacts
        $contactsToday = Contact::whereDate('created_at', $today)->count();

        // Yesterday's contacts
        $contactsYesterday = Contact::whereDate('created_at', $yesterday)->count();

        // This week's contacts
        $contactsThisWeek = Contact::where('created_at', '>=', $startOfWeek)->count();

        // Last week's contacts (between start of last week and end of last week)
        $contactsLastWeek = Contact::whereBetween('created_at', [
            $startOfLastWeek,
            $endOfLastWeek
        ])->count();

        // This month's contacts
        $contactsThisMonth = Contact::where('created_at', '>=', $startOfMonth)->count();

        // Last month's contacts (between start of last month and end of last month)
        $contactsLastMonth = Contact::whereBetween('created_at', [
            $startOfLastMonth,
            $endOfLastMonth
        ])->count();

        // Average contacts per day over the last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $contactsLast30Days = Contact::where('created_at', '>=', $thirtyDaysAgo)->count();
        $avgPerDay = number_format($contactsLast30Days / 30, 1);

        return response()->json([
            'totalContacts' => $totalContacts,
            'contactsToday' => $contactsToday,
            'contactsYesterday' => $contactsYesterday,
            'contactsThisWeek' => $contactsThisWeek,
            'contactsLastWeek' => $contactsLastWeek,
            'contactsThisMonth' => $contactsThisMonth,
            'contactsLastMonth' => $contactsLastMonth,
            'avgPerDay' => $avgPerDay,
        ]);
    }
}
