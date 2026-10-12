<!-- Hospital Security Idle Inactivity Logout Modal (Alpine.js) -->
<template x-teleport="body">
<div 
  x-show="idleTimeoutModalOpen" 
  x-cloak 
  class="fixed inset-0 z-50 overflow-y-auto" 
  @keydown.escape.window="idleTimeoutModalOpen = false"
  role="dialog" 
  aria-modal="true" 
  aria-labelledby="idle-timeout-modal-title"
>
  <!-- Full-Screen Heavy Backdrop Blur Overlay -->
  <div 
    x-show="idleTimeoutModalOpen" 
    x-transition:enter="ease-out duration-300" 
    x-transition:enter-start="opacity-0" 
    x-transition:enter-end="opacity-100" 
    x-transition:leave="ease-in duration-200" 
    x-transition:leave-start="opacity-100" 
    x-transition:leave-end="opacity-0" 
    class="fixed inset-0 bg-slate-950/70 backdrop-blur-md transition-opacity"
    @click="idleTimeoutModalOpen = false"
  ></div>

  <!-- Centered Modal Card Container -->
  <div class="fixed inset-0 z-10 flex min-h-full items-center justify-center p-4 sm:p-6 pointer-events-none">
    <div 
      x-show="idleTimeoutModalOpen" 
      x-transition:enter="ease-out duration-300" 
      x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" 
      x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
      x-transition:leave="ease-in duration-200" 
      x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
      x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95" 
      @click.outside="idleTimeoutModalOpen = false" 
      class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 p-6 sm:p-7 shadow-2xl ring-1 ring-amber-300/40 dark:ring-amber-500/20 transition-all text-left pointer-events-auto"
    >
      <!-- Ambient Lighting Radial Glow -->
      <div class="pointer-events-none absolute -top-12 -right-12 h-36 w-36 rounded-full bg-amber-500/10 blur-2xl"></div>
      <div class="pointer-events-none absolute -bottom-12 -left-12 h-36 w-36 rounded-full bg-rose-500/10 blur-2xl"></div>

      <!-- Header with Icon & Title (Cleanly Aligned) -->
      <div class="flex items-center gap-3.5">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400 ring-1 ring-amber-500/25 shadow-xs">
          <i class="ph-bold ph-clock-countdown text-2xl animate-pulse"></i>
        </div>
        <div class="min-w-0 flex-1">
          <h3 id="idle-timeout-modal-title" class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white leading-snug">
            Session Forcefully Logged Out
          </h3>
        </div>
      </div>

      <!-- Prominent Highlight Callout Box: Emphasizing "IDLING FOR 3 MINUTES" -->
      <div class="mt-5 rounded-2xl border border-amber-200/90 bg-amber-50/80 p-4 dark:border-amber-900/60 dark:bg-amber-950/40">
        <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-amber-200/70 dark:border-amber-900/50">
          <span class="text-xs font-bold text-amber-900 dark:text-amber-200 uppercase tracking-wide flex items-center gap-1.5">
            <i class="ph-fill ph-hourglass-simple-high text-sm text-amber-600 dark:text-amber-400"></i>
            Inactivity Limit Exceeded
          </span>
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-amber-200/80 text-amber-950 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300/60 dark:border-amber-700/60">
            3 MINS ELAPSED
          </span>
        </div>
        <p class="text-xs text-amber-950/90 dark:text-amber-100/90 leading-relaxed">
          Your hospital portal session was automatically and forcefully signed out because you were 
          <strong class="font-extrabold text-amber-900 dark:text-amber-200 underline decoration-amber-500/50 underline-offset-2">IDLING FOR 3 MINUTES</strong> 
          with no user interaction detected on this workstation.
        </p>
      </div>

      <!-- Action Confirmation Button -->
      <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
        <button 
          type="button" 
          @click="dismissIdleModal()"
          class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-amber-700 ring-1 ring-amber-600/30 transition-all cursor-pointer"
        >
          <i class="ph-bold ph-sign-in text-sm"></i>
          <span>I Understand &bull; Return to Sign In</span>
        </button>
      </div>
    </div>
  </div>
</div>
</template>
