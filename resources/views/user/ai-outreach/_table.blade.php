{{-- Results table. Expects $rows (paginator/collection of AiLeadAnalysis); $selectable adds checkboxes for form#folder-form --}}
@include('user._ui-kit')
@php $selectable = $selectable ?? false; @endphp
<div class="overflow-x-auto cnm-ui">
    <table class="ui-table min-w-full">
        <thead>
            <tr>
                @if($selectable)<th class="w-8"><input type="checkbox" class="ui-check" aria-label="Select all" onclick="document.querySelectorAll('.cnm-row-check').forEach(c => c.checked = this.checked); cnmUpdateSelected();"></th>@endif
                <th>Business</th>
                <th>Website status</th>
                <th>Audit status</th>
                <th>Selected offer</th>
                <th>Email status</th>
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
                    <td>@include('user.ai-outreach._badges', ['analysis' => $row, 'which' => 'website'])</td>
                    <td>@include('user.ai-outreach._badges', ['analysis' => $row, 'which' => 'status'])</td>
                    <td class="text-slate-700 max-w-[14rem] break-words">{{ $row->recommendedOffer?->title ?? '—' }}</td>
                    <td>@include('user.ai-outreach._badges', ['analysis' => $row, 'which' => 'draft', 'draft' => $row->currentDraft])</td>
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
                    <td colspan="{{ $selectable ? 7 : 6 }}">
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
