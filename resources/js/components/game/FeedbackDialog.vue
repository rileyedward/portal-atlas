<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFeedback } from '@/composables/useFeedback';
import { http, HttpError } from '@/lib/http';
import { store } from '@/routes/api/reports';

const page = usePage();
const { state, closeFeedback } = useFeedback();

const signedIn = computed(() => page.props.auth.user !== null);
const subject = computed(() => state.options.subject ?? null);

const types = computed(() =>
    (page.props.feedbackTypes ?? []).filter((t) =>
        subject.value ? t.scope !== 'general' : t.scope !== 'subject',
    ),
);

const type = ref('');
const message = ref('');
const email = ref('');
const website = ref('');
const errors = ref<Record<string, string[]>>({});
const sending = ref(false);

watch(
    () => state.open,
    (open) => {
        if (!open) {
            return;
        }

        const preset = state.options.type;
        type.value =
            preset && types.value.some((t) => t.value === preset)
                ? preset
                : state.options.suggested
                  ? 'incorrect_location'
                  : '';
        message.value = state.options.message ?? '';
        errors.value = {};
    },
);

const title = computed(() =>
    subject.value
        ? `Something off with “${subject.value.name}”?`
        : 'Send feedback',
);

async function submit(): Promise<void> {
    sending.value = true;
    errors.value = {};

    try {
        const response = await http<{ message: string }>('post', store().url, {
            subject_type: subject.value?.type ?? null,
            subject_id: subject.value?.id ?? null,
            type: type.value,
            message: message.value || null,
            email: !signedIn.value && email.value ? email.value : null,
            page_url: window.location.pathname + window.location.search,
            context: state.options.context ?? null,
            suggested_x: state.options.suggested?.x ?? null,
            suggested_y: state.options.suggested?.y ?? null,
            ...(website.value ? { website: website.value } : {}),
        });
        toast.success(response.message);
        closeFeedback();
    } catch (e) {
        if (e instanceof HttpError) {
            errors.value = e.data.errors ?? {};

            if (!e.data.errors) {
                toast.error(e.message);
            }
        } else {
            toast.error('Something went wrong. Please try again.');
        }
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <Dialog
        :open="state.open"
        @update:open="(open) => (open ? null : closeFeedback())"
    >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    Spotted a mistake, something missing or a bug? Tell us and a
                    moderator will review it.
                    <template v-if="subject">
                        Until then, this entry shows a lower confidence
                        score.</template
                    >
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <p
                    v-if="state.options.context || state.options.suggested"
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md border bg-muted/40 px-3 py-2 text-xs text-muted-foreground"
                >
                    <span v-if="state.options.context"
                        >About: {{ state.options.context }}</span
                    >
                    <span
                        v-if="state.options.suggested"
                        class="inline-flex items-center gap-1 text-foreground"
                    >
                        <MapPin class="size-3.5 text-warning" />
                        Suggested position x
                        {{ state.options.suggested.x.toFixed(1) }}, y
                        {{ state.options.suggested.y.toFixed(1) }}
                    </span>
                </p>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium">
                        What kind of feedback?
                    </legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label
                            v-for="option in types"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm transition has-[:checked]:border-primary has-[:checked]:bg-primary/10"
                        >
                            <input
                                v-model="type"
                                type="radio"
                                name="feedback-type"
                                :value="option.value"
                                class="accent-[var(--primary)]"
                                required
                            />
                            {{ option.label }}
                        </label>
                    </div>
                    <InputError :message="errors.type?.[0]" />
                </fieldset>

                <div class="space-y-1.5">
                    <Label for="feedback-message">
                        Details<span
                            v-if="subject"
                            class="text-muted-foreground"
                        >
                            (optional)</span
                        >
                    </Label>
                    <textarea
                        id="feedback-message"
                        v-model="message"
                        rows="4"
                        maxlength="2000"
                        :required="!subject"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        placeholder="What looks off, and what should it be? e.g. “The portal moved to the roof after the last patch.”"
                    />
                    <InputError :message="errors.message?.[0]" />
                </div>

                <div v-if="!signedIn" class="space-y-1.5">
                    <Label for="feedback-email">
                        Email
                        <span class="text-muted-foreground"
                            >(optional — only if you'd like a reply)</span
                        >
                    </Label>
                    <Input
                        id="feedback-email"
                        v-model="email"
                        type="email"
                        autocomplete="email"
                        maxlength="255"
                    />
                    <InputError :message="errors.email?.[0]" />
                </div>

                <input
                    v-model="website"
                    type="text"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                    class="hidden"
                    aria-hidden="true"
                />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="closeFeedback()"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="!type || sending">
                        Send feedback
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
