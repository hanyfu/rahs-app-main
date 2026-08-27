@props([
    'profile' => null,
    'islandName' => '',
    'atollName' => '',
    'hospitalName' => '',
    'canEdit' => false,
    'compact' => false,
    'dashboardStyle' => false,
])

@php
    $profilePayload = base64_encode(json_encode([
        'profile' => $profile ?? (object) [],
        'options' => [
            'islandName' => $islandName,
            'atollName' => $atollName,
            'hospitalName' => $hospitalName,
            'hospitalContactId' => $hospitalContactId ?? null,
            'canEdit' => $canEdit,
            'compact' => $compact,
            'dashboardStyle' => $dashboardStyle,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
@endphp

<div
    x-data='hospitalProfilePayload(@json($profilePayload))'
    @hospital-edit.window="enterEdit()"
    class="overflow-hidden relative"
    :class="!compact ? (dashboardStyle ? 'bg-card rounded-2xl border border-border shadow-sm' : 'bg-background/95 backdrop-blur-2xl rounded-2xl sm:rounded-3xl border border-white/10 shadow-2xl') : ''"
>
    {{-- Header --}}
    <div class="relative overflow-hidden" :class="!compact ? (dashboardStyle ? 'bg-muted/20 border-b border-border/60' : 'bg-gradient-to-br from-primary via-primary/90 to-primary/80') : ''">
        <div :class="compact ? 'p-0' : 'relative p-4 sm:p-6 md:p-8'">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1" :class="!compact ? 'space-y-2 sm:space-y-3' : ''">
                    <div class="flex items-center gap-2">
                        <div class="shrink-0" :class="compact ? 'p-1.5 rounded-lg bg-primary/10 text-primary' : 'p-2 sm:p-2.5 rounded-xl sm:rounded-2xl bg-white/15 backdrop-blur border border-white/10'">
                            <x-icon name="hospital" class="h-5 w-5 text-primary sm:h-6 sm:w-6" />
                        </div>
                        <div class="min-w-0">
                            @if ($hospitalName)
                                <h2 class="truncate font-bold tracking-tight" :class="compact ? 'text-lg sm:text-xl text-foreground' : (dashboardStyle ? 'text-base sm:text-xl md:text-2xl text-foreground' : 'text-base sm:text-xl md:text-2xl text-white')">{{ $hospitalName }}</h2>
                            @endif
                            <div class="flex flex-wrap items-center gap-1" :class="compact ? 'text-xs text-muted-foreground' : (dashboardStyle ? 'text-muted-foreground text-xs sm:text-sm' : 'text-white/80 text-xs sm:text-sm')">
                                <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0" />
                                <span class="truncate">{{ $islandName }}</span>
                                @if ($atollName)
                                    <x-icon name="chevron-right" class="h-3 w-3 shrink-0" />
                                    <span class="truncate">{{ $atollName }} Atoll</span>
                                @endif
                            </div>
                            <template x-if="lastUpdatedLabel()">
                                <div class="mt-1 flex items-center gap-1.5" :class="compact ? 'text-[10px] text-muted-foreground' : (dashboardStyle ? 'text-xs text-muted-foreground' : 'text-white/70 text-xs')">
                                    <x-icon name="clock" class="h-3 w-3 shrink-0" />
                                    <span x-text="lastUpdatedLabel()"></span>
                                    <span x-show="isStale()" class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">Needs update</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1.5">
                    @if ($dashboardStyle)
                        <button type="button" @click="toggleCompact()" :title="compact ? 'Switch to expanded view' : 'Switch to compact view'" class="inline-flex items-center font-semibold rounded-full h-8 px-3 text-xs text-muted-foreground hover:bg-muted hover:text-foreground">
                            <x-icon name="layout-grid" class="w-3.5 h-3.5 mr-1.5" x-show="compact" />
                            <x-icon name="list" class="w-3.5 h-3.5 mr-1.5" x-show="!compact" x-cloak />
                            <span x-text="compact ? 'Expand' : 'Compact'"></span>
                        </button>
                    @endif
                    @if ($canEdit)
                        <template x-if="!isEditing">
                            <button type="button" @click="enterEdit()" class="inline-flex items-center font-semibold" :class="compact ? 'h-7 px-2 text-xs rounded-md text-muted-foreground hover:text-foreground' : (dashboardStyle ? 'rounded-full h-8 px-3 text-xs text-foreground hover:bg-muted' : 'rounded-full h-8 px-3 text-xs bg-white/20 hover:bg-white/30 text-white border-white/10')">
                                <x-icon name="pencil" class="mr-1.5 h-3.5 w-3.5" />
                                Edit
                            </button>
                        </template>
                        <template x-if="isEditing">
                            <button type="button" @click="cancelEdit()" class="inline-flex items-center font-semibold" :class="compact ? 'h-7 px-2 text-xs rounded-md text-muted-foreground hover:text-foreground' : (dashboardStyle ? 'rounded-full h-8 px-3 text-xs text-foreground hover:bg-muted' : 'rounded-full h-8 px-3 text-xs bg-white/20 hover:bg-white/30 text-white border-white/10')">
                                <x-icon name="x" class="mr-1.5 h-3.5 w-3.5" />
                                Cancel
                            </button>
                        </template>
                    @endif
                    <template x-if="profile.grade">
                        <div class="rounded-full border font-bold" :class="compact ? 'px-2.5 py-0.5 text-[11px] border-border/50 bg-muted/30 text-muted-foreground' : 'px-2 sm:px-3 py-1 sm:py-1.5 bg-white/15 backdrop-blur border-white/10 text-white text-xs sm:text-sm'" x-text="'Grade ' + profile.grade"></div>
                    </template>
                </div>
            </div>

            {{-- Stat tiles --}}
            <div class="grid grid-cols-2 gap-2 sm:gap-3" :class="compact ? 'mt-3' : 'mt-4 sm:mt-6'">
                <div class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-xl sm:rounded-2xl border" :class="compact ? 'border-border/50 bg-muted/20' : 'bg-white/10 backdrop-blur border-white/5'">
                    <div class="shrink-0 p-1.5 sm:p-2 rounded-lg sm:rounded-xl bg-primary/10">
                        <x-icon name="bed-double" class="text-primary w-3.5 h-3.5 sm:w-4 sm:h-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm sm:text-lg font-bold tabular-nums truncate text-foreground" x-text="profile.no_of_beds || 0"></p>
                        <p class="text-[9px] sm:text-[10px] font-medium uppercase tracking-wider truncate text-muted-foreground">Total Beds</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-xl sm:rounded-2xl border" :class="compact ? 'border-border/50 bg-muted/20' : 'bg-white/10 backdrop-blur border-white/5'">
                    <div class="shrink-0 p-1.5 sm:p-2 rounded-lg sm:rounded-xl bg-primary/10">
                        <x-icon name="users" class="text-primary w-3.5 h-3.5 sm:w-4 sm:h-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm sm:text-lg font-bold tabular-nums truncate text-foreground" x-text="totalStaff"></p>
                        <p class="text-[9px] sm:text-[10px] font-medium uppercase tracking-wider truncate text-muted-foreground">Staff Count</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-xl sm:rounded-2xl border" :class="compact ? 'border-border/50 bg-muted/20' : 'bg-white/10 backdrop-blur border-white/5'">
                    <div class="shrink-0 p-1.5 sm:p-2 rounded-lg sm:rounded-xl bg-primary/10">
                        <x-icon name="activity" class="text-primary w-3.5 h-3.5 sm:w-4 sm:h-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm sm:text-lg font-bold tabular-nums truncate text-foreground" x-text="activeServices + '/8'"></p>
                        <p class="text-[9px] sm:text-[10px] font-medium uppercase tracking-wider truncate text-muted-foreground">Services</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-xl sm:rounded-2xl border" :class="compact ? 'border-border/50 bg-muted/20' : 'bg-white/10 backdrop-blur border-white/5'">
                    <div class="shrink-0 p-1.5 sm:p-2 rounded-lg sm:rounded-xl bg-primary/10">
                        <x-icon name="users" class="text-primary w-3.5 h-3.5 sm:w-4 sm:h-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm sm:text-lg font-bold tabular-nums truncate text-foreground" x-text="Number(profile.population || 0).toLocaleString()"></p>
                        <p class="text-[9px] sm:text-[10px] font-medium uppercase tracking-wider truncate text-muted-foreground">Population</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-show="!dashboardStyle" :class="compact ? 'mb-2' : 'bg-muted/20'">
        <div class="flex w-full justify-start overflow-x-auto no-scrollbar rounded-none border-b border-border/50 bg-transparent p-0" :class="!compact ? 'px-2 sm:px-6' : ''">
            <template x-for="s in [{ id: 'overview', label: 'Overview', icon: 'activity' }, { id: 'staff', label: 'Staff', icon: 'users' }, { id: 'services', label: 'Services', icon: 'stethoscope' }, { id: 'status', label: 'Status', icon: 'shield' }]" :key="s.id">
                <button
                    type="button"
                    @click="activeSection = s.id"
                    class="shrink-0 rounded-none border-b-2 border-transparent bg-transparent transition-all duration-300 text-muted-foreground hover:text-foreground inline-flex items-center"
                    :class="(compact ? 'px-3 py-2 text-xs gap-1.5' : 'px-3 sm:px-4 py-3 sm:py-3.5 text-xs sm:text-sm gap-1.5 sm:gap-2') + (activeSection === s.id ? ' !border-primary !text-primary' : '')"
                >
                    <x-icon name="activity" x-show="s.icon === 'activity'" class="h-4 w-4 transition-transform duration-300" />
                    <x-icon name="users" x-show="s.icon === 'users'" class="h-4 w-4 transition-transform duration-300" />
                    <x-icon name="stethoscope" x-show="s.icon === 'stethoscope'" class="h-4 w-4 transition-transform duration-300" />
                    <x-icon name="shield" x-show="s.icon === 'shield'" class="h-4 w-4 transition-transform duration-300" />
                    <span x-text="s.label"></span>
                </button>
            </template>
        </div>
    </div>

    <div :class="!compact && !dashboardStyle ? 'h-[400px] sm:h-[480px] overflow-y-auto' : ''">
        <div :class="compact ? 'space-y-4' : 'p-4 sm:p-6 md:p-8 space-y-6 sm:space-y-8'">

            {{-- Overview section --}}
            <div x-show="dashboardStyle || activeSection === 'overview'">
                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <template x-for="m in [
                        { label: 'Beds', value: 'no_of_beds', icon: 'bed-double', iconColor: 'text-primary', field: 'no_of_beds' },
                        { label: 'Population', value: 'population', icon: 'users', iconColor: 'text-emerald-500', field: 'population' },
                        { label: 'Outpatient/Day', value: 'avg_outpatient_per_day', icon: 'heart', iconColor: 'text-violet-500', field: 'avg_outpatient_per_day' },
                        { label: 'Inpatient/Month', value: 'avg_inpatient_per_month', icon: 'activity', iconColor: 'text-amber-500', field: 'avg_inpatient_per_month' },
                    ]" :key="m.field">
                        <div class="relative overflow-hidden rounded-xl sm:rounded-2xl p-3 sm:p-5 border {{ $dashboardStyle ? 'bg-card border-border/70' : 'bg-card border-border/70' }}">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl flex items-center justify-center mb-2 sm:mb-3 bg-primary/10">
                                <x-icon name="bed-double" x-show="m.icon === 'bed-double'" class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
                                <x-icon name="users" x-show="m.icon === 'users'" class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-500" />
                                <x-icon name="heart" x-show="m.icon === 'heart'" class="w-4 h-4 sm:w-5 sm:h-5 text-violet-500" />
                                <x-icon name="activity" x-show="m.icon === 'activity'" class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500" />
                            </div>
                            <template x-if="isEditing">
                                <div>
                                    <input type="number" min="0" x-model.number="profile[m.field]" class="input h-9 text-base sm:text-lg font-bold bg-background/50 border-border/50 mb-1" />
                                    <p class="text-[10px] sm:text-xs font-medium text-muted-foreground" x-text="m.label"></p>
                                </div>
                            </template>
                            <template x-if="!isEditing">
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold tracking-tight" x-text="m.field === 'population' ? Number(profile[m.field] || 0).toLocaleString() : (profile[m.field] || 0)"></p>
                                    <p class="text-[10px] sm:text-xs font-medium text-muted-foreground mt-0.5" x-text="m.label"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <template x-if="isEditing">
                    <div class="rounded-2xl border border-border/50 bg-muted/20 p-5 space-y-4 mt-4">
                        <div class="flex items-center gap-2">
                            <x-icon name="gauge" class="w-4 h-4 text-primary" />
                            <h3 class="text-sm font-semibold">Quick Edit</h3>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Grade</label>
                            <input type="text" x-model="profile.grade" class="input h-9 text-sm font-medium bg-background/50" placeholder="e.g. A, B, C" />
                        </div>
                    </div>
                </template>

                <div class="mt-6">
                    <h3 class="text-sm font-semibold mb-4 flex items-center gap-2">
                        <x-icon name="users" class="w-4 h-4 text-primary" />
                        Staff Summary
                    </h3>
                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        <div class="rounded-xl sm:rounded-2xl p-3 sm:p-5 border bg-card border-border/70">
                            <x-icon name="stethoscope" class="w-4 h-4 sm:w-5 sm:h-5 mb-2 sm:mb-3 text-blue-500" />
                            <p class="text-lg sm:text-2xl font-bold tracking-tight" x-text="totalMedicalStaff"></p>
                            <p class="text-[10px] sm:text-xs font-medium text-muted-foreground mt-0.5">Medical</p>
                        </div>
                        <div class="rounded-xl sm:rounded-2xl p-3 sm:p-5 border bg-card border-border/70">
                            <x-icon name="heart" class="w-4 h-4 sm:w-5 sm:h-5 mb-2 sm:mb-3 text-pink-500" />
                            <p class="text-lg sm:text-2xl font-bold tracking-tight" x-text="totalNursingStaff"></p>
                            <p class="text-[10px] sm:text-xs font-medium text-muted-foreground mt-0.5">Nursing</p>
                        </div>
                        <div class="rounded-xl sm:rounded-2xl p-3 sm:p-5 border bg-card border-border/70">
                            <x-icon name="user-cog" class="w-4 h-4 sm:w-5 sm:h-5 mb-2 sm:mb-3 text-violet-500" />
                            <p class="text-lg sm:text-2xl font-bold tracking-tight" x-text="totalAdminStaff"></p>
                            <p class="text-[10px] sm:text-xs font-medium text-muted-foreground mt-0.5">Admin &amp; Support</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Staff section --}}
            <div x-show="dashboardStyle || activeSection === 'staff'" class="grid gap-5 border-t border-border/60 pt-6 sm:grid-cols-2 sm:pt-8">
                <div class="rounded-2xl border border-border/50 bg-card p-5">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                            <x-icon name="stethoscope" class="w-5 h-5 text-blue-500" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-sm">Medical Staff</h3>
                            <p class="text-xs text-muted-foreground" x-text="totalMedicalStaff + ' personnel'"></p>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <template x-for="row in [
                            { label: 'Physiotherapy', field: 'staff_physiotherapy' }, { label: 'Dermatology', field: 'staff_dermatology' },
                            { label: 'Orthopaedics', field: 'staff_ortho' }, { label: 'Medicine', field: 'staff_medicine' },
                            { label: 'Surgeon', field: 'staff_surgeon' }, { label: 'Gynaecology', field: 'staff_gynaecology' },
                            { label: 'Paediatrician', field: 'staff_paediatrician' }, { label: 'ENT', field: 'staff_ent' },
                            { label: 'Dental', field: 'staff_dental' }, { label: 'Ophthalmology', field: 'staff_ophthalmology' },
                            { label: 'Psychology', field: 'staff_psychology' }, { label: 'Radiology', field: 'staff_radiology' },
                            { label: 'Anesthesiologist', field: 'staff_anesthesiologist' }, { label: 'Medical Officer', field: 'staff_medical_officer' },
                            { label: 'Psychiatrist', field: 'staff_psychiatrist' },
                        ]" :key="row.field">
                            <div class="group flex items-center gap-3 py-2.5 border-b border-border/30 last:border-0">
                                <div class="w-8 h-8 rounded-lg bg-muted/30 flex items-center justify-center transition-colors group-hover:bg-primary/10">
                                    <x-icon name="stethoscope" class="w-4 h-4 text-muted-foreground transition-colors group-hover:text-primary" />
                                </div>
                                <span class="flex-1 text-sm font-medium" x-text="row.label"></span>
                                <template x-if="isEditing">
                                    <input type="number" min="0" x-model.number="profile[row.field]" class="input w-20 h-8 text-center text-sm font-semibold tabular-nums bg-background/50" />
                                </template>
                                <template x-if="!isEditing">
                                    <span class="font-semibold tabular-nums text-sm w-20 text-center" x-text="profile[row.field] || 0"></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="rounded-2xl border border-border/50 bg-card p-5">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-pink-500/10 flex items-center justify-center">
                                <x-icon name="heart" class="w-5 h-5 text-pink-500" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm">Nursing Staff</h3>
                                <p class="text-xs text-muted-foreground" x-text="totalNursingStaff + ' personnel'"></p>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <template x-for="row in [
                                { label: 'Clinical Nurses', field: 'nurses_clinical' }, { label: 'Senior Registered', field: 'nurses_senior_registered' },
                                { label: 'Registered', field: 'nurses_registered' }, { label: 'Enrolled', field: 'nurses_enrolled' },
                            ]" :key="row.field">
                                <div class="group flex items-center gap-3 py-2.5 border-b border-border/30 last:border-0">
                                    <div class="w-8 h-8 rounded-lg bg-muted/30 flex items-center justify-center transition-colors group-hover:bg-pink-500/10">
                                        <x-icon name="heart" class="w-4 h-4 text-muted-foreground transition-colors group-hover:text-pink-500" />
                                    </div>
                                    <span class="flex-1 text-sm font-medium" x-text="row.label"></span>
                                    <template x-if="isEditing">
                                        <input type="number" min="0" x-model.number="profile[row.field]" class="input w-20 h-8 text-center text-sm font-semibold tabular-nums bg-background/50" />
                                    </template>
                                    <template x-if="!isEditing">
                                        <span class="font-semibold tabular-nums text-sm w-20 text-center" x-text="profile[row.field] || 0"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border/50 bg-card p-5">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-violet-500/10 flex items-center justify-center">
                                <x-icon name="user-cog" class="w-5 h-5 text-violet-500" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm">Admin &amp; Support</h3>
                                <p class="text-xs text-muted-foreground" x-text="totalAdminStaff + ' personnel'"></p>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <template x-for="row in [
                                { label: 'Senior Admin', field: 'admin_officers_senior' }, { label: 'Admin Officers', field: 'admin_officers' },
                                { label: 'Customer Service', field: 'customer_service' }, { label: 'Drivers', field: 'drivers' },
                                { label: 'Lab Technicians', field: 'lab_tech' }, { label: 'Other Staff', field: 'other_staffs' },
                            ]" :key="row.field">
                                <div class="group flex items-center gap-3 py-2.5 border-b border-border/30 last:border-0">
                                    <div class="w-8 h-8 rounded-lg bg-muted/30 flex items-center justify-center transition-colors group-hover:bg-violet-500/10">
                                        <x-icon name="user-cog" class="w-4 h-4 text-muted-foreground transition-colors group-hover:text-violet-500" />
                                    </div>
                                    <span class="flex-1 text-sm font-medium" x-text="row.label"></span>
                                    <template x-if="isEditing">
                                        <input type="number" min="0" x-model.number="profile[row.field]" class="input w-20 h-8 text-center text-sm font-semibold tabular-nums bg-background/50" />
                                    </template>
                                    <template x-if="!isEditing">
                                        <span class="font-semibold tabular-nums text-sm w-20 text-center" x-text="profile[row.field] || 0"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Services section --}}
            <div x-show="dashboardStyle || activeSection === 'services'" class="space-y-6 border-t border-border/60 pt-6 sm:pt-8">
                <div class="rounded-2xl border border-border/50 p-5 {{ $dashboardStyle ? 'bg-card' : 'bg-card' }}">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center">
                            <x-icon name="ambulance" class="w-5 h-5 text-red-500" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Ambulance Fleet</h3>
                            <p class="text-xs text-muted-foreground">Vehicle availability status</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-4 rounded-xl bg-background/60 border border-border/50">
                            <p class="text-xs font-medium text-muted-foreground mb-1">Total</p>
                            <p class="text-3xl font-bold" x-text="profile.ambulance_total || 0"></p>
                            <template x-if="isEditing">
                                <input type="number" min="0" x-model.number="profile.ambulance_total" class="input mt-2 h-8 bg-background/50" />
                            </template>
                        </div>
                        <div class="p-4 rounded-xl bg-green-500/5 border border-green-500/20">
                            <p class="text-xs font-medium text-green-600 mb-1">Running</p>
                            <p class="text-3xl font-bold text-green-600" x-text="profile.ambulance_running_condition || 0"></p>
                            <template x-if="isEditing">
                                <input type="number" min="0" x-model.number="profile.ambulance_running_condition" class="input mt-2 h-8 bg-background/50" />
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold mb-4 flex items-center gap-2">
                        <x-icon name="circle-dot" class="w-4 h-4 text-primary" />
                        Available Services
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="s in [
                            { label: 'Operation Theatre', field: 'operation_theatre_service', icon: 'scissors' },
                            { label: 'Emergency Room', field: 'emergency_room_service', icon: 'heart' },
                            { label: 'Radiology', field: 'radiology_service', icon: 'microscope' },
                            { label: 'Public Health', field: 'public_health_unit_service', icon: 'shield' },
                            { label: 'Sterilization', field: 'sterilization_service', icon: 'syringe' },
                            { label: 'Lab Service', field: 'lab_service_available', icon: 'flask-conical' },
                            { label: 'POCT', field: 'poct_available', icon: 'thermometer' },
                            { label: 'Launch Boat', field: 'launch_boat_service', icon: 'ship' },
                        ]" :key="s.field">
                            <button
                                type="button"
                                :disabled="!isEditing"
                                @click="isEditing && updateField(s.field, !profile[s.field])"
                                class="flex flex-col items-center gap-1.5 sm:gap-2 p-2 sm:p-3 md:p-4 rounded-xl transition-all duration-300"
                                :class="(profile[s.field] ? 'bg-primary/10 border-2 border-primary/30 text-primary shadow-sm' : 'bg-muted/30 border-2 border-transparent text-muted-foreground hover:border-muted-foreground/20 hover:bg-muted/50') + (isEditing ? ' cursor-pointer hover:scale-105 active:scale-95' : ' cursor-default')"
                            >
                                <x-icon name="scissors" x-show="s.icon === 'scissors'" class="h-5 w-5" />
                                <x-icon name="heart" x-show="s.icon === 'heart'" class="h-5 w-5" />
                                <x-icon name="microscope" x-show="s.icon === 'microscope'" class="h-5 w-5" />
                                <x-icon name="shield" x-show="s.icon === 'shield'" class="h-5 w-5" />
                                <x-icon name="syringe" x-show="s.icon === 'syringe'" class="h-5 w-5" />
                                <x-icon name="flask-conical" x-show="s.icon === 'flask-conical'" class="h-5 w-5" />
                                <x-icon name="thermometer" x-show="s.icon === 'thermometer'" class="h-5 w-5" />
                                <x-icon name="ship" x-show="s.icon === 'ship'" class="h-5 w-5" />
                                <span class="text-[9px] sm:text-[10px] font-semibold text-center leading-tight" x-text="s.label"></span>
                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full transition-colors" :class="profile[s.field] ? 'bg-primary' : 'bg-muted-foreground/20'"></div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Status section --}}
            <div x-show="dashboardStyle || activeSection === 'status'" class="space-y-6 border-t border-border/60 pt-6 sm:pt-8">
                <div class="grid sm:grid-cols-2 gap-4">
                    <template x-for="s in [
                        { label: 'Medical Consumables', field: 'medical_consumables_status' },
                        { label: 'Laboratory Reagents', field: 'laboratory_reagents_status' },
                        { label: 'Life-Saving Drugs', field: 'life_saving_drugs_status' },
                        { label: 'STO Pharmacy', field: 'sto_pharmacy_status' },
                        { label: 'Staff Status', field: 'staff_status' },
                        { label: 'Building Status', field: 'building_status' },
                    ]" :key="s.field">
                        <div class="rounded-2xl border p-5" :class="statusMeta(profile[s.field]).bg + ' ' + statusMeta(profile[s.field]).border">
                            <div class="flex items-center gap-2 mb-3">
                                <x-icon name="check-circle-2" x-show="statusMeta(profile[s.field]).icon === 'check-circle-2'" class="h-4 w-4 text-emerald-600" />
                                <x-icon name="alert-circle" x-show="statusMeta(profile[s.field]).icon === 'alert-circle'" class="h-4 w-4 text-amber-600" />
                                <x-icon name="x-circle" x-show="statusMeta(profile[s.field]).icon === 'x-circle'" class="h-4 w-4 text-destructive" />
                                <x-icon name="circle-dot" x-show="statusMeta(profile[s.field]).icon === 'circle-dot'" class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-semibold" x-text="s.label"></span>
                            </div>
                            <template x-if="isEditing">
                                <select x-model="profile[s.field]" class="input h-9 rounded-xl border border-border/50 bg-background/50 px-3 text-sm font-medium">
                                    <option value="">Select status</option>
                                    <template x-for="o in statusOptions" :key="o">
                                        <option :value="o" x-text="o"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="!isEditing">
                                <p class="font-semibold" :class="statusMeta(profile[s.field]).color" x-text="profile[s.field] || 'Not specified'"></p>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="rounded-2xl border border-border/50 bg-muted/20 p-5 space-y-4">
                    <div class="space-y-2">
                        <h4 class="text-sm font-semibold flex items-center gap-2">
                            <x-icon name="building-2" class="w-4 h-4 text-primary" />
                            Project Information
                        </h4>
                        <template x-if="isEditing">
                            <textarea x-model="profile.project_information" placeholder="Enter project details..." rows="3" class="textarea resize-none rounded-xl bg-background/50 border-border/50"></textarea>
                        </template>
                        <template x-if="!isEditing">
                            <p class="text-sm text-muted-foreground" x-text="profile.project_information || 'No project information available.'"></p>
                        </template>
                    </div>
                    <div class="space-y-2">
                        <h4 class="text-sm font-semibold flex items-center gap-2">
                            <x-icon name="building-2" class="w-4 h-4 text-primary" />
                            Other Information
                        </h4>
                        <template x-if="isEditing">
                            <textarea x-model="profile.other_information" placeholder="Enter any other relevant information..." rows="3" class="textarea resize-none rounded-xl bg-background/50 border-border/50"></textarea>
                        </template>
                        <template x-if="!isEditing">
                            <p class="text-sm text-muted-foreground" x-text="profile.other_information || 'No additional information available.'"></p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Save bar --}}
    <template x-if="isEditing">
        <div class="flex justify-end gap-2 border-t" :class="compact ? 'border-border/30 pt-3 mt-2' : 'border-border/50 bg-muted/10 p-4 sm:p-6 gap-3'">
            <button type="button" @click="cancelEdit()" class="btn btn-outline" :class="compact ? 'h-8 px-3 text-xs rounded-md font-semibold' : 'rounded-xl h-10 px-6 font-semibold'">
                <x-icon name="x" class="mr-1.5 h-4 w-4" />
                Cancel
            </button>
            <button type="button" @click="save()" :disabled="saving" class="btn" :class="compact ? 'h-8 px-3 text-xs rounded-md font-semibold' : 'rounded-xl h-10 px-6 font-semibold shadow-lg shadow-primary/20'">
                <template x-if="saving">
                    <x-icon name="loader-2" class="mr-1.5 h-4 w-4 animate-spin" />
                </template>
                <template x-if="!saving">
                    <x-icon name="save" class="mr-1.5 h-4 w-4" />
                </template>
                <span x-text="saving ? 'Saving...' : 'Save Profile'"></span>
            </button>
        </div>
    </template>
</div>
