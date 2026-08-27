<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { LockKeyhole } from 'lucide-vue-next'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'

defineOptions({ layout: null })

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div class="min-h-screen bg-ivory lg:grid lg:grid-cols-[1.05fr_1fr]">
    <!-- ── Brand panel ── -->
    <aside class="relative hidden overflow-hidden bg-forest-deep lg:flex lg:flex-col lg:justify-between lg:p-14">
      <div class="grain pointer-events-none absolute inset-0 opacity-20" aria-hidden="true" />
      <div class="relative">
        <Link href="/"><BrandLogo tone="ivory" size="md" /></Link>
      </div>

      <div class="relative mt-10 max-w-md">
        <div class="flex items-center gap-3">
          <span class="h-px w-8 bg-gold" />
          <p class="text-[0.68rem] font-semibold uppercase tracking-[0.25em] text-gold">ArafahGift.id</p>
        </div>
        <h1 class="mt-6 text-[2.6rem] leading-[1.08] text-ivory sm:text-[3rem]">
          Masuk ke akun Anda.
        </h1>
        <p class="mt-5 max-w-sm text-[0.95rem] leading-relaxed text-ivory/60">
          Untuk pelanggan: pantau pesanan dan wishlist. Untuk admin: kelola pesanan, katalog, dan konten situs.
        </p>

        <div class="mt-12 max-w-sm">
          <div class="arch arch--deep overflow-hidden border border-ivory/15">
            <div class="aspect-[5/6] w-full"><ProductArt art="giftset" dark /></div>
          </div>
        </div>
      </div>

      <p class="relative text-[0.72rem] text-ivory/40">© 2026 ArafahGift.id — Oleh-oleh Umrah &amp; Hajj</p>
    </aside>

    <!-- ── Form panel ── -->
    <main class="flex min-h-screen flex-col items-center justify-center px-5 py-10 sm:px-10">
      <div class="w-full max-w-sm">
        <div class="mb-10 flex items-center justify-center lg:hidden">
          <BrandLogo tone="forest" size="md" />
        </div>

        <p class="eyebrow">Selamat datang kembali</p>
        <h2 class="mt-4 text-[1.9rem] leading-tight">Masuk</h2>
        <p class="mt-3 text-[0.85rem] text-muted">Gunakan email dan kata sandi akun Anda.</p>

        <form class="mt-9 space-y-5" @submit.prevent="submit">
          <div>
            <label class="field-label" for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="field"
              placeholder="admin@arafahgift.id"
              autocomplete="username"
              required
              autofocus
            />
            <p v-if="form.errors.email" class="mt-2 text-[0.78rem] text-danger">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="field-label" for="password">Kata sandi</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              class="field"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            />
            <p v-if="form.errors.password" class="mt-2 text-[0.78rem] text-danger">{{ form.errors.password }}</p>
          </div>

          <div class="flex items-center justify-between gap-4">
            <label class="flex cursor-pointer items-center gap-2.5 text-[0.82rem] text-muted transition hover:text-forest">
              <input
                v-model="form.remember"
                type="checkbox"
                class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]"
              />
              Ingat saya
            </label>
          </div>

          <AppButton variant="gold" block type="submit" :loading="form.processing">
            Masuk
            <template #icon><LockKeyhole class="h-4 w-4" /></template>
          </AppButton>
        </form>

        <p class="mt-10 text-center text-[0.72rem] text-muted">
          Belum punya akun? <Link href="/register" class="text-forest underline underline-offset-4">Daftar</Link>
          · <Link href="/" class="text-forest underline underline-offset-4">Kembali ke storefront</Link>
        </p>
      </div>
    </main>
  </div>
</template>