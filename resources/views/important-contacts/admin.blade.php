@extends('layouts.app')

@section('title', 'Manage Important Contacts')

@section('content')
<div x-data='contactsAdmin({ contacts: @json($contacts) })' class="mx-auto max-w-[1400px] space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-primary">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10"><x-icon name="phone" class="h-3.5 w-3.5" /></span>
                Contact directory
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Important contacts</h1>
            <p class="mt-1.5 text-sm leading-6 text-muted-foreground">Keep emergency and key stakeholder details organized and ready when your team needs them.</p>
        </div>
        <button type="button" @click="openCreate()" class="gov-btn gov-btn-primary shrink-0">
            <x-icon name="plus" class="h-4 w-4" />
            Add contact
        </button>
    </div>

    {{-- Directory summary --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="gov-card flex items-center gap-3 p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><x-icon name="user" class="h-5 w-5" /></span>
            <div><p class="text-xs font-medium text-muted-foreground">Active contacts</p><p class="text-xl font-bold tracking-tight" x-text="contacts.length"></p></div>
        </div>
        <div class="gov-card flex items-center gap-3 p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400"><x-icon name="building-2" class="h-5 w-5" /></span>
            <div><p class="text-xs font-medium text-muted-foreground">Organizations</p><p class="text-xl font-bold tracking-tight" x-text="organizationCount"></p></div>
        </div>
        <div class="gov-card flex items-center gap-3 p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-destructive/10 text-destructive"><x-icon name="flag" class="h-5 w-5" /></span>
            <div><p class="text-xs font-medium text-muted-foreground">High priority</p><p class="text-xl font-bold tracking-tight" x-text="highPriorityCount"></p></div>
        </div>
    </div>

    <section class="gov-card overflow-hidden" aria-labelledby="contacts-list-heading">
        <div class="border-b border-border/70 p-4 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 id="contacts-list-heading" class="font-semibold text-foreground">Contact list</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground"><span x-text="getFiltered.length"></span> <span x-text="getFiltered.length === 1 ? 'contact' : 'contacts'"></span> shown</p>
                </div>
                <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row lg:max-w-3xl lg:justify-end">
                    <label class="relative min-w-0 flex-1 lg:max-w-xl">
                        <span class="sr-only">Search contacts</span>
                        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input type="search" x-model="filters.search" placeholder="Search contacts..." class="gov-input h-10 pl-10 pr-10">
                        <button type="button" x-show="filters.search" x-cloak @click="filters.search = ''" aria-label="Clear search" class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground">×</button>
                    </label>
                    <button type="button" @click="deactivateSelected()" class="gov-btn gov-btn-danger h-10 shrink-0" x-show="selected.size > 0" x-cloak>
                        <x-icon name="trash-2" class="h-4 w-4" />
                        Deactivate <span x-text="selected.size"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="sticky top-0 z-10 bg-muted/70 text-left backdrop-blur-sm">
                    <tr>
                        <th class="w-12 px-5 py-3.5">
                            <input type="checkbox" aria-label="Select all visible contacts" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" :checked="selected.size === getFiltered.length && getFiltered.length > 0" @change="toggleSelectAll($event.target.checked)">
                        </th>
                        <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Contact</th>
                        <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Title</th>
                        <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Organization</th>
                        <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Phone</th>
                        <th class="px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Priority</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/70">
                    <template x-if="getFiltered.length === 0">
                        <tr><td colspan="7" class="px-5 py-16 text-center"><div class="mx-auto flex max-w-sm flex-col items-center"><span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"><x-icon name="search" class="h-5 w-5" /></span><p class="font-semibold text-foreground">No contacts found</p><p class="mt-1 text-sm text-muted-foreground">Try another search or add a new contact.</p></div></td></tr>
                    </template>
                    <template x-for="c in getFiltered" :key="c.id">
                        <tr class="group transition-colors hover:bg-primary/[0.035]">
                            <td class="px-5 py-4"><input type="checkbox" :aria-label="`Select ${c.name}`" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" :checked="selected.has(c.id)" @change="toggleSelect(c.id, $event.target.checked)"></td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xs font-bold text-primary" x-text="initials(c.name)"></span><span class="font-semibold text-foreground" x-text="c.name"></span></div>
                            </td>
                            <td class="px-4 py-4 text-muted-foreground" x-text="c.title"></td>
                            <td class="px-4 py-4 text-muted-foreground" x-text="c.organization || '—'"></td>
                            <td class="px-4 py-4"><a :href="'tel:' + c.phone_primary" class="inline-flex items-center gap-1.5 font-medium text-primary hover:underline"><x-icon name="phone" class="h-3.5 w-3.5" />
                                    <span x-text="c.phone_primary"></span>
                                </a>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex min-w-10 justify-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="c.priority < 10 ? 'bg-destructive/10 text-destructive' : 'bg-muted text-muted-foreground'" x-text="c.priority"></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" @click="openEdit(c)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground transition hover:border-primary/30 hover:bg-primary/5 hover:text-primary" :aria-label="`Edit ${c.name}`"><x-icon name="pencil" class="h-4 w-4" /></button>
                                    <button type="button" @click="deactivate(c)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground transition hover:border-destructive/30 hover:bg-destructive/5 hover:text-destructive" :aria-label="`Deactivate ${c.name}`"><x-icon name="trash-2" class="h-4 w-4" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="divide-y divide-border/70 md:hidden">
            <template x-if="getFiltered.length === 0"><div class="px-5 py-12 text-center"><p class="font-semibold">No contacts found</p><p class="mt-1 text-sm text-muted-foreground">Try another search or add a new contact.</p></div></template>
            <template x-for="c in getFiltered" :key="`mobile-${c.id}`">
                <article class="p-4">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" :aria-label="`Select ${c.name}`" class="mt-2 h-4 w-4 rounded border-border text-primary focus:ring-primary" :checked="selected.has(c.id)" @change="toggleSelect(c.id, $event.target.checked)">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xs font-bold text-primary" x-text="initials(c.name)"></span>
                        <div class="min-w-0 flex-1"><h3 class="truncate font-semibold" x-text="c.name"></h3><p class="truncate text-sm text-muted-foreground" x-text="c.title"></p></div>
                        <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground" x-text="c.priority"></span>
                    </div>
                    <div class="ml-7 mt-3 space-y-1.5 pl-10 text-sm text-muted-foreground"><p class="flex items-center gap-2"><x-icon name="building-2" class="h-3.5 w-3.5" /><span x-text="c.organization || 'No organization'"></span></p><a :href="'tel:' + c.phone_primary" class="flex items-center gap-2 font-medium text-primary"><x-icon name="phone" class="h-3.5 w-3.5" /><span x-text="c.phone_primary"></span></a></div>
                    <div class="mt-4 flex justify-end gap-2"><button type="button" @click="openEdit(c)" class="gov-btn gov-btn-outline h-9 px-3 text-xs"><x-icon name="pencil" class="h-3.5 w-3.5" /> Edit</button><button type="button" @click="deactivate(c)" class="gov-btn gov-btn-ghost h-9 px-3 text-xs text-destructive"><x-icon name="trash-2" class="h-3.5 w-3.5" /> Deactivate</button></div>
                </article>
            </template>
        </div>
    </section>

    {{-- Form dialog --}}
    <template x-teleport="body">
    <div x-show="showForm" x-cloak x-transition.opacity.duration.200ms class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-slate-950/60 p-0 backdrop-blur-[2px] sm:p-6" @click.self="showForm = false" @keydown.escape.window="showForm = false" role="dialog" aria-modal="true" aria-labelledby="contact-dialog-title">
        <div x-show="showForm" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0 sm:scale-[.98]" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100" class="my-auto flex max-h-dvh w-full flex-col overflow-hidden bg-card shadow-2xl sm:max-h-[calc(100dvh-3rem)] sm:max-w-xl sm:rounded-2xl">
            <div class="flex shrink-0 items-start justify-between border-b border-border bg-card px-5 py-4 sm:px-6">
                <div><h2 id="contact-dialog-title" class="text-lg font-bold" x-text="editing ? 'Edit contact' : 'Add contact'"></h2><p class="mt-0.5 text-sm text-muted-foreground">Add accurate details for quick access.</p></div>
                <button type="button" @click="showForm = false" aria-label="Close dialog" class="flex h-9 w-9 items-center justify-center rounded-lg text-xl text-muted-foreground transition hover:bg-muted hover:text-foreground">×</button>
            </div>
            <form @submit.prevent="save()" class="flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-5 sm:p-6">
                <div>
                    <label for="contact-name" class="mb-1.5 block text-sm font-medium">Name <span class="text-destructive">*</span></label>
                    <input id="contact-name" type="text" x-model="form.name" required autocomplete="name" class="gov-input" placeholder="Full name">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact-title" class="mb-1.5 block text-sm font-medium">Title <span class="text-destructive">*</span></label>
                        <input id="contact-title" type="text" x-model="form.title" required class="gov-input" placeholder="Role or designation">
                    </div>
                    <div>
                        <label for="contact-organization" class="mb-1.5 block text-sm font-medium">Organization</label>
                        <input id="contact-organization" type="text" x-model="form.organization" class="gov-input" placeholder="Organization name">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="contact-phone" class="mb-1.5 block text-sm font-medium">Primary phone <span class="text-destructive">*</span></label><input id="contact-phone" type="tel" x-model="form.phone_primary" required autocomplete="tel" class="gov-input" placeholder="e.g. 7770000"></div>
                    <div><label for="contact-phone-secondary" class="mb-1.5 block text-sm font-medium">Secondary phone</label><input id="contact-phone-secondary" type="tel" x-model="form.phone_secondary" class="gov-input" placeholder="Optional"></div>
                </div>
                <div>
                    <label for="contact-email" class="mb-1.5 block text-sm font-medium">Email</label>
                    <input id="contact-email" type="email" x-model="form.email" autocomplete="email" class="gov-input" placeholder="name@organization.com">
                </div>
                <div>
                    <label for="contact-priority" class="mb-1.5 block text-sm font-medium">Priority</label>
                    <input id="contact-priority" type="number" x-model="form.priority" min="1" class="gov-input">
                    <p class="mt-1.5 text-xs text-muted-foreground">Lower numbers appear first; 1 is the highest priority.</p>
                </div>
                <div>
                    <label for="contact-notes" class="mb-1.5 block text-sm font-medium">Notes</label>
                    <textarea id="contact-notes" x-model="form.notes" rows="3" class="gov-input resize-y" placeholder="Optional context or availability"></textarea>
                </div>
                </div>
                <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-border bg-card px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" @click="showForm = false" class="gov-btn gov-btn-outline">Cancel</button>
                    <button type="submit" class="gov-btn gov-btn-primary" :disabled="saving"><x-icon name="loader-2" class="h-4 w-4 animate-spin" x-show="saving" x-cloak /><span x-text="saving ? 'Saving…' : (editing ? 'Save changes' : 'Add contact')"></span></button>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>
@endsection

