@extends('layouts.app')

@section('title', 'Edit Letter - '.$letter->ref_no)

@section('content')
    <div class="grid gap-6 lg:grid-cols-3" x-data="letterEditor()">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-bold text-slate-900">{{ $letter->ref_no }}</h1>
                            <p class="text-sm text-slate-600">Editing letter for <strong>{{ $letter->officer?->full_name_en }}</strong> ({{ $letter->officer?->nic_no }})</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('letters.show', $letter->letterBatch) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Back to Batch</a>
                            @if ($letter->status === \App\Enums\LetterStatus::Final)
                                <a href="{{ route('letters.pdf', $letter) }}" target="_blank" class="btn-primary !px-3 !py-1.5 text-xs">Download PDF</a>
                            @endif
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('letters.update', $letter) }}" class="p-5" @submit="syncBody()">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="ref_no" class="block text-sm font-medium text-slate-700">Reference No</label>
                                <input type="text" name="ref_no" id="ref_no" value="{{ $letter->ref_no }}"
                                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <label for="subject" class="block text-sm font-medium text-slate-700">Subject</label>
                                <input type="text" name="subject" id="subject" value="{{ $letter->subject }}"
                                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="signatory_id" class="block text-sm font-medium text-slate-700">Signatory (on behalf of Secretary)</label>
                                <select name="signatory_id" id="signatory_id"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">— Select signatory —</option>
                                    @foreach ($secretaryOptions as $option)
                                        <option value="{{ $option->id }}" @selected($letter->signatory_id === $option->id)>{{ $option->select_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="controlling_officer_id" class="block text-sm font-medium text-slate-700">Controlling Officer</label>
                                <select name="controlling_officer_id" id="controlling_officer_id"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">— Select controlling officer —</option>
                                    @foreach ($controllingOptions as $option)
                                        <option value="{{ $option->id }}" @selected($letter->controlling_officer_id === $option->id)>{{ $option->select_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Letter Body</label>
                            <div class="mt-1 overflow-hidden rounded-lg border border-slate-300 shadow-sm">
                                <div class="letter-editor-toolbar">
                                    <button type="button" @click="exec('bold')" title="Bold"><b>B</b></button>
                                    <button type="button" @click="exec('italic')" title="Italic"><i>I</i></button>
                                    <button type="button" @click="exec('underline')" title="Underline"><u>U</u></button>
                                    <button type="button" @click="exec('insertUnorderedList')" title="Bullet list">&bull; List</button>
                                    <button type="button" @click="exec('insertOrderedList')" title="Numbered list">1. List</button>
                                    <button type="button" @click="exec('justifyLeft')" title="Align left">&lt;|</button>
                                    <button type="button" @click="exec('justifyCenter')" title="Center">|&lt;&gt;|</button>
                                    <button type="button" @click="exec('justifyRight')" title="Align right">|&gt;</button>
                                    <button type="button" @click="exec('formatBlock', 'p')" title="Paragraph">P</button>
                                    <button type="button" @click="exec('formatBlock', 'h2')" title="Heading">H2</button>
                                </div>
                                <div
                                    id="letter-body"
                                    class="letter-body-editor"
                                    contenteditable="true"
                                    data-placeholder="Compose the letter body..."
                                    x-ref="body"
                                    x-init="$nextTick(() => { $refs.body.innerHTML = $refs.source.value; })"
                                ></div>
                                <textarea name="body" id="body" x-ref="source" class="hidden">{!! $letter->body !!}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <button type="submit" class="btn-primary">Save Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Officer</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">NIC</dt><dd class="font-semibold">{{ $letter->officer?->nic_no }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Grade</dt><dd class="font-semibold">{{ is_object($letter->officer?->current_grade) ? $letter->officer?->current_grade?->label() : $letter->officer?->current_grade }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">DS Division</dt>
                        <dd class="font-semibold">{{ is_object($letter->officer?->ds_division) ? ($letter->officer?->ds_division?->name_en ?? $letter->officer?->ds_division?->name_si) : ($letter->officer?->ds_division ?? $letter->officer?->dsDivision?->name_en) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">District</dt>
                        <dd class="font-semibold">{{ is_object($letter->officer?->district) ? ($letter->officer?->district?->name_en ?? $letter->officer?->district?->name_si) : ($letter->officer?->district ?? $letter->officer?->district?->name_en) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Signatories</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Signatory (on behalf of Secretary)</dt>
                        @if ($letter->signatory)
                            <dd class="mt-1 font-semibold text-slate-800">{{ $letter->signatory->officer_name }}</dd>
                            <dd class="text-slate-600">{{ $letter->signatory->designation }}</dd>
                        @else
                            <dd class="mt-1 text-amber-600">None selected. Configure in the Admin panel.</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Controlling Officer</dt>
                        @if ($letter->controllingOfficer)
                            <dd class="mt-1 font-semibold text-slate-800">{{ $letter->controllingOfficer->officer_name }}</dd>
                            <dd class="text-slate-600">{{ $letter->controllingOfficer->designation }}</dd>
                        @else
                            <dd class="mt-1 text-amber-600">None selected. Configure in the Admin panel.</dd>
                        @endif
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Auto CC</h2>
                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                    @forelse ($letter->cc_to ?? [] as $cc)
                        <li class="rounded bg-slate-50 px-2 py-1">{{ $cc }}</li>
                    @empty
                        <li class="text-slate-400">No CC recipients configured.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Finalize</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Finalizing the <strong>{{ $letter->letterBatch?->document_type?->label() }}</strong> will:
                </p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-slate-600">
                    <li>Update the officer record (grade / status / station)</li>
                    <li>Append a service history entry</li>
                    <li>Archive the PDF as an official document</li>
                </ul>

                <form method="POST" action="{{ route('letters.finalize', $letter) }}" class="mt-4"
                      onsubmit="return confirm('Finalize this letter? The officer record will be updated and the document archived.')">
                    @csrf
                    <button type="submit" class="btn-success w-full justify-center" {{ $letter->status === \App\Enums\LetterStatus::Final ? 'disabled' : '' }}>
                        {{ $letter->status === \App\Enums\LetterStatus::Final ? 'Finalized' : 'Finalize & Archive' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function letterEditor() {
            return {
                exec(command, value = null) {
                    document.execCommand(command, false, value);
                },
                syncBody() {
                    this.$refs.source.value = this.$refs.body.innerHTML;
                },
            };
        }
    </script>
@endpush