<div
    x-data="commentsActivity(task.id, { profiles: profiles, assignedTo: task.assigned_to, assignedBy: task.assigned_by, userRole: userRole, currentUserId: currentUserId })"
    x-init="init()"
    class="w-full"
>
    <template x-if="loading">
        <div class="flex items-center justify-center py-8">
            <x-icon name="loader-2" class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>
    </template>

    <template x-if="!loading">
        <div>
            {{-- Tabs --}}
            <div class="flex w-full rounded-lg bg-muted/50 p-1">
                <button type="button" @click="tab = 'comments'" class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-bold transition-colors" :class="tab === 'comments' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                    <x-icon name="message-square" class="h-4 w-4" />
                    <span x-text="'Comments (' + comments.length + ')'"></span>
                </button>
                <button type="button" @click="tab = 'activity'" class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-bold transition-colors" :class="tab === 'activity' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                    <x-icon name="history" class="h-4 w-4" />
                    <span x-text="'Activity (' + activities.length + ')'"></span>
                </button>
            </div>

            {{-- Comments tab --}}
            <div x-show="tab === 'comments'" class="mt-4 space-y-4">
                <template x-if="canComment">
                    <div class="flex gap-3">
                        <div class="h-8 w-8 shrink-0 overflow-hidden rounded-full bg-primary/10 text-xs text-primary">
                            <template x-if="profileOf(currentUserId)?.avatar_url"><img :src="profileOf(currentUserId).avatar_url" alt="Your avatar" class="h-full w-full object-cover"></template>
                            <template x-if="!profileOf(currentUserId)?.avatar_url"><span class="flex h-full w-full items-center justify-center" x-text="initials(profileOf(currentUserId))"></span></template>
                        </div>
                        <div class="flex-1 space-y-2">
                            <textarea x-model="newComment" @keydown.meta.enter="submitComment()" @keydown.ctrl.enter="submitComment()" placeholder="Add a comment... (Ctrl+Enter to send)" class="textarea min-h-[80px] resize-none"></textarea>
                            <div class="flex justify-end">
                                <button type="button" @click="submitComment()" :disabled="!newComment.trim() || submitting" class="btn h-9 rounded-md px-3 text-sm">
                                    <x-icon name="loader-2" x-show="submitting" class="mr-2 h-4 w-4 animate-spin" />
                                    <x-icon name="send" x-show="!submitting" class="mr-2 h-4 w-4" />
                                    Post Comment
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="!canComment">
                    <div class="rounded-lg border bg-muted/30 py-4 text-center text-sm text-muted-foreground">
                        Only the assignee can add comments to this task.
                    </div>
                </template>

                <div class="h-[300px] overflow-y-auto pr-4">
                    <template x-if="comments.length === 0">
                        <div class="py-8 text-center text-muted-foreground">
                            <x-icon name="message-square" class="mx-auto mb-2 h-8 w-8 opacity-50" />
                            <p>No comments yet</p>
                            <p class="text-sm">Be the first to add a comment</p>
                        </div>
                    </template>
                    <div x-show="comments.length > 0" class="space-y-4">
                        <template x-for="comment in comments" :key="comment.id">
                            <div class="group flex gap-3">
                                <div class="h-8 w-8 shrink-0 overflow-hidden rounded-full bg-muted text-xs">
                                    <template x-if="profileOf(comment.user_id)?.avatar_url"><img :src="profileOf(comment.user_id).avatar_url" alt="Comment author avatar" class="h-full w-full object-cover"></template>
                                    <template x-if="!profileOf(comment.user_id)?.avatar_url"><span class="flex h-full w-full items-center justify-center" x-text="initials(profileOf(comment.user_id))"></span></template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium" x-text="profileName(profileOf(comment.user_id))"></span>
                                        <span class="text-xs text-muted-foreground" x-text="timeAgo(comment.created_at)"></span>
                                        <template x-if="comment.user_id === currentUserId">
                                            <button type="button" @click="deleteComment(comment.id)" class="ml-1 h-6 w-6 rounded-md p-0 text-muted-foreground opacity-0 transition-opacity hover:text-destructive group-hover:opacity-100 focus:opacity-100" aria-label="Delete comment">
                                                <x-icon name="trash-2" class="h-3 w-3" />
                                            </button>
                                        </template>
                                    </div>
                                    <p class="mt-1 whitespace-pre-wrap break-words text-sm" x-text="comment.content"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Activity tab --}}
            <div x-show="tab === 'activity'" class="mt-4">
                <div class="h-[350px] overflow-y-auto pr-4">
                    <template x-if="activities.length === 0">
                        <div class="py-8 text-center text-muted-foreground">
                            <x-icon name="history" class="mx-auto mb-2 h-8 w-8 opacity-50" />
                            <p>No activity yet</p>
                        </div>
                    </template>
                    <div x-show="activities.length > 0" class="space-y-3">
                        <template x-for="activity in activities" :key="activity.id">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 h-6 w-6 shrink-0 overflow-hidden rounded-full bg-muted text-[10px]">
                                    <template x-if="profileOf(activity.user_id)?.avatar_url"><img :src="profileOf(activity.user_id).avatar_url" alt="Activity author avatar" class="h-full w-full object-cover"></template>
                                    <template x-if="!profileOf(activity.user_id)?.avatar_url"><span class="flex h-full w-full items-center justify-center" x-text="initials(profileOf(activity.user_id))"></span></template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm leading-relaxed" x-html="activityMessage(activity)"></p>
                                    <p class="mt-0.5 text-xs text-muted-foreground" x-text="timeAgo(activity.created_at)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
