{{-- Equipment checkboxes; $lookupData must include equipmentTypes and equipment --}}
@foreach($lookupData['equipmentTypes'] as $equipmentType)
    @if($equipmentType->equipments->count() > 0)
        <details class="equipment-type-details">
            <summary class="equipment-type-toggle">
                <span>{{ $equipmentType->name }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="equipment-type-icon">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </summary>
            <div class="equipment-type-content">
                <div class="flex flex-wrap gap-2">
                    @foreach($equipmentType->equipments as $equipment)
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                            <input
                                type="checkbox"
                                name="equipment_ids[]"
                                value="{{ $equipment->id }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                onchange="handleEquipmentChange(this, {{ $equipment->id }}, '{{ addslashes($equipment->name) }}')"
                            >
                            <span>{{ $equipment->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </details>
    @endif
@endforeach

@php
    $equipmentWithoutType = $lookupData['equipment']->filter(function ($equip) {
        return !$equip->equipment_type_id;
    });
@endphp
@if($equipmentWithoutType->count() > 0)
    <details class="equipment-type-details">
        <summary class="equipment-type-toggle">
            <span>{{ __('messages.pages.sell_your_car.equipment_other') }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="equipment-type-icon">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </summary>
        <div class="equipment-type-content">
            <div class="flex flex-wrap gap-2">
                @foreach($equipmentWithoutType as $equipment)
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                        <input
                            type="checkbox"
                            name="equipment_ids[]"
                            value="{{ $equipment->id }}"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            onchange="handleEquipmentChange(this, {{ $equipment->id }}, '{{ addslashes($equipment->name) }}')"
                        >
                        <span>{{ $equipment->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </details>
@endif
