<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AccountShell from '@/components/storefront/AccountShell.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({ user: { type: Object, required: true } })
const { push } = useToast()

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone ?? '',
  birth_date: props.user.birth_date ?? '',
})

const save = () => {
  form.put('/akun', {
    preserveScroll: true,
    onSuccess: () => push('Profil disimpan', { tone: 'success' }),
  })
}

const showPasswordForm = ref(false)
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' })

const savePassword = () => {
  passwordForm.put('/akun/kata-sandi', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
      showPasswordForm.value = false
      push('Kata sandi diperbarui', { tone: 'success' })
    },
  })
}
</script>

<template>
  <AccountShell title="Profil" sub="Data ini dipakai untuk pengiriman dan konfirmasi pesanan.">
    <div class="max-w-xl space-y-5">
      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="field-label" for="p-name">Nama lengkap</label>
          <input id="p-name" v-model="form.name" class="field" />
          <p v-if="form.errors.name" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.name }}</p>
        </div>
        <div>
          <label class="field-label" for="p-phone">Nomor WhatsApp</label>
          <input id="p-phone" v-model="form.phone" class="field" />
        </div>
      </div>
      <div>
        <label class="field-label" for="p-email">Email</label>
        <input id="p-email" v-model="form.email" type="email" class="field" />
        <p v-if="form.errors.email" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.email }}</p>
      </div>
      <div>
        <label class="field-label" for="p-birth">Tanggal lahir</label>
        <input id="p-birth" v-model="form.birth_date" type="date" class="field sm:w-56" />
      </div>
      <AppButton :loading="form.processing" class="mt-2" @click="save">Simpan perubahan</AppButton>
    </div>

    <div class="mt-14 max-w-xl border border-line bg-surface p-6">
      <h2 class="font-display text-xl">Kata sandi</h2>
      <p class="mt-2 text-[0.83rem] text-muted">Ubah kata sandi akun Anda.</p>
      <AppButton v-if="!showPasswordForm" variant="quiet" size="sm" class="mt-5" @click="showPasswordForm = true">Ubah kata sandi</AppButton>

      <div v-else class="mt-5 space-y-4">
        <div>
          <label class="field-label" for="pw-current">Kata sandi saat ini</label>
          <input id="pw-current" v-model="passwordForm.current_password" type="password" class="field" />
          <p v-if="passwordForm.errors.current_password" class="mt-1.5 text-[0.72rem] text-danger">{{ passwordForm.errors.current_password }}</p>
        </div>
        <div>
          <label class="field-label" for="pw-new">Kata sandi baru</label>
          <input id="pw-new" v-model="passwordForm.password" type="password" class="field" />
          <p v-if="passwordForm.errors.password" class="mt-1.5 text-[0.72rem] text-danger">{{ passwordForm.errors.password }}</p>
        </div>
        <div>
          <label class="field-label" for="pw-confirm">Ulangi kata sandi baru</label>
          <input id="pw-confirm" v-model="passwordForm.password_confirmation" type="password" class="field" />
        </div>
        <div class="flex gap-3">
          <AppButton size="sm" :loading="passwordForm.processing" @click="savePassword">Simpan</AppButton>
          <AppButton size="sm" variant="quiet" @click="showPasswordForm = false">Batal</AppButton>
        </div>
      </div>
    </div>
  </AccountShell>
</template>
