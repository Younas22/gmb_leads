{{-- Results table. Expects $rows (paginator/collection of AiLeadAnalysis); $selectable adds checkboxes for form#folder-form --}}
@include('user._ui-kit')
@php
    $selectable = $selectable ?? false;
    // Lead id => send state ('sent'|'queued'|'failed'); a missing lead = not sent. Null hides the column.
    $sendStates = $sendStates ?? null;
    $sendCols = $sendStates !== null ? 1 : 0;
@endphp
<div class="overflow-x-auto cnm-ui">
    <table class="ui-table min-w-full">
        <thead>
            <tr>
                @if($selectable)<th class="w-8"><input type="checkbox" class="ui-check" aria-label="Select all" onclick="document.querySelectorAll('.cnm-row-check').forEach(c => c.checked = this.checked); cnmUpdateSelected();"></th>@endif
                <th>Business</th>
                <th>Email</th>
                <th>Website status</th>
                <th>Audit status</th>
                <th>Selected offer</th>
                <th>Email status</th>
                @if($sendStates !== null)<th>Send status</th>@endif
                <th class="!text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr data-analysis-id="{{ (int) $row->id }}">
                    @if($selectable)<td><input type="checkbox" class="cnm-row-check ui-check" name="analysis_ids[]" value="{{ (int) $row->id }}" form="folder-form" onchange="cnmUpdateSelected()" aria-label="Select {{ $row->lead?->name }}"></td>@endif
                    <td>
                        <div class="font-medium text-gray-900 break-words max-w-xs">{{ $row->lead?->name ?? 'Lead removed' }}</div>
                        @if($row->error_message && in_array($row->status, ['failed', 'quota_exhausted'], true))
                            <div class="text-[11.5px] text-red-600 mt-0.5 max-w-xs">{{ $row->error_message }}</div>
                        @endif
                    </td>
                    <td class="min-w-[12rem]">
                        @if($row->lead)
                            @include('user.partials._lead-email', ['lead' => $row->lead])
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>@include('user.ai-outreach._badges', ['analysis' => $row, 'which' => 'website'])</td>
                    <td>@include('user.ai-outreach._badges', ['analysis' => $row, 'which' => 'status'])</td>
                    <td class="text-slate-700 max-w-[14rem] break-words">{{ $row->recommendedOffer?->title ?? '—' }}</td>
                    <td>@include('user.ai-outreach._badges', ['analysis' => $row, 'which' => 'draft', 'draft' => $row->currentDraft])</td>
                    @if($sendStates !== null)
                        @php
                            $st = $sendStates[$row->saved_lead_id] ?? 'not_sent';
                            $stCls = match ($st) {
                                'sent' => 'bg-green-50 text-green-700 border-green-200',
                                'queued' => 'bg-orange-50 text-orange-700 border-orange-200',
                                'failed' => 'bg-red-50 text-red-700 border-red-200',
                                default => 'bg-gray-50 text-gray-500 border-gray-200',
                            };
                        @endphp
                        <td><span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full border whitespace-nowrap {{ $stCls }}" data-send-state="{{ $st }}">{{ \App\Http\Controllers\AiOutreach\AiOutreachController::SEND_STATE_LABELS[$st] }}</span></td>
                    @endif
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('user.ai-outreach.analysis', $row->id) }}" class="ui-btn ui-sm">
                            <i class="fas fa-eye"></i> Open
                        </a>
                        @if(in_array($row->status, ['failed', 'quota_exhausted'], true))
                            <form method="POST" action="{{ route('user.ai-outreach.analysis.retry', $row->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="ui-btn-light ui-sm">
                                    <i class="fas fa-redo"></i> Retry
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($selectable ? 8 : 7) + $sendCols }}">
                        <div class="ui-empty">
                            <div class="ui-empty-i"><i class="fas fa-wand-magic-sparkles"></i></div>
                            <p class="ui-empty-t">No AI analyses yet</p>
                            <p class="mt-1">Go to <a href="{{ route('user.leads') }}" class="ui-link">My Leads</a>, select leads and click "Analyze with AI". For each lead you'll get a website audit, a matched offer and a ready-to-review email.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
