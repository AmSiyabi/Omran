<div class="mx-auto max-w-3xl">
    {{-- الترويسة --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="font-mono text-xs text-muted" dir="ltr">{{ $cohort->code }}</p>
            <h1 class="mt-1 text-2xl text-navy">{{ $cohort->displayTitle() }}</h1>
            <p class="mt-1 text-sm text-muted">
                {{ $cohort->starts_at->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}
                → {{ $cohort->ends_at->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}
                · {{ $cohort->delivery_mode->label() }}
                @if ($cohort->city_ar) · {{ $cohort->city_ar }} @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-badge :variant="$cohort->status->badgeVariant()">{{ $cohort->status->label() }}</x-badge>
            @can('update', $cohort)
                <x-button variant="secondary" size="sm" :href="route('admin.cohorts.edit', $cohort)" wire:navigate>{{ __('common.edit') }}</x-button>
            @endcan
        </div>
    </div>

    {{-- بيانات سريعة --}}
    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-card :padding="false" class="p-3 text-center">
            <p class="text-xs text-muted">{{ __('courses.price') }}</p>
            <p class="mt-1 text-sm font-bold text-navy">
                {{ $cohort->is_free ? __('courses.is_free') : \App\Support\Baisa::format($cohort->price_baisa) }}
            </p>
        </x-card>
        <x-card :padding="false" class="p-3 text-center">
            <p class="text-xs text-muted">{{ __('courses.seats_taken') }}</p>
            <p class="mt-1 text-sm font-bold text-navy">{{ $cohort->seats_taken }}{{ $cohort->capacity ? ' / '.$cohort->capacity : '' }}</p>
        </x-card>
        <x-card :padding="false" class="p-3 text-center">
            <p class="text-xs text-muted">{{ __('courses.client') }}</p>
            <p class="mt-1 truncate text-sm font-bold text-navy">{{ $cohort->client->name_ar ?? '—' }}</p>
        </x-card>
        <x-card :padding="false" class="p-3 text-center">
            <p class="text-xs text-muted">{{ __('courses.invoicing_entity') }}</p>
            <p class="mt-1 truncate text-sm font-bold text-navy">{{ $cohort->invoicingEntity->name_ar }}</p>
        </x-card>
    </div>

    {{-- تغيير الحالة --}}
    @can('transition', $cohort)
        @if ($availableTransitions !== [])
            <x-card class="mt-5">
                <h2 class="font-bold text-navy">{{ __('courses.transition_confirm_title') }}</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($availableTransitions as $transition)
                        <x-button
                            :variant="$transition === \App\Enums\CohortStatus::Cancelled ? 'danger' : 'secondary'"
                            size="sm"
                            wire:click="confirmTransition('{{ $transition->value }}')"
                            wire:key="transition-{{ $transition->value }}"
                        >
                            {{ $transition === \App\Enums\CohortStatus::Cancelled ? __('courses.cancel_cohort') : __('courses.transition_to', ['status' => $transition->label()]) }}
                        </x-button>
                    @endforeach
                </div>
            </x-card>
        @endif
    @endcan

    {{-- الجلسات --}}
    <x-card class="mt-5" :padding="false">
        <x-slot:header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-navy">{{ __('courses.sessions') }}</h2>
                @can('update', $cohort)
                    <x-button variant="secondary" size="sm" wire:click="createSession">{{ __('courses.new_session') }}</x-button>
                @endcan
            </div>
        </x-slot:header>

        @if ($cohort->sessions->isEmpty())
            <x-empty-state :title="__('courses.no_sessions')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($cohort->sessions as $session)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5" wire:key="session-{{ $session->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-navy">
                                {{ $session->session_number }}. {{ $session->title_ar ?? __('courses.session') }}
                            </p>
                            <p class="text-xs text-muted" dir="ltr">
                                {{ $session->starts_at->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}
                                – {{ $session->ends_at->timezone(config('app.display_timezone'))->format('H:i') }}
                            </p>
                        </div>
                        @can('update', $cohort)
                            <div class="flex shrink-0 gap-1">
                                <x-button variant="ghost" size="sm" wire:click="editSession({{ $session->id }})">{{ __('common.edit') }}</x-button>
                                <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmDeleteSession({{ $session->id }})">{{ __('common.delete') }}</x-button>
                            </div>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    {{-- المنفذون --}}
    <x-card class="mt-5" :padding="false">
        <x-slot:header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-navy">{{ __('courses.deliverers') }}</h2>
                @can('update', $cohort)
                    <x-button variant="secondary" size="sm" wire:click="editDeliverers">{{ __('courses.manage_deliverers') }}</x-button>
                @endcan
            </div>
        </x-slot:header>

        @if ($cohort->deliverers->isEmpty())
            <x-empty-state :title="__('courses.no_deliverers')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($cohort->deliverers as $deliverer)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5" wire:key="deliverer-{{ $deliverer->id }}">
                        <div class="flex min-w-0 items-center gap-2">
                            <x-badge :variant="$deliverer->deliverer_type === \App\Enums\DelivererType::Partner ? 'gold' : 'info'">
                                {{ $deliverer->deliverer_type->label() }}
                            </x-badge>
                            <span class="truncate text-sm font-medium text-navy">{{ $deliverer->displayName() }}</span>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-navy">{{ rtrim(rtrim((string) $deliverer->share_weight, '0'), '.') }}%</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    @if ($cohort->internal_notes)
        <x-card class="mt-5">
            <h2 class="font-bold text-navy">{{ __('courses.internal_notes') }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-muted">{{ $cohort->internal_notes }}</p>
        </x-card>
    @endif

    {{-- تأكيد تغيير الحالة --}}
    <x-lw-modal :show="$pendingTransition !== null" close="cancelTransition" :title="__('courses.transition_confirm_title')" maxWidth="sm">
        @if ($pendingTransition !== null)
            <p class="text-sm text-muted">
                {{ __('courses.transition_confirm_text', ['code' => $cohort->code, 'status' => \App\Enums\CohortStatus::from($pendingTransition)->label()]) }}
            </p>
        @endif
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button wire:click="applyTransition" wire:loading.attr="disabled" wire:target="applyTransition">{{ __('common.confirm') }}</x-button>
            <x-button variant="ghost" wire:click="cancelTransition">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>

    {{-- نموذج الجلسة --}}
    <x-lw-modal :show="$showSessionForm" close="closeSessionForm" :title="$editingSessionId ? __('courses.edit_session') : __('courses.new_session')">
        <form wire:submit="saveSession" class="space-y-4">
            <x-input :label="__('courses.session_title')" name="session_title" wire:model="session_title" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input :label="__('courses.starts_at')" name="session_starts_at" type="datetime-local" wire:model="session_starts_at" required />
                <x-input :label="__('courses.ends_at')" name="session_ends_at" type="datetime-local" wire:model="session_ends_at" required />
            </div>
            <x-input :label="__('courses.venue_override')" name="session_venue" wire:model="session_venue" />
            <x-textarea :label="__('courses.internal_notes')" name="session_notes" wire:model="session_notes" rows="2" :hint="__('common.optional')" />

            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <x-button type="submit" target="saveSession" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                <x-button variant="ghost" type="button" wire:click="closeSessionForm">{{ __('common.cancel') }}</x-button>
            </div>
        </form>
    </x-lw-modal>

    {{-- تأكيد حذف جلسة --}}
    <x-lw-modal :show="$deletingSessionId !== null" close="cancelDeleteSession" :title="__('courses.delete_session_title', ['number' => $cohort->sessions->firstWhere('id', $deletingSessionId)?->session_number ?? ''])" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('common.are_you_sure') }} {{ __('common.action_irreversible') }}.</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="deleteSession" wire:loading.attr="disabled" wire:target="deleteSession">{{ __('common.delete') }}</x-button>
            <x-button variant="ghost" wire:click="cancelDeleteSession">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>

    {{-- نموذج المنفذين --}}
    <x-lw-modal :show="$showDeliverersForm" close="closeDeliverersForm" :title="__('courses.manage_deliverers')" maxWidth="xl">
        <form wire:submit="saveDeliverers" class="space-y-4">
            @error('delivererRows')
                <p class="rounded-lg bg-error-soft p-3 text-sm text-error">{{ $message }}</p>
            @enderror

            <div class="space-y-3">
                @foreach ($delivererRows as $index => $row)
                    <div class="rounded-lg border border-line bg-white p-3" wire:key="deliverer-row-{{ $index }}">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <x-select :name="'delivererRows.'.$index.'.type'" wire:model.live="delivererRows.{{ $index }}.type" :label="__('courses.deliverer')">
                                <option value="partner">{{ __('courses.deliverer_type.partner') }}</option>
                                <option value="external">{{ __('courses.deliverer_type.external') }}</option>
                            </x-select>

                            @if ($row['type'] === 'partner')
                                <x-select :name="'delivererRows.'.$index.'.partner_id'" wire:model="delivererRows.{{ $index }}.partner_id" :label="__('courses.select_partner')" :placeholder="__('common.choose')">
                                    @foreach ($partners as $partner)
                                        <option value="{{ $partner->id }}">{{ $partner->display_name_ar }}</option>
                                    @endforeach
                                </x-select>
                            @else
                                <x-select :name="'delivererRows.'.$index.'.instructor_id'" wire:model="delivererRows.{{ $index }}.instructor_id" :label="__('courses.select_instructor')" :placeholder="__('common.choose')">
                                    @foreach ($instructorOptions as $instructor)
                                        <option value="{{ $instructor->id }}">{{ $instructor->name_ar }}</option>
                                    @endforeach
                                </x-select>
                            @endif

                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <x-input :name="'delivererRows.'.$index.'.weight'" type="text" inputmode="decimal" wire:model="delivererRows.{{ $index }}.weight" :label="__('courses.share_weight')" />
                                </div>
                                <button
                                    type="button"
                                    class="flex size-11 shrink-0 items-center justify-center rounded-lg text-muted transition hover:bg-error/10 hover:text-error"
                                    wire:click="removeDelivererRow({{ $index }})"
                                    aria-label="{{ __('common.delete') }}"
                                >
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                                </button>
                            </div>
                        </div>
                        @error('delivererRows.'.$index.'.type')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <x-button type="button" variant="secondary" size="sm" wire:click="addDelivererRow">{{ __('courses.add_deliverer_row') }}</x-button>

            <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row">
                <x-button type="submit" target="saveDeliverers" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                <x-button variant="ghost" type="button" wire:click="closeDeliverersForm">{{ __('common.cancel') }}</x-button>
            </div>
        </form>
    </x-lw-modal>
</div>
