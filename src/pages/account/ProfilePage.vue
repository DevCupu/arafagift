<script setup>
import { reactive, ref } from 'vue'
import AccountShell from '@/components/storefront/AccountShell.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const { push } = useToast()
const saving = ref(false)
const form = reactive({
  name: 'Ratna Halim', email: 'ratna.halim@mail.com', phone: '0812-3344-5566', birth: '1978-04-19',
})

const save = () => {
  saving.value = true
  setTimeout(() => { saving.value = false; push('Profil disimpan', { tone: 'success' }) }, 700)
}
</script>

<template>
  <AccountShell title="Profil" sub="Data ini dipakai untuk pengiriman dan konfirmasi pesanan.">
    <div class="max-w-xl space-y-5">
      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="field-label" for="p-name">Nama lengkap</label>
          <input id="p-name" v-model="form.name" class="field" />
        </div>
        <div>
          <label class="field-label" for="p-phone">Nomor WhatsApp</label>
          <input id="p-phone" v-model="form.phone" class="field" />
        </div>
      </div>
      <div>
        <label class="field-label" for="p-email">Email</label>
        <input id="p-email" v-model="form.email" type="email" class="field" />
      </div>
      <div>
        <label class="field-label" for="p-birth">Tanggal lahir</label>
        <input id="p-birth" v-model="form.birth" type="date" class="field sm:w-56" />
      </div>
      <AppButton :loading="saving" class="mt-2" @click="save">Simpan perubahan</AppButton>
    </div>

    <div class="mt-14 max-w-xl border border-line bg-surface p-6">
      <h2 class="font-display text-xl">Kata sandi</h2>
      <p class="mt-2 text-[0.83rem] text-muted">Terakhir diubah 4 bulan lalu.</p>
      <AppButton variant="quiet" size="sm" class="mt-5">Ubah kata sandi</AppButton>
    </div>
  </AccountShell>
</template>
