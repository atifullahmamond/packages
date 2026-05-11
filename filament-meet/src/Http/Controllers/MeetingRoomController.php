<?php

namespace Atifullahmamond\FilamentMeet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Atifullahmamond\FilamentMeet\Models\Meeting;
use Atifullahmamond\FilamentMeet\Services\MeetingService;

class MeetingRoomController extends Controller
{
    public function __construct(protected MeetingService $meetingService)
    {
    }

    public function show(Request $request, Meeting $meeting): \Illuminate\View\View
    {
        $user = Auth::user();

        abort_unless(
            $this->meetingService->canJoin($meeting, $user),
            403,
            'You are not authorized to join this meeting.'
        );

        abort_if($meeting->isEnded(), 410, 'This meeting has ended.');
        abort_if($meeting->isCancelled(), 410, 'This meeting has been cancelled.');

        return view('filament-meet::meeting-room', compact('meeting'));
    }
}
