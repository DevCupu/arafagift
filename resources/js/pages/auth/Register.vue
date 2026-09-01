<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ShoppingBag, UserPlus } from 'lucide-vue-next'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'

defineOptions({ layout: null })

defineProps({ checkoutRedirect: { type: Boolean, default: false } })

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}

// Laravel selalu menaruh error rule "confirmed" di field `password`, bukan `password_confirmation` —
// arahkan ke field yang benar biar jelas mana yang harus dibetulkan.
const isConfirmMismatch = computed(() => form.errors.password?.startsWith('Konfirmasi'))
const passwordError = computed(() => (isConfirmMismatch.value ? null : form.errors.password))
const confirmationError = computed(() => (isConfirmMismatch.value ? form.errors.password : null))
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
          <p class="text-[0.68rem] font-semibold uppercase tracking-[0.25em] text-gold">Akun Pelanggan</p>
        </div>
        <h1 class="mt-6 text-[2.6rem] leading-[1.08] text-ivory sm:text-[3rem]">
          Simpan alamat, pantau pesanan.
        </h1>
        <p class="mt-5 max-w-sm text-[0.95rem] leading-relaxed text-ivory/60">
          Daftar sekali, checkout berikutnya jadi lebih cepat — alamat dan wishlist tersimpan otomatis.
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

        <div v-if="checkoutRedirect" class="mb-6 flex items-start gap-3 border border-gold/40 bg-gold/[0.08] p-4">
          <ShoppingBag class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
          <p class="text-[0.8rem] leading-relaxed text-forest">Buat akun dulu untuk lanjut checkout. Keranjang Anda tetap tersimpan.</p>
        </div>

        <p class="eyebrow">Selamat datang</p>
        <h2 class="mt-4 text-[1.9rem] leading-tight">Buat akun baru</h2>
        <p class="mt-3 text-[0.85rem] text-muted">Gratis, cuma butuh beberapa detik.</p>

        <form class="mt-9 space-y-5" @submit.prevent="submit">
          <div>
            <label class="field-label" for="name">Nama lengkap</label>
            <input id="name" v-model="form.name" class="field" placeholder="Nama Anda" autocomplete="name" required autofocus />
            <p v-if="form.errors.name" class="mt-2 text-[0.78rem] text-danger">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="field-label" for="email">Email</label>
            <input id="email" v-model="form.email" type="email" class="field" placeholder="nama@email.com" autocomplete="username" required />
            <p v-if="form.errors.email" class="mt-2 text-[0.78rem] text-danger">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="field-label" for="phone">Nomor WhatsApp (opsional)</label>
            <input id="phone" v-model="form.phone" class="field" placeholder="08xx xxxx xxxx" autocomplete="tel" />
          </div>

          <div>
            <label class="field-label" for="password">Kata sandi</label>
            <input id="password" v-model="form.password" type="password" class="field" placeholder="••••••••" autocomplete="new-password" required />
            <p v-if="passwordError" class="mt-2 text-[0.78rem] text-danger">{{ passwordError }}</p>
          </div>

          <div>
            <label class="field-label" for="password_confirmation">Ulangi kata sandi</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="field" placeholder="••••••••" autocomplete="new-password" required />
            <p v-if="confirmationError" class="mt-2 text-[0.78rem] text-danger">{{ confirmationError }}</p>
          </div>

          <AppButton variant="gold" block type="submit" :loading="form.processing">
            Daftar
            <template #icon><UserPlus class="h-4 w-4" /></template>
          </AppButton>
        </form>

        <p class="mt-10 text-center text-[0.72rem] text-muted">
          Sudah punya akun? <Link href="/login" class="text-forest underline underline-offset-4">Masuk</Link>
        </p>
      </div>
    </main>
  </div>
</template>
