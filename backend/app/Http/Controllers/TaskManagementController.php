<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class TaskManagementController extends Controller
{
    /**
     * List current jobs and failed jobs.
     */
    public function index()
    {
        $jobs = DB::table('jobs')->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')->get();
        $failed = DB::table('failed_jobs')->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')->get();
        return response()->json([
            'jobs' => $jobs,
            'failed' => $failed,
        ]);
    }

    /**
     * Perform an action on a job (stop, retry, delete).
     */
    public function action(Request $request, $id)
    {
        $action = $request->input('action');
        if ($action === 'stop') {
            DB::table('jobs')->where('id', $id)->delete();
            return response()->json(['status' => 'stopped']);
        }
        if ($action === 'retry') {
            $failedJob = DB::table('failed_jobs')->where('id', $id)->first();
            if ($failedJob) {
                DB::table('jobs')->insert([
                    'queue' => $failedJob->queue,
                    'payload' => $failedJob->payload,
                    'attempts' => 0,
                    'reserved_at' => null,
                    'available_at' => now()->timestamp,
                    'created_at' => now()->timestamp,
                ]);
                DB::table('failed_jobs')->where('id', $id)->delete();
                return response()->json(['status' => 'retried']);
            }
        }
        if ($action === 'cancel_python') {
            $script = base_path('workers/scripts/manage_worker.py');
            $process = new \Symfony\Component\Process\Process(['python3', $script, $id, 'stop']);
            $process->run();
            return response()->json(['status' => 'python_stopped']);
        }
        return response()->json(['error' => 'invalid_action'], 400);
    }
}
