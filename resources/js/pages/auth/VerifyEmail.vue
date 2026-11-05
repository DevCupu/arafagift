<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { Mail } from 'lucide-vue-next'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: null })

const page = usePage()
const { push } = useToast()
const resend = useForm({})
const logout = useForm({})
</script>

<template>
  <main class="flex min-h-screen flex-col items-center justify-center bg-ivory px-5 py-10">
    <div class="w-full max-w-sm">
      <div class="mb-10 flex items-center justify-center">
        <Link href="/"><BrandLogo tone="forest" size="md" /></Link>
      </div>

      <p class="eyebrow">Satu langkah lagi</p>
      <h1 class="mt-4 text-[1.9rem] leading-tight">Verifikasi email Anda</h1>
      <p class="mt-3 text-[0.85rem] leading-relaxed text-muted">
        Kami mengirim tautan verifikasi ke <strong class="text-forest">{{ page.props.auth.user?.email }}</strong>.
        Klik tautan tersebut untuk membuka halaman akun Anda.
      </p>

      <AppButton class="mt-8" variant="gold" block :loading="resend.processing" @click="resend.post('/email/verification-notification', { onSuccess: () => push('Tautan verifikasi baru sudah dikirim.', { tone: 'success' }) })">
        Kirim ulang tautan
        <template #icon><Mail class="h-4 w-4" /></template>
      </AppButton>

      <p class="mt-8 text-center text-[0.72rem] text-muted">
        <button type="button" class="text-forest underline underline-offset-4" @click="logout.post('/logout')">Keluar</button>
      </p>
    </div>
  </main>
</template>
