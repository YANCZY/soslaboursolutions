<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative min-h-svh overflow-hidden bg-background lg:grid lg:grid-cols-[1.15fr_minmax(24rem,0.85fr)]"
    >
        <div
            class="relative hidden min-h-svh overflow-hidden lg:flex"
        >
            <div
                class="absolute inset-0 bg-[linear-gradient(135deg,rgba(24,35,42,0.96),rgba(17,25,31,0.92)_48%,rgba(12,18,24,0.98))]"
            />
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_18%_20%,rgba(34,198,233,0.26),transparent_28%),radial-gradient(circle_at_82%_28%,rgba(255,88,76,0.22),transparent_22%),radial-gradient(circle_at_68%_78%,rgba(243,178,60,0.16),transparent_18%)]"
            />
            <div
                class="absolute inset-0 opacity-30 [background-image:linear-gradient(rgba(255,255,255,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.04)_1px,transparent_1px)] [background-size:7rem_7rem]"
            />
            <div
                class="absolute inset-y-0 right-[16%] w-px bg-white/10"
            />
            <div
                class="absolute bottom-16 left-12 h-28 w-28 rounded-full border border-cyan-300/25"
            />
            <div class="relative z-10 flex w-full flex-col justify-between p-12">
                <div class="space-y-8">
                    <div
                        class="flex items-center justify-between border-b border-white/10 pb-6"
                    >
                        <Link
                            :href="home()"
                            class="flex items-center gap-4 text-white"
                        >
                            <div
                                class="flex size-14 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur-sm"
                            >
                                <AppLogoIcon
                                    class="size-9 fill-current text-cyan-400"
                                />
                            </div>
                            <div class="leading-none">
                                <div
                                    class="text-[2rem] font-semibold tracking-[0.18em] text-white"
                                >
                                    <span class="text-red-500">SOS</span>
                                    <span class="ml-3 font-normal tracking-[0.08em] text-white/90">
                                        Labour Solutions
                                    </span>
                                </div>
                                <div
                                    class="mt-3 text-xs uppercase tracking-[0.45em] text-white/70"
                                >
                                    Personalised Industry Services
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div class="max-w-xl space-y-6 pt-8">
                        <div
                            class="inline-flex items-center gap-3 rounded-full border border-cyan-300/20 bg-white/10 px-4 py-2 text-[0.7rem] font-medium uppercase tracking-[0.35em] text-cyan-100/90"
                        >
                            <span class="h-2 w-2 rounded-full bg-cyan-400" />
                            Secure Workforce Portal
                        </div>

                        <div class="space-y-4">
                            <h2
                                class="max-w-lg text-5xl font-semibold tracking-tight text-white"
                            >
                                Industrial strength access, shaped around the SOS brand.
                            </h2>
                            <p
                                class="max-w-xl text-base leading-7 text-white/70"
                            >
                                A sharper sign-in experience with the same steel,
                                charcoal, cyan, and red language your public site
                                already uses.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid max-w-2xl grid-cols-3 gap-4">
                    <div
                        class="rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm"
                    >
                        <div
                            class="text-xs uppercase tracking-[0.3em] text-white/60"
                        >
                            SOS Solutions
                        </div>
                        <div class="mt-3 text-lg font-medium text-white">
                            Labour
                        </div>
                    </div>
                    <div
                        class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-5 backdrop-blur-sm"
                    >
                        <div
                            class="text-xs uppercase tracking-[0.3em] text-cyan-100/70"
                        >
                            SOS Solutions
                        </div>
                        <div class="mt-3 text-lg font-medium text-cyan-50">
                             Engineer
                        </div>
                    </div>
                    <div
                        class="rounded-2xl border border-red-300/20 bg-red-500/10 p-5 backdrop-blur-sm"
                    >
                        <div
                            class="text-xs uppercase tracking-[0.3em] text-red-100/70"
                        >
                            SOS Solution
                        </div>
                        <div class="mt-3 text-lg font-medium text-red-50">
                             On Site
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="relative flex min-h-svh items-center justify-center px-6 py-10 sm:px-8 lg:px-12"
        >
            <div
                class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.55),rgba(255,255,255,0.86))] dark:bg-[linear-gradient(180deg,rgba(10,15,20,0.76),rgba(10,15,20,0.94))]"
            />
            <div
                class="absolute inset-0 opacity-50 [background-image:radial-gradient(rgba(17,24,39,0.08)_1px,transparent_1px)] [background-size:1.2rem_1.2rem] dark:opacity-25"
            />
            <div
                class="relative z-10 mx-auto flex w-full max-w-md flex-col justify-center space-y-6"
            >
                <div
                    class="rounded-[2rem] border border-border/70 bg-card/90 p-8 shadow-[0_24px_70px_-32px_rgba(15,23,42,0.45)] backdrop-blur-xl dark:border-white/10 dark:bg-card/95"
                >
                    <div class="mb-8 flex items-center justify-between lg:hidden">
                        <Link
                            :href="home()"
                            class="flex items-center gap-3 text-foreground"
                        >
                            <div
                                class="flex size-11 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20"
                            >
                                <AppLogoIcon
                                    class="size-7 fill-current text-primary"
                                />
                            </div>
                            <div class="leading-none">
                                <div class="text-lg font-semibold tracking-wide">
                                    <span class="text-accent">SOS</span>
                                    <span class="ml-2 text-foreground">
                                        Labour Solutions
                                    </span>
                                </div>
                                <div
                                    class="mt-1 text-[0.65rem] uppercase tracking-[0.3em] text-muted-foreground"
                                >
                                    Workforce Portal
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div class="flex flex-col space-y-2 text-center">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                            v-if="title"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="text-sm leading-6 text-muted-foreground"
                            v-if="description"
                        >
                            {{ description }}
                        </p>
                    </div>
                    <div class="mt-8">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
