<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPayment;
use Illuminate\Http\Request;
use App\Models\TaskCategory;
use App\Models\ClientTag;
use App\Models\ProspectTag;
use App\Models\Task;
use App\Models\TaskTag;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DataController extends Controller
{
    public function getSettings(): JsonResponse
    {   
        return response()->json([
            'success' => true,
            'task_categories' => TaskCategory::all(),
            'client_tags' => ClientTag::all(),
            'prospect_tags' => ProspectTag::all(),
            'task_tags' => TaskTag::all(),
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $lastMonth = $now->copy()->subMonth()->month;

        /**
         * -----------------------------------------------------
         * KPI CALCULATIONS
         * -----------------------------------------------------
         */

        // 1. totalClients
        $totalClients = Client::where('status', 'active')->count();

        // 2. trend %
        $currentMonthActive = Client::where('status', 'active')
            ->whereMonth('closed_at', $currentMonth)
            ->count();

        $lastMonthActive = Client::where('status', 'active')
            ->whereMonth('closed_at', $lastMonth)
            ->count();

        if ($lastMonthActive == 0) {
            $trend = "0%";
        } else {
            $trendValue = (($currentMonthActive - $lastMonthActive) / $lastMonthActive) * 100;
            $trend = sprintf("%+.1f%%", $trendValue);
        }

        // 3. newClients
        $newClients = Client::whereMonth('closed_at', $currentMonth)->where('status', 'active')->count();

        // 4. activeProspects
        $activeProspects = Client::where('status', 'prospect')->count();

        // 5. completedTasks (based on TaskCategory.type = 'done')
        $completedTasks = Task::whereHas('type', function ($q) {
            $q->where('type', 'done');
        })->count();

        // 6. taskCompletionRate
        $totalTasks = Task::count();
        $taskCompletionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100) . "%"
            : "0%";


        /**
         * -----------------------------------------------------
         * MONTHLY FLOW
         * -----------------------------------------------------
         */

        $monthlyFlow = [];
        for ($m = 1; $m <= 12; $m++) {
            $new = Client::whereMonth('closed_at', $m)->count();
            $lost = 0; // still always 0 for now
            $net = $new - $lost;

            $monthlyFlow[] = [
                'month' => Carbon::create()->month($m)->format('M'),
                'new'   => $new,
                'lost'  => $lost,
                'net'   => $net,
            ];
        }


        /**
         * -----------------------------------------------------
         * REVENUE (BASED ON payment_date)
         * -----------------------------------------------------
         */

        // MRR = payments made in the current month
        $mrr = ClientPayment::whereMonth('payment_date', $currentMonth)->sum('value');

        // ARR = payments made in the current year
        $arr = ClientPayment::whereYear('payment_date', $now->year)->sum('value');

        // Growth = revenue current month vs last month
        $currentMonthRevenue = $mrr;

        $lastMonthRevenue = ClientPayment::whereMonth('payment_date', $lastMonth)->sum('value');

        if ($lastMonthRevenue == 0) {
            $growth = "0%";
        } else {
            $growthValue = (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
            $growth = sprintf("%+.1f%%", $growthValue);
        }


        /**
         * -----------------------------------------------------
         * FINAL RESPONSE
         * -----------------------------------------------------
         */

        return response()->json([
            'kpis' => [
                'totalClients'       => $totalClients,
                'trend'              => $trend,
                'newClients'         => $newClients,
                'activeProspects'    => $activeProspects,
                'completedTasks'     => $completedTasks,
                'taskCompletionRate' => $taskCompletionRate,
            ],
            'monthlyFlow' => $monthlyFlow,
            'revenue' => [
                'mrr'    => $mrr,
                'arr'    => $arr,
                'growth' => $growth,
            ]
        ]);
    }

}
