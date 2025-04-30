<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    function index()
    {
        return view('tasks.index');
    }
    function getAll()
    {
        $tasks = Task::orderBy('created_at', 'desc')->get();
        return response()->json($tasks);
    }
    function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
        ]);
        // check for duplicate tasks
        $exists = Task::where('title', $request->title)->exists();
        if($exists){
            return response()->json(['error'=>'Task already exists'],422);
        }
        $task = Task::create([
            'title' => $request->title,
            'completed' => false,
        ]);

        return response()->json($task);
    }
    function toggle($id)
    {
        $task = Task::findOrFail($id);
        $task->completed = !$task->completed;
        $task->save();

        return response()->json($task);
    }
    public function delete($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(['success' => true]);
    }
}
