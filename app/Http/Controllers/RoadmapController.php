<?php

namespace App\Http\Controllers;

use App\Models\Roadmap;
use App\Models\RoadmapFollow;
use App\Models\RoadmapProgress;
use App\Models\RoadmapStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoadmapController extends Controller
{
    public function index()
    {
        $roadmaps = Roadmap::query()
            ->withCount('steps')
            ->latest()
            ->get();

        return view('roadmaps.index', compact('roadmaps'));
    }

    public function create()
    {
        return view('roadmaps.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'steps' => 'required|string|max:5000',
        ]);

        $roadmap = Roadmap::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
        ]);

        $steps = collect(preg_split('/[\n]+/', (string) $request->steps) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        $steps->each(function ($step, $index) use ($roadmap) {
            RoadmapStep::create([
                'roadmap_id' => $roadmap->id,
                'title' => $step,
                'sort_order' => $index + 1,
            ]);
        });

        return redirect()->route('roadmaps.show', $roadmap->id)->with('success', 'Roadmap created.');
    }

    public function show(Roadmap $roadmap)
    {
        $roadmap->load('steps');

        $progressIds = RoadmapProgress::query()
            ->where('user_id', Auth::id())
            ->pluck('roadmap_step_id')
            ->all();

        $isFollowing = RoadmapFollow::query()
            ->where('roadmap_id', $roadmap->id)
            ->where('user_id', Auth::id())
            ->exists();

        return view('roadmaps.show', compact('roadmap', 'progressIds', 'isFollowing'));
    }

    public function follow(Roadmap $roadmap)
    {
        RoadmapFollow::firstOrCreate([
            'roadmap_id' => $roadmap->id,
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Roadmap followed.');
    }

    public function toggleStep(RoadmapStep $step)
    {
        $existing = RoadmapProgress::query()
            ->where('roadmap_step_id', $step->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Step marked incomplete.');
        }

        RoadmapProgress::create([
            'roadmap_step_id' => $step->id,
            'user_id' => Auth::id(),
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Step completed.');
    }
}
