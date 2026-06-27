@extends('layouts.app')

@section('title', 'Hearing Calendar')
@section('page-title', 'Hearing Calendar')
@section('breadcrumbs', 'Case Management / Hearing Calendar')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
.calendar-wrap { min-height: 500px; }
.fc-event { cursor: pointer; border-radius: 4px; padding: 2px 4px; font-size: .82rem; }
.fc-toolbar-title { font-size: 1.1rem !important; }
</style>
@endpush

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div id="calendar" class="calendar-wrap"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek,listWeek'
        },
        height: 'auto',
        eventSources: [{
            url: '{{ route('cases.calendar') }}',
            method: 'GET',
            extraParams: { _: Date.now() },
            failure: function () {
                console.error('Failed to load calendar events');
            }
        }],
        eventClick: function (info) {
            if (info.event.url) {
                window.location.href = info.event.url;
            }
        },
        loading: function (isLoading) {
            if (isLoading) {
                calendarEl.style.opacity = '0.5';
            } else {
                calendarEl.style.opacity = '1';
            }
        }
    });

    calendar.render();
});
</script>
@endpush
